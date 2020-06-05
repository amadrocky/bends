<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('pseudonym', TextType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Nom (ou pseudo)'
                ]
            ])
            ->add('text', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Votre message ici ...'
                ]
            ])
            ->add('createdBy', EmailType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'Email'
                ]
            ])
            // ->add('createdAt')
            // ->add('workflowState')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}
