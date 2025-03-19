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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class ResponsableCrudController extends AbstractCrudController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

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
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            TextField::new('prenom'),
            EmailField::new('email'),
        ];

        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            $fields[] = TextField::new('password')
                ->setFormType(PasswordType::class)
                ->setLabel('Mot de passe')
                ->setHelp($pageName === Crud::PAGE_EDIT ? 'Laissez vide pour ne pas modifier le mot de passe' : 'Le mot de passe est requis pour la création')
                ->setRequired($pageName === Crud::PAGE_NEW);
        }

        return $fields;
    }

    public function configureCrud(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud $crud): \EasyCorp\Bundle\EasyAdminBundle\Config\Crud
    {
        return $crud
            ->setEntityLabelInSingular('Responsable')
            ->setEntityLabelInPlural('Responsables')
            ->setDefaultSort(['nom' => 'ASC', 'prenom' => 'ASC'])
            ->setSearchFields(['nom', 'prenom', 'email', 'poste']);
    }

    public function createEntity(string $entityFqcn)
    {
        $responsable = new Responsable();
        $responsable->setRoles(['ROLE_RESPONSABLE']);
        return $responsable;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $entityInstance->getPassword()
            );
            $entityInstance->setPassword($hashedPassword);
        }
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword(
                $entityInstance,
                $entityInstance->getPassword()
            );
            $entityInstance->setPassword($hashedPassword);
        }
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }
}
