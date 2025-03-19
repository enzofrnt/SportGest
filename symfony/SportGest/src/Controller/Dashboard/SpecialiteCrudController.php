<?php

namespace App\Controller\Dashboard;

use App\Entity\Specialite;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SpecialiteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Specialite::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Spécialité')
            ->setEntityLabelInPlural('Spécialités')
            ->setDefaultSort(['nom' => 'ASC'])
            ->setSearchFields(['nom']);
    }
} 