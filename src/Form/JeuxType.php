<?php

namespace App\Form;

use App\Entity\Jeux;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JeuxType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du jeu',
                'required' => true,
            ])
            ->add('url', TextType::class, [
                'label' => 'URL (optionnel)',
                'required' => false,
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix',
                'required' => false,
                'empty_data' => '0', // Valeur par défaut si non remplie
            ])
            ->add('min_player', NumberType::class, [
                'label' => 'Nombre minimum de joueurs',
                'required' => false,
                'empty_data' => '1', // Valeur par défaut si non remplie
            ])
            ->add('max_player', NumberType::class, [
                'label' => 'Nombre maximum de joueurs',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Jeux::class,
        ]);
    }
}