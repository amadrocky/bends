<?php

namespace App\Form;

use App\Entity\Offers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre de l\'annonce'])
            ->add('description' ,TextareaType::class, ['label' => 'Description'])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => [
                    'Ameublement' => 'Ameublement',
                    'Electroménager' => 'Electroménager',
                    'Aliments' => 'Aliments',
                    'Vêtements' => 'Vêtements',
                    'Vêtements bébé' => 'Vêtements bébé',
                    'Équipements bébé' => 'Équipements bébé',
                    'Linge de maison' => 'Linge de maison',
                    'Chaussures' => 'Chaussures',
                    'Informatique' => 'Informatique',
                    'Image & son' => 'Image & son',
                    'DVD / Films' => 'DVD / Films',
                    'CD / Musique' => 'CD / Musique',
                    'Jeux & jouets' => 'Jeux & jouets',
                    'Téléphonie' => 'Téléphonie',
                    'Bricolage' => 'Bricolage',
                    'Jardin' => 'Jardin',
                    'Livres' => 'Livres',
                    'Animaux' => 'Animaux',
                    'Vélos' => 'Vélos',
                    'Fournitures de bureau' => 'Fournitures de bureau',
                    'Préstations de services' => 'Préstations de services',
                    'Matériel médical' => 'Matériel médical',
                    'Autre' => 'Autre'
                ]
            ])
            //->add('pictures')
            /*->add('createdBy')
            ->add('createdAt')
            ->add('workflowState')*/
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Offers::class,
        ]);
    }
}
