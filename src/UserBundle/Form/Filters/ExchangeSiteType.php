<?php
namespace UserBundle\Form\Filters;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use CoreBundle\Entity\Category;

/**
 * Class ExchangeSiteType
 *
 * @package UserBundle\Form\Filters
 */
class ExchangeSiteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tag', TextType::class, [
                'required' => false,
                'label' => 'form.search_tag'
            ])
            ->add('category', EntityType::class, [
                'placeholder' => 'form.choose_category',
                'class' => Category::class,
                'choice_label' => 'multiselectName',
                'query_builder' => function (EntityRepository $er) {
                    return $er
                        ->createQueryBuilder('c')
                        ->where('c.parent IS NOT NULL')
                        ->orderBy('c.lft', 'ASC');
                },
                'label' => 'form.categories',
                'attr' => [
                    'class' => 'chosen-select'
                ],
                'required' => false,
            ])
            ->add('price', NumberRangeType::class, [
                'label' => 'form.price'
            ])
            ->add('language', LanguageType::class, [
                'label' => 'form.language',
                'required' => false,
            ])
            ->add('googleNews', CheckboxType::class, [
                'label' => 'form.google_news',
                'required' => false,
            ])
            ->add('googleAnalytics', CheckboxType::class, [
                'label' => 'form.google_analytics',
                'required' => false,
            ])
            ->add('mozPageAuthority', NumberRangeType::class, [
                'label' => 'form.page_authority',
                'required' => false
            ])
            ->add('mozDomainAuthority', NumberRangeType::class, [
                'label' => 'form.domain_authority',
                'required' => false
            ])
            ->add('authorizedAnchor', ChoiceType::class, [
                'choices' => [
                    'form.authorized_anchor_unoptimized' => 'unoptimized',
                    'form.authorized_anchor_semioptimized' => 'semioptimized',
                    'form.authorized_anchor_optimized' => 'optimized',
                ],
                'expanded' => true,
                'multiple' => true,
                'label' => 'form.authorized_anchor',
                'required' => false,
            ])
            ->add('plugin', CheckboxType::class, [
                'label' => 'form.plugin',
                'required' => false,
            ])
            ->add('ageCondition', ChoiceType::class, [
                'choices' => [
                    '>=' => 'gte',
                    '<=' => 'lte',
                ],
                'label' => 'form.age_type',
                'placeholder' => false,
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => 'gte'
            ])
            ->add('ageYears', IntegerType::class, [
                'label' => 'form.age_years',
                'required' => false
            ])
            ->add('ageMonth', IntegerType::class, [
                'label' => 'form.age_month',
                'required' => false
            ])
            ->add('hideUrl', CheckboxType::class, [
                'label' => 'form.hidden_url',
                'required' => false,
            ])

            //Majestic
            ->add('majesticTrustFlow', NumberRangeType::class, [
                'label' => 'form.trust_flow'
            ])
            ->add('majesticCitation', NumberRangeType::class, [
                'label' => 'form.citation_flow',
                'required' => false
            ])
            ->add('majesticTrustCitationRatio', NumberRangeType::class, [
                'label' => 'form.trust_citation_ration',
                'required' => false
            ])
            ->add('majesticTtfCategories', CollectionType::class, [
                    'label' => 'form.topical_trust_flow',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'attr' => [
                        'class' => 'ttf-categories-collection',
                    ],
                    'entry_type' => TtfCategoryType::class,
                    'entry_options' => ['label' => false],
                    'required' => false
            ])
            ->add('majesticRefDomains', NumberRangeType::class, [
                'label' => 'form.referring_domains',
                'required' => false
            ])
            ->add('majesticBacklinks', NumberRangeType::class, [
                'label' => 'form.backlinks',
                'required' => false
            ])
            ->add('majesticEduBacklinks', NumberRangeType::class, [
                'label' => 'form.edu_backlinks',
                'required' => false
            ])
            ->add('majesticGovBacklinks', NumberRangeType::class, [
                'label' => 'form.gov_backlinks',
                'required' => false
            ])

            //Semrush
            ->add('semrushTraffic', NumberRangeType::class, [
                'label' => 'form.semrush_traffic',
                'required' => false
            ])
            ->add('semrushKeyword', NumberRangeType::class, [
                'label' => 'form.semrush_keyword',
                'required' => false
            ])
            ->add('semrushTrafficCost', NumberRangeType::class, [
                'label' => 'form.semrush_traffic_cost',
                'required' => false
            ])
            ->add('filter', SubmitType::class, [
                'label' => 'form.filter',
                'attr' => ['class' => 'btn btn-success btn-sm']
            ])
            ->add('site', ChoiceType::class, [
                'choices' => [
                    'Archive.org' => 'archiveAge',
                    'Whois' => 'bwaAge',
                    'Both' => 'both'
                ],
                'choices_as_values' => true,
                'data' => 'archiveAge',
                'multiple'=>false,
                'expanded'=>true
            ])
            ->add('wordsCount', NumberRangeType::class, [
                'required' => false,
                'label' => 'form.words_count',
                'attr' => [
                    'class' => 'directory_list_min_words',
                ]
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'filters';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'exchange_site_find'
        ]);
    }
}
