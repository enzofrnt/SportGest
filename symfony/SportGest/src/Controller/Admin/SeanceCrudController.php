<?php

namespace App\Controller\Admin;

use App\Entity\Seance;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class SeanceCrudController extends AbstractCrudController
{
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
        return [
            IdField::new('id')->hideOnForm(),
            DateTimeField::new('dateHeure', 'Date et heure'),
            TextField::new('typeSeance', 'Type de séance'),
            TextField::new('themeSeance', 'Thème de la séance'),
            TextField::new('niveauSeance', 'Niveau de la séance'),
            TextField::new('statut', 'Statut'),
            AssociationField::new('coach', 'Coach'),
            AssociationField::new('sportifs', 'Sportifs')
                ->setFormTypeOption('by_reference', false),
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
}
