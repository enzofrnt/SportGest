<?php

namespace App\Form;

use App\Entity\FicheDePaie;
use App\Entity\Coach;
use App\Enum\PeriodePaie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class FicheDePaieGenerationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coach', EntityType::class, [
                'class' => Coach::class,
                'choice_label' => function(Coach $coach) {
                    return $coach->getNom() . ' ' . $coach->getPrenom();
                },
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.nom', 'ASC')
                        ->addOrderBy('c.prenom', 'ASC');
                },
                'label' => 'Coach',
                'placeholder' => 'Sélectionnez un coach',
                'required' => true,
            ])
            ->add('periode', EnumType::class, [
                'class' => PeriodePaie::class,
                'choice_label' => function (PeriodePaie $choice) {
                    return $choice->value;
                },
                'label' => 'Période',
                'required' => true,
                'placeholder' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FicheDePaie::class,
        ]);
    }
} 