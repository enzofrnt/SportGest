<?php

namespace App\Controller\Dashboard;

use App\Entity\Reservation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class ReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('sportif')
                ->setRequired(true)
                ->autocomplete(),
            AssociationField::new('seance')
                ->setRequired(true)
                ->autocomplete(),
            BooleanField::new('presence')
                ->setHelp('Cochez si le sportif est présent à la séance')
                ->renderAsSwitch(true),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réservation')
            ->setEntityLabelInPlural('Réservations')
            ->setDefaultSort(['seance.dateHeure' => 'DESC'])
            ->setSearchFields(['sportif.nom', 'sportif.prenom', 'seance.dateHeure']);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::persistEntity($entityManager, $entityInstance);
        } catch (UniqueConstraintViolationException $e) {
            throw new \RuntimeException('Ce sportif est déjà inscrit à cette séance.');
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            parent::updateEntity($entityManager, $entityInstance);
        } catch (UniqueConstraintViolationException $e) {
            throw new \RuntimeException('Ce sportif est déjà inscrit à cette séance.');
        }
    }
}
