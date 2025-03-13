<?php

namespace App\Controller\Coach;

use App\Entity\Seance;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use App\Enum\TypeSeance;
use App\Enum\StatutSeance;
use App\Enum\NiveauSportif;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('ROLE_COACH')]
class SeanceCrudController extends AbstractCrudController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Seance::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        $coach = $this->getUser();
        
        return [
            IdField::new('id')->hideOnForm(),
            DateTimeField::new('dateHeure', 'Date et heure'),
            ChoiceField::new('typeSeance', 'Type de séance')
                ->setChoices(array_combine(TypeSeance::values(), TypeSeance::values())),
            TextField::new('themeSeance', 'Thème de la séance'),
            ChoiceField::new('niveauSeance', 'Niveau de la séance')
                ->setChoices(array_combine(NiveauSportif::values(), NiveauSportif::values())),
            ChoiceField::new('statut', 'Statut')
                ->setChoices(array_combine(StatutSeance::values(), StatutSeance::values())),
            AssociationField::new('sportifs', 'Sportifs')
                ->setFormTypeOption('by_reference', false)
                ->formatValue(function ($value, $entity) {
                    return count($entity->getSportifs()) . ' sportif(s)';
                }),
            AssociationField::new('exercices', 'Exercices')
                ->setFormTypeOption('by_reference', false)
                ->formatValue(function ($value, $entity) {
                    $exercices = $entity->getExercices();
                    return implode(', ', array_map(function($exercice) {
                        return $exercice->getNom();
                    }, $exercices->toArray()));
                }),
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        $seance = new Seance();
        $seance->setCoach($this->getUser());
        return $seance;
    }
} 