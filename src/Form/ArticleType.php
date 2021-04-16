<?php

namespace App\Form;

use App\Entity\Articles;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('title', TextType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => 'Titre de l\'article'
            ]
        ])
        ->add('description', TextareaType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => 'Description'
            ]
        ])
        ->add('link', TextType::class, [
            'label' => false,
            'attr' => [
                'placeholder' => 'Lien (format: http://www.exemple.com)'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Articles::class,
        ]);
    }
}
