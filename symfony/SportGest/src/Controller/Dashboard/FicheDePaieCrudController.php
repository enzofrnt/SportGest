<?php

namespace App\Controller\Dashboard;

use App\Entity\FicheDePaie;
use App\Entity\Responsable;
use App\Entity\Coach;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use App\Repository\SeanceRepository;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Form\FicheDePaieGenerationType;
use App\Enum\PeriodePaie;
use App\Enum\StatutSeance;
use Psr\Log\LoggerInterface;

class FicheDePaieCrudController extends AbstractCrudController
{
    public function __construct(
        private SeanceRepository $seanceRepository,
        private LoggerInterface $logger
    ) {}

    public static function getEntityFqcn(): string
    {
        return FicheDePaie::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $user = $this->getUser();

        if (!($user instanceof Responsable) and !($user instanceof Coach)) {
            throw new AccessDeniedException('Seuls les responsables et les coaches peuvent gérer les fiches de paie');
        }

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Génération de la fiche de paie')
                    ->setCssClass('btn btn-primary')
                    ->linkToCrudAction('generationFichePaie');
            })
            ->setPermission(Action::EDIT, 'ROLE_RESPONSABLE')
            ->setPermission(Action::DELETE, 'ROLE_RESPONSABLE')
            ->setPermission(Action::NEW, 'ROLE_RESPONSABLE');
    }

    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser();

        $fields = [
            IdField::new('id')->hideOnForm(),
            ChoiceField::new('periode')
                ->setFormType(\Symfony\Component\Form\Extension\Core\Type\EnumType::class)
                ->setFormTypeOptions([
                    'class' => \App\Enum\PeriodePaie::class,
                    'choice_label' => function (\App\Enum\PeriodePaie $choice) {
                        return $choice->value;
                    }
                ])
                ->formatValue(function ($value) {
                    return $value instanceof \App\Enum\PeriodePaie ? $value->value : '';
                }),
            Field::new('totalHeure', 'Total heures')
                ->setVirtual(true)
                ->formatValue(function ($value, $entity) {
                    try {
                        if (!$entity instanceof FicheDePaie || !$entity->getCoach()) {
                            $this->logger->warning('Entity invalide ou coach absent', [
                                'entity_type' => get_class($entity),
                                'entity_id' => $entity instanceof FicheDePaie ? $entity->getId() : 'unknown',
                                'has_coach' => $entity instanceof FicheDePaie ? ($entity->getCoach() ? 'yes' : 'no') : 'n/a'
                            ]);
                            return 'N/A';
                        }

                        $this->logger->info('Calcul des heures pour fiche de paie', [
                            'fiche_id' => $entity->getId(),
                            'coach' => $entity->getCoach()->getNom() . ' ' . $entity->getCoach()->getPrenom(),
                            'periode' => $entity->getPeriode()->value
                        ]);

                        $totalHeures = $this->calculerTotalHeures($entity);

                        // Log explicite avec valeur
                        $this->logger->debug('Valeur calculée: ' . $totalHeures);

                        // Log explicite des détails des attributs du log
                        $this->logger->debug('Détails du log', [
                            'fiche_id_type' => gettype($entity->getId()),
                            'total_heures_type' => gettype($totalHeures),
                            'total_heures_value' => $totalHeures
                        ]);

                        $this->logger->info('Résultat calcul des heures', [
                            'fiche_id' => $entity->getId(),
                            'total_heures' => $totalHeures
                        ]);

                        // Log explicite du résultat formaté
                        $resultat = sprintf('%.2f h', $totalHeures);
                        $this->logger->debug('Valeur formatée à retourner: ' . $resultat . ' (type: ' . gettype($totalHeures) . ')');

                        return $resultat;
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors du calcul des heures', [
                            'message' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        return 'Erreur';
                    }
                }),
            MoneyField::new('montantTotal')
                ->setCurrency('EUR')
                ->setStoredAsCents(true)
                ->setLabel('Montant'),
        ];

        // Afficher le champ coach uniquement pour les responsables
        if (!($user instanceof Coach)) {
            $fields[] = AssociationField::new('coach');
        }

        return $fields;
    }

    /**
     * Calcule le total d'heures des séances validées pour une fiche de paie
     */
    private function calculerTotalHeures(FicheDePaie $ficheDePaie): float
    {
        // Récupérer la période et le coach
        $periode = $ficheDePaie->getPeriode();
        $coach = $ficheDePaie->getCoach();

        $this->logger->debug('Début calcul total heures', [
            'fiche_id' => $ficheDePaie->getId(),
            'coach_id' => $coach ? $coach->getId() : null,
            'periode' => $periode ? $periode->value : null
        ]);

        if (!$coach) {
            $this->logger->warning('Coach non trouvé pour la fiche de paie', [
                'fiche_id' => $ficheDePaie->getId()
            ]);
            return 0;
        }

        // Récupérer toutes les séances validées de ce coach
        $seancesValidees = $this->seanceRepository->findValidatedSeancesByCoach($coach);

        $this->logger->debug('Séances validées récupérées', [
            'fiche_id' => $ficheDePaie->getId(),
            'coach_id' => $coach->getId(),
            'nombre_seances' => count($seancesValidees)
        ]);

        if (empty($seancesValidees)) {
            $this->logger->debug('Aucune séance validée trouvée pour ce coach', [
                'coach_id' => $coach->getId(),
                'coach_nom' => $coach->getNom() . ' ' . $coach->getPrenom()
            ]);
            return 0;
        }

        // Trouver les dates qui correspondent le mieux à cette fiche de paie
        // en analysant le montant
        $montantCible = $ficheDePaie->getMontantTotal();
        $tarifHoraire = $coach->getTarifHoraire();

        $this->logger->debug('Données financières', [
            'fiche_id' => $ficheDePaie->getId(),
            'montant_cible' => $montantCible,
            'tarif_horaire' => $tarifHoraire,
            'montant_cible_type' => gettype($montantCible),
            'tarif_horaire_type' => gettype($tarifHoraire)
        ]);

        if ($tarifHoraire <= 0) {
            $this->logger->debug('Tarif horaire invalide', [
                'coach_id' => $coach->getId(),
                'tarif' => $tarifHoraire
            ]);
            return 0;
        }

        // Si le montant est stocké en centimes, le convertir en euros
        if ($montantCible > 1000) { // Heuristique simple pour détecter si c'est en centimes
            $montantCible = $montantCible / 100;
            $this->logger->debug('Montant converti de centimes à euros', [
                'montant_original' => $ficheDePaie->getMontantTotal(),
                'montant_converti' => $montantCible
            ]);
        }

        // Heures théoriques basées sur le montant
        $heuresTheoriques = $montantCible / $tarifHoraire;

        $this->logger->debug('Heures théoriques calculées', [
            'id_fiche' => $ficheDePaie->getId(),
            'montant' => $montantCible,
            'tarif_horaire' => $tarifHoraire,
            'heures_theoriques' => $heuresTheoriques
        ]);

        // Calculer manuellement le nombre d'heures travaillées
        $totalMinutes = 0;
        foreach ($seancesValidees as $seance) {
            $duree = $seance->getDureeSeance();
            $this->logger->debug('Séance: ID ' . $seance->getId() . ', durée: ' . $duree . ' minutes');
            $totalMinutes += $duree;
        }

        $totalHeures = round($totalMinutes / 60, 2);

        $this->logger->debug('Total des heures pour toutes les séances validées', [
            'coach_id' => $coach->getId(),
            'total_minutes' => $totalMinutes,
            'total_heures' => $totalHeures,
            'nb_seances' => count($seancesValidees)
        ]);

        return $totalHeures;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Fiche de paie')
            ->setEntityLabelInPlural('Fiches de paie')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user = $this->getUser();

        if ($user instanceof Coach) {
            // Les coachs ne voient que leurs propres fiches de paie
            $qb->andWhere('entity.coach = :coach')
                ->setParameter('coach', $user);
        }
        // Les responsables voient toutes les fiches (comportement par défaut)

        return $qb;
    }

    public function generationFichePaie(AdminContext $context)
    {
        $ficheDePaie = new FicheDePaie();

        $form = $this->createForm(FicheDePaieGenerationType::class, $ficheDePaie);

        $request = $context->getRequest();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $coach = $ficheDePaie->getCoach();
                $typePeriode = $form->get('typePeriode')->getData();

                if ($typePeriode === 'standard') {
                    $periode = $ficheDePaie->getPeriode();
                    // Logique existante pour Mois/Semaine en cours
                    $dateDebut = new \DateTime();
                    $dateFin = new \DateTime();

                    if ($periode == PeriodePaie::MOIS) {
                        $dateDebut->modify('first day of this month')->setTime(0, 0, 0);
                        $dateFin->modify('last day of this month')->setTime(23, 59, 59);
                    } else {
                        $jour = $dateDebut->format('N');
                        $decalage = $jour - 1;
                        $dateDebut->modify("-{$decalage} days")->setTime(0, 0, 0);
                        $dateFin = clone $dateDebut;
                        $dateFin->modify('+6 days')->setTime(23, 59, 59);
                    }
                } else if ($typePeriode === 'mois_precedent') {
                    // Logique pour le mois précédent
                    $dateDebut = new \DateTime('first day of last month');
                    $dateDebut->setTime(0, 0, 0);
                    $dateFin = new \DateTime('last day of last month');
                    $dateFin->setTime(23, 59, 59);

                    $this->logger->debug('Période mois précédent sélectionnée', [
                        'date_debut' => $dateDebut->format('Y-m-d H:i:s'),
                        'date_fin' => $dateFin->format('Y-m-d H:i:s')
                    ]);
                } else {
                    // Période personnalisée
                    $dateDebut = $form->get('dateDebut')->getData();
                    $dateFin = $form->get('dateFin')->getData();

                    if (!$dateDebut || !$dateFin) {
                        $this->addFlash('error', 'Veuillez sélectionner une période valide.');
                        return $this->redirect($this->container->get(AdminUrlGenerator::class)
                            ->setController(self::class)
                            ->setAction('generationFichePaie')
                            ->generateUrl());
                    }
                }

                $this->logger->info('Génération de fiche de paie initiée', [
                    'coach' => $coach->getNom() . ' ' . $coach->getPrenom() . ' (ID: ' . $coach->getId() . ')',
                    'date_debut' => $dateDebut->format('d/m/Y'),
                    'date_fin' => $dateFin->format('d/m/Y')
                ]);

                // Récupérer les séances du coach pour la période sélectionnée
                $seances = $this->seanceRepository->findSeancesByCoachAndDateRange($coach, $dateDebut, $dateFin);

                $this->logger->info('Séances trouvées pour la période', [
                    'nombre_total' => count($seances)
                ]);

                // Log détaillé de chaque séance trouvée
                foreach ($seances as $index => $seance) {
                    $this->logger->debug('Séance #' . ($index + 1), [
                        'id' => $seance->getId(),
                        'date_heure' => $seance->getDateHeure()->format('d/m/Y H:i'),
                        'type' => $seance->getTypeSeance()->value,
                        'statut' => $seance->getStatut()->value,
                        'niveau' => $seance->getNiveauSeance()->value,
                        'duree_minutes' => $seance->getDureeSeance()
                    ]);
                }

                // Calculer le montant total uniquement pour les séances validées
                $montantTotal = 0;
                $seancesValidees = 0;

                foreach ($seances as $seance) {
                    // Ne prendre en compte que les séances validées
                    if ($seance->getStatut() === StatutSeance::VALIDEE) {
                        $dureeHeures = $seance->getDureeSeance() / 60;  // Convertir minutes en heures
                        $montantSeance = $dureeHeures * $coach->getTarifHoraire();
                        $montantTotal += $montantSeance * 100;
                        $seancesValidees++;

                        $this->logger->info('Calcul pour séance validée ID ' . $seance->getId(), [
                            'date' => $seance->getDateHeure()->format('d/m/Y H:i'),
                            'duree_minutes' => $seance->getDureeSeance(),
                            'duree_heures' => round($dureeHeures, 2),
                            'tarif_horaire' => $coach->getTarifHoraire() . ' EUR',
                            'montant_seance' => round($montantSeance, 2) . ' EUR'
                        ]);
                    } else {
                        $this->logger->debug('Séance ignorée (non validée) ID ' . $seance->getId(), [
                            'date' => $seance->getDateHeure()->format('d/m/Y H:i'),
                            'statut' => $seance->getStatut()->value
                        ]);
                    }
                }

                // Arrondir le montant total à 2 décimales
                $montantTotal = round($montantTotal, 2);

                $this->logger->info('Résumé du calcul', [
                    'seances_totales' => count($seances),
                    'seances_validees' => $seancesValidees,
                    'seances_ignorees' => count($seances) - $seancesValidees,
                    'montant_total' => $montantTotal . ' EUR'
                ]);

                // Mettre à jour le montant total
                $ficheDePaie->setMontantTotal($montantTotal);

                // Persister l'entité
                $entityManager = $this->container->get('doctrine')->getManager();
                $entityManager->persist($ficheDePaie);
                $entityManager->flush();

                $this->logger->info('Fiche de paie générée et enregistrée', [
                    'id' => $ficheDePaie->getId(),
                    'montant' => $ficheDePaie->getMontantTotal() . ' EUR'
                ]);

                $this->addFlash('success', 'La fiche de paie a été générée avec succès.');

                return $this->redirect($this->container->get(AdminUrlGenerator::class)
                    ->setController(self::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl());
            }
        }

        return $this->render('admin/fiche_de_paie/generation.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}