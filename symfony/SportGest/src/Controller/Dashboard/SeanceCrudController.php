<?php

namespace App\Controller\Dashboard;

use App\Entity\Coach;
use App\Entity\Seance;
use App\Enum\NiveauSportif;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SeanceCrudController extends AbstractCrudController
{
    private AdminUrlGenerator $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return Seance::class;
    }

    public function configureActions(\EasyCorp\Bundle\EasyAdminBundle\Config\Actions $actions): \EasyCorp\Bundle\EasyAdminBundle\Config\Actions
    {
        $user = $this->getUser();

        $viewAction = Action::new('view', 'Voir')
            ->linkToCrudAction('detail')
            ->addCssClass('btn btn-info');

        $editAction = Action::new('edit', 'Modifier')
            ->linkToCrudAction('edit')
            ->addCssClass('btn btn-primary');

        $deleteAction = Action::new('delete', 'Supprimer')
            ->linkToCrudAction('delete')
            ->addCssClass('btn btn-danger');

        $actions
            ->add(Crud::PAGE_INDEX, $viewAction)
            // ->add(Crud::PAGE_DETAIL, Action::EDIT)
            // ->add(Crud::PAGE_DETAIL, Action::DELETE)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Créer une séance');
            });

        // Personnalisation des actions selon le rôle
        if ($user instanceof Coach) {
            $actions->setPermission(Action::NEW, 'ROLE_COACH')
                ->setPermission(Action::EDIT, 'ROLE_COACH')
                ->setPermission(Action::DELETE, 'ROLE_COACH');
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser();
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('themeSeance'),
            DateTimeField::new('dateHeure'),
            ChoiceField::new('niveauSeance', 'Niveau')
                ->setFormType(\Symfony\Component\Form\Extension\Core\Type\EnumType::class)
                ->setFormTypeOptions([
                    'class' => \App\Enum\NiveauSportif::class,
                    'choice_label' => function(\App\Enum\NiveauSportif $choice) {
                        return $choice->value; // Affiche la valeur (ex: "débutant") au lieu de la clé
                    }
                ])
                ->formatValue(function ($value) {
                    // Convertir l'enum en chaîne pour l'affichage
                    return $value instanceof \App\Enum\NiveauSportif ? $value->value : '';
                }),
        ];

        // Si c'est un nouveau formulaire, définir automatiquement le coach actuel
        if ($pageName === Crud::PAGE_NEW) {
            $fields[] = AssociationField::new('coach')
                ->setFormTypeOption('data', $user)
                ->setFormTypeOption('disabled', true);
        } else {
            $fields[] = AssociationField::new('coach')
                ->setFormTypeOption('disabled', true);
        }

        $fields[] = AssociationField::new('exercices');
        $fields[] = AssociationField::new('sportifs');

        return $fields;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user = $this->getUser();

        if ($user instanceof Coach) {
            // Les coachs ne voient que leurs propres séances
            $qb->andWhere('entity.coach = :coach')
               ->setParameter('coach', $user)
               ->orderBy('entity.dateHeure', 'DESC');
        }

        return $qb;
    }

    public function configureCrud(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud $crud): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud
    {
        return $crud
            ->setEntityLabelInSingular('Séance')
            ->setEntityLabelInPlural('Séances')
            ->setDefaultSort(['dateHeure' => 'DESC'])
            ->setSearchFields(['themeSeance', 'coach.nom', 'coach.prenom'])
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une nouvelle séance')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier la séance')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détails de la séance');
    }

    public function edit(AdminContext $context)
    {
        $seance = $context->getEntity()->getInstance();
        $user = $this->getUser();

        if ($user instanceof Coach && $seance->getCoach() !== $user) {
            throw new AccessDeniedException('Vous ne pouvez pas modifier une séance dont vous n\'êtes pas le créateur');
        }

        return parent::edit($context);
    }

    public function delete(AdminContext $context)
    {
        $seance = $context->getEntity()->getInstance();
        $user = $this->getUser();

        if ($user instanceof Coach && $seance->getCoach() !== $user) {
            throw new AccessDeniedException('Vous ne pouvez pas supprimer une séance dont vous n\'êtes pas le créateur');
        }

        return parent::delete($context);
    }
}
