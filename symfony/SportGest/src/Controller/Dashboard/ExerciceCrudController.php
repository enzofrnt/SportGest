<?php

namespace App\Controller\Dashboard;

use App\Entity\Coach;
use App\Entity\Exercice;
use App\Entity\Responsable;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Enum\DifficulteExercice;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class ExerciceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Exercice::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $user = $this->getUser();

        if (!($user instanceof Coach || $user instanceof Responsable)) {
            throw new AccessDeniedException('Accès non autorisé');
        }

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Créer un exercice');
            })
            ->setPermission(Action::NEW, 'ROLE_COACH')
            ->setPermission(Action::EDIT, 'ROLE_COACH')
            ->setPermission(Action::DELETE, 'ROLE_COACH');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('nom'),
            TextEditorField::new('description'),
            ChoiceField::new('difficulte')
                ->setFormType(\Symfony\Component\Form\Extension\Core\Type\EnumType::class)
                ->setFormTypeOptions([
                    'class' => \App\Enum\DifficulteExercice::class,
                    'choice_label' => function(\App\Enum\DifficulteExercice $choice) {
                        return $choice->value;
                    }
                ])
                ->formatValue(function ($value) {
                    // Convertir l'enum en chaîne pour l'affichage
                    return $value instanceof \App\Enum\DifficulteExercice ? $value->value : '';
                }),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Exercice')
            ->setEntityLabelInPlural('Exercices')
            ->setDefaultSort(['nom' => 'ASC']);
    }
}
