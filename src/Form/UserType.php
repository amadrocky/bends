<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lastname', TextType::class, ['label' => 'Nom'])
            ->add('firstname', TextType::class, ['label' => 'Prénom'])
            ->add('email', EmailType::class, ['label' => 'Adresse email'])
            ->add('gender', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Homme' => 'M',
                    'Femme' => 'F'
                ]
            ])
            ->add('dateOfBirth', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'html5' => true
            ])
            //->add('roles')
            ->add('password', RepeatedType::class, array(
                'type' => PasswordType::class,
                'invalid_message' => 'Le mot de passe doit être similaire.',
                'required' => true,
                'first_options' => array('label' => 'Mot de passe'),
                'second_options' => array('label' => 'Répétez le mot de passe'),
            ))
            //->add('createdAt')
            //->add('workflowState')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
