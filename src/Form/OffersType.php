<?php

namespace App\Form;

use App\Entity\Categories;
use App\Entity\Offers;
use App\Entity\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => Categories::class,
                'choice_label' => 'name',
                'placeholder' => 'Séléctionnez une catégorie'
            ])
            ->add('type', EntityType::class, [
                'label' => 'Type',
                'class' => Type::class,
                'choice_label' => 'name',
                'placeholder' => 'Séléctionnez un type'
            ])
            ->add('title', TextType::class, ['label' => 'Titre de l\'annonce'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('createdBy', EmailType::class, ['label' => 'Email'])
            ->add('phoneNumber', IntegerType::class, ['label' => 'Téléphone'])
            ->add('phoneVisible', CheckboxType::class, [
                'label' => 'Masquer le numéro de téléphone dans l\'annonce',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Offers::class,
        ]);
    }
}
