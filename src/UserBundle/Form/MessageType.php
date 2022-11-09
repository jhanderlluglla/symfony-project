<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

use CoreBundle\Entity\Message;

/**
 * Class MessageType
 *
 * @package UserBundle\Form
 */
class MessageType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('recipient', ChoiceType::class, [
                'required' => true,
                'label' => 'to',
                'choices' => $options['recipient'],
                'mapped' => false,
            ])
            ->add('subject', TextType::class, [
                'required' => true,
                'label' => 'subject',
                'attr' => [
                    'minlength' => '2',
                    'maxlength' => '80',
                ]
            ])
            ->add('content', TextareaType::class, [
                'required' => true,
                'label' => 'content',
                'attr' => [
                    'class' => 'mail-text h-200',
                    'minlength' => '5',
                    'maxlength' => '5000',
                ]
            ])
            ->add('sendMessage', CheckboxType::class, [
                'required' => false,
                'mapped' => false,
                'label' => 'real_message',
            ])
            ->add('save',  SubmitType::class, [
                'label' => 'send',
                'attr' => [
                    'class' => 'btn btn-sm btn-primary'
                ]
            ])
            ->add('discard', ResetType::class, [
                'label' => 'discard.text',
                'attr' => array('class' => 'btn btn-sm btn-danger'),
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'translation_domain' => 'message',
            'recipient' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'message';
    }
}