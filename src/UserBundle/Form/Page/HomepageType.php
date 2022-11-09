<?php

namespace UserBundle\Form\Page;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UserBundle\Form\Page\Elements\ButtonBlockType;
use UserBundle\Form\Page\Elements\ContainerType;
use UserBundle\Form\Page\Elements\CustomButtonType;
use UserBundle\Form\Page\Elements\ListBlockType;
use UserBundle\Form\Page\Elements\TextBlockType;

class HomepageType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('language', ChoiceType::class, [
                'label' => 'form.language',
                'choices' => $options['locales'],
            ])
            ->add('topList', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true,
                'prototype' => true,
                'allow_delete' => true,
                'label' => 'form.top_list',
                'entry_options' => [
                    'label' => 'form.item',
                ],
            ])
            ->add('topButton', CustomButtonType::class, [
                'label' => 'form.top_button'
            ])
            ->add('blockContainer', ContainerType::class, ['label' => 'form.blocks'])
            ->add('textBlock', TextBlockType::class, ['label' => 'form.about_us'])
            ->add('listBlock', ListBlockType::class, ['label' => 'form.list_block'])
            ->add('submit', SubmitType::class, ['label' => 'form.submit'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'CoreBundle\Entity\Page\Homepage',
            'translation_domain' => 'pages',
            'locales' => [],
        ]);
    }
}