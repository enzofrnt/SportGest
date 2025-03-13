<?php

namespace App\Controller\Dashboard;

use App\Entity\Responsable;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ResponsableCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Responsable::class;
    }

    public function configureActions(\EasyCorp\Bundle\EasyAdminBundle\Config\Actions $actions): \EasyCorp\Bundle\EasyAdminBundle\Config\Actions
    {
        $user = $this->getUser();

        if (!($user instanceof Responsable && $this->isGranted('ROLE_ADMIN'))) {
            throw new AccessDeniedException('Seuls les responsables admin peuvent gérer les responsables');
        }

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN');
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
            TextField::new('poste'),
            BooleanField::new('isAdmin')
                ->setLabel('Administrateur')
                ->renderAsSwitch(true),
        ];
    }

    public function configureCrud(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud $crud): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud
    {
        return $crud
            ->setEntityLabelInSingular('Responsable')
            ->setEntityLabelInPlural('Responsables')
            ->setDefaultSort(['nom' => 'ASC', 'prenom' => 'ASC'])
            ->setSearchFields(['nom', 'prenom', 'email', 'poste']);
    }
}
