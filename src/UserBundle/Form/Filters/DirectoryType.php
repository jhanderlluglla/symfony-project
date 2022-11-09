<?php
namespace UserBundle\Form\Filters;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Class DirectoryType
 *
 * @package UserBundle\Form\Filters
 */
class DirectoryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('directoriesList', ChoiceType::class, [
                'required' => true,
                'label' => 'filter.directories_list.label',
                'choices' => $options['directories_list'],
                'mapped' => false,
            ])
            ->add('sorting', ChoiceType::class, [
                'required' => true,
                'label' => 'filter.sorting.label',
                'choices' => [
                    'filter.sorting.no_sorting' => '',
                    'filter.sorting.tariff_group.label' => [
                        'filter.sorting.tariff_group.asc' => 'tariff-asc',
                        'filter.sorting.tariff_group.desc' => 'tariff-desc',
                    ],
                    'filter.sorting.page_rank_group.label' => [
                        'filter.sorting.page_rank_group.asc' => 'page-rank-asc',
                        'filter.sorting.page_rank_group.desc' => 'page-rank-desc',
                    ],
                    'filter.sorting.age_group.label' => [
                        'filter.sorting.age_group.asc' => 'age-asc',
                        'filter.sorting.age_group.desc' => 'age-desc',
                    ],
                    'filter.sorting.trust_flow_group.label' => [
                        'filter.sorting.trust_flow_group.asc' => 'trust-flow-asc',
                        'filter.sorting.trust_flow_group.desc' => 'trust-flow-desc',
                    ],
                    'filter.sorting.domain_referrals_group.label' => [
                        'filter.sorting.domain_referrals_group.asc' => 'trust-flow-asc',
                        'filter.sorting.domain_referrals_group.desc' => 'trust-flow-desc',
                    ],
                    'filter.sorting.domain_referrals_group.label' => [
                        'filter.sorting.domain_referrals_group.asc' => 'trust-flow-asc',
                        'filter.sorting.domain_referrals_group.desc' => 'trust-flow-desc',
                    ],
                    'filter.sorting.validation_time_group.label' => [
                        'filter.sorting.validation_time_group.asc' => 'trust-flow-asc',
                        'filter.sorting.validation_time_group.desc' => 'trust-flow-desc',
                    ],
                    'filter.sorting.validation_rate_group.label' => [
                        'filter.sorting.validation_rate_group.asc' => 'trust-flow-asc',
                        'filter.sorting.validation_rate_group.desc' => 'trust-flow-desc',
                    ],
                ],
                'mapped' => false,
            ])
            ->add('tariffExtraWebmaster', TextType::class, [
                'required' => false,
                'label' => 'filter.tariff'
            ])
            ->add('tariffExtraWebmasterCondition', ChoiceType::class, [
                'choices' => [
                    '>=' => 'gte',
                    '<=' => 'lte',
                ],
                'label' => false,
                'placeholder' => false,
                'required' => false,
            ])
            ->add('trustFlow', TextType::class, [
                'required' => false,
                'label' => 'filter.trust_flow'
            ])
            ->add('trustFlowCondition', ChoiceType::class, [
                'choices' => [
                    '>=' => 'gte',
                    '<=' => 'lte',
                ],
                'label' => false,
                'placeholder' => false,
                'required' => false,
            ])
            ->add('age', TextType::class, [
                'required' => false,
                'label' => 'filter.age'
            ])
            ->add('ageCondition', ChoiceType::class, [
                'choices' => [
                    '>=' => 'gte',
                    '<=' => 'lte',
                ],
                'label' => false,
                'placeholder' => false,
                'required' => false,
            ])
            ->add('validationTime', TextType::class, [
                'required' => false,
                'label' => 'filter.validation_time'
            ])
            ->add('validationTimeCondition', ChoiceType::class, [
                'choices' => [
                    '>=' => 'gte',
                    '<=' => 'lte',
                ],
                'label' => false,
                'placeholder' => false,
                'required' => false,
            ])
            ->add('validationRate', TextType::class, [
                'required' => false,
                'label' => 'filter.validation_rate'
            ])
            ->add('validationRateCondition', ChoiceType::class, [
                'choices' => [
                    '>=' => 'gte',
                    '<=' => 'lte',
                ],
                'label' => false,
                'placeholder' => false,
                'required' => false,
            ])
            ->add('totalBacklink', TextType::class, [
                'required' => false,
                'label' => 'filter.total_backlink'
            ])
            ->add('totalBacklinkCondition', ChoiceType::class, [
                'choices' => [
                    '>=' => 'gte',
                    '<=' => 'lte',
                ],
                'label' => false,
                'placeholder' => false,
                'required' => false,
            ])
            ->add('acceptInnerPages', CheckboxType::class, [
                'label' => 'filter.accept_inner_pages',
                'required' => false,
            ])
            ->add('acceptLegalInfo', CheckboxType::class, [
                'label' => 'filter.accept_legal_info',
                'required' => false,
            ])
            ->add('minWordsCount', TextType::class, [
                'required' => false,
                'label' => 'filter.min_words_count'
            ])
            ->add('filter',  SubmitType::class, [
                'label' => 'filter.button',
                'attr' => ['class' => 'btn btn-success btn-sm']
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'exchange_site_find';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'directories_list',
            'directories_list' => [],
        ]);
    }
}