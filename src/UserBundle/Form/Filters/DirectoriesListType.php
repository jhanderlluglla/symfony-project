<?php

namespace UserBundle\Form\Filters;

use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DirectoryType
 *
 * @package UserBundle\Form\Filters
 */
class DirectoriesListType extends ExchangeSiteType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('language', LanguageType::class, [
                'placeholder' => 'form.choose_language',
                'label' => 'form.language',
                'required' => false,
                'attr' => [
                    'class' => 'chosen-select'
                ],
                'empty_data' => true
            ])
            ->remove('filter')
            ->add('filter', ButtonType::class, [
                'label' => 'form.filter',
                'attr' => [
                    'class' => 'btn btn-primary btn-sm directory_list_filter',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'exchange_site_find',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'filters';
    }
}
