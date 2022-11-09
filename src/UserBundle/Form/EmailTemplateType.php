<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use CoreBundle\Entity\EmailTemplates;

class EmailTemplateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'name',
                'required' => true,
            ])
            ->add('identificator', TextType::class, [
                'label' => 'identificator',
                'required' => true,
            ])
            ->add('subject', TextType::class, [
                'label' => 'subject',
                'required' => true,
            ])
            ->add('emailContent', TextareaType::class, [
                'label' => 'email_content',
                'required' => true,
                'attr' => [
                    'class' => 'tinymce',
                ]
            ])
            ->add('language', ChoiceType::class, [
                'label' => 'language',
                'required' => true,
                'choices' => $options['locales']
            ])
            ->add('save',  SubmitType::class, [
                'label' => 'save',
                'attr' => ['class' => 'btn btn-primary btn-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EmailTemplates::class,
            'translation_domain' => 'email_template',
            'locales' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_email_template';
    }
}