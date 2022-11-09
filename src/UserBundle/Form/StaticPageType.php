<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use CoreBundle\Entity\StaticPage;

class StaticPageType extends AbstractType
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
            ->add('pageContent', TextareaType::class, [
                'label' => 'page_content',
                'required' => false,
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
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StaticPage::class,
            'translation_domain' => 'static_page',
            'locales' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_static_page';
    }
}