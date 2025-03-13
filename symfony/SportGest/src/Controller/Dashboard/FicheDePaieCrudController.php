<?php

namespace App\Controller\Dashboard;

use App\Entity\FicheDePaie;
use App\Entity\Responsable;
use App\Entity\Coach;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class FicheDePaieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FicheDePaie::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $user = $this->getUser();

        if (!($user instanceof Responsable) and !($user instanceof Coach)) {
            throw new AccessDeniedException('Seuls les responsables et les coaches peuvent gérer les fiches de paie');
        }

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_RESPONSABLE')
            ->setPermission(Action::EDIT, 'ROLE_RESPONSABLE')
            ->setPermission(Action::DELETE, 'ROLE_RESPONSABLE');
    }

    public function configureFields(string $pageName): iterable
    {
        $user = $this->getUser();
        
        $fields = [
            IdField::new('id')->hideOnForm(),
            ChoiceField::new('periode')
                ->setFormType(\Symfony\Component\Form\Extension\Core\Type\EnumType::class)
                ->setFormTypeOptions([
                    'class' => \App\Enum\PeriodePaie::class,
                    'choice_label' => function(\App\Enum\PeriodePaie $choice) {
                        return $choice->value;
                    }
                ])
                ->formatValue(function ($value) {
                    return $value instanceof \App\Enum\PeriodePaie ? $value->value : '';
                }),
            MoneyField::new('montantTotal')->setCurrency('EUR')->setLabel('Montant'),
        ];
        
        // Afficher le champ coach uniquement pour les responsables
        if (!($user instanceof Coach)) {
            $fields[] = AssociationField::new('coach');
        }
        
        return $fields;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Fiche de paie')
            ->setEntityLabelInPlural('Fiches de paie')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $user = $this->getUser();

        if ($user instanceof Coach) {
            // Les coachs ne voient que leurs propres fiches de paie
            $qb->andWhere('entity.coach = :coach')
               ->setParameter('coach', $user);
        }
        // Les responsables voient toutes les fiches (comportement par défaut)

        return $qb;
    }
}
