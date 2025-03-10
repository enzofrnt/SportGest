<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;

class ArticleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Article::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('titre')
                ->setMaxLength(20),
            AssociationField::new('categorie')
                ->setLabel('Catégorie')
                ->setRequired(true),
            TextEditorField::new('description')
                ->setNumOfRows(6),
            Field::new('publie'),
            DateField::new('date_publication')
                ->setLabel('Date de publication')
                ->setFormat('dd MMMM yyyy'),
        ];
    }
}
