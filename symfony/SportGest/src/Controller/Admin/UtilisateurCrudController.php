<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use App\Entity\Sportif;
use App\Entity\Coach;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\RequestStack;

class UtilisateurCrudController extends AbstractCrudController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
    }

    public function configureActions(\EasyCorp\Bundle\EasyAdminBundle\Config\Actions $actions): \EasyCorp\Bundle\EasyAdminBundle\Config\Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            TextField::new('prenom'),
            EmailField::new('email'),
            TextField::new('password')
                ->setFormType(PasswordType::class)
                ->setRequired(true)
                ->onlyOnForms(),
            TextField::new('role', 'Role')
                ->setVirtual(true)
                ->hideOnForm(),
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        $request = $this->getContext()->getRequest();
        $formData = $request->request->all();
        $role = $formData['Utilisateur']['role'] ?? null;
        
        if ($request->isMethod('POST') && !$role) {
            $this->addFlash('danger', 'Vous devez sélectionner un type d\'utilisateur (Sportif ou Coach)');
            return null;
        }
        
        return match ($role) {
            'sportif' => new Sportif(),
            'coach' => new Coach(),
            default => null,
        };
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}

