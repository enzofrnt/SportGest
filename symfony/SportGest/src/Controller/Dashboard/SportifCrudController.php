<?php

namespace App\Controller\Dashboard;

use App\Entity\Coach;
use App\Entity\Responsable;
use App\Entity\Sportif;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SportifCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sportif::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $user = $this->getUser();

        if (!($user instanceof Responsable)) {
            throw new AccessDeniedException('Seuls les responsables peuvent gérer les sportifs');
        }

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_RESPONSABLE')
            ->setPermission(Action::EDIT, 'ROLE_RESPONSABLE')
            ->setPermission(Action::DELETE, 'ROLE_RESPONSABLE');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            TextField::new('prenom'),
            EmailField::new('email'),
            TelephoneField::new('telephone'),
            DateField::new('dateNaissance'),
            AssociationField::new('seances')
                ->setFormTypeOption('by_reference', false),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Sportif')
            ->setEntityLabelInPlural('Sportifs')
            ->setDefaultSort(['nom' => 'ASC', 'prenom' => 'ASC']);
    }
}
