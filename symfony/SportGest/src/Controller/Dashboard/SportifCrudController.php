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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class SportifCrudController extends AbstractCrudController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

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
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            TextField::new('prenom'),
            EmailField::new('email'),
            DateField::new('dateInscription', 'Date d\'inscription'),
            ChoiceField::new('niveauSportif', 'Niveau')
                ->setFormType(\Symfony\Component\Form\Extension\Core\Type\EnumType::class)
                ->setFormTypeOptions([
                    'class' => \App\Enum\NiveauSportif::class,
                    'choice_label' => function(\App\Enum\NiveauSportif $choice) {
                        return $choice->value;
                    }
                ])
                ->formatValue(function ($value) {
                    return $value instanceof \App\Enum\NiveauSportif ? $value->value : '';
                })
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

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Sportif')
            ->setEntityLabelInPlural('Sportifs')
            ->setDefaultSort(['nom' => 'ASC', 'prenom' => 'ASC']);
    }

    public function createEntity(string $entityFqcn)
    {
        $sportif = new Sportif();
        $sportif->setRoles(['ROLE_SPORTIF']);
        return $sportif;
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
