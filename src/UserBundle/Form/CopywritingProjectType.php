<?php

namespace UserBundle\Form;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Repository\ExchangeSiteRepository;
use CoreBundle\Services\ChooseWriterService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use CoreBundle\CoreBundle;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Repository\CopywritingProjectRepository;
use Symfony\Component\Validator\Constraints\Choice;
use UserBundle\Form\Filters\LanguageType;

/**
 * Class CopywritingProjectType
 *
 * @package UserBundle\Form
 */
class CopywritingProjectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('load_template', EntityType::class, [
                'label' => 'form.template_label',
                'class' => CopywritingProject::class,
                'query_builder' => function (CopywritingProjectRepository $er) use ($options) {
                    return $er->createQueryBuilder('p')
                        ->where('p.template = true')
                        ->andWhere('p.customer = '. $options['customer']);
                },
                'choice_label' => 'title',
                'placeholder' => 'form.template_placeholder',
                'mapped' => false,
                'required' => false
            ])
            ->add('title', TextType::class, [
                'label' => 'form.title',
                'attr' => [
                    'placeholder' => 'form.title_placeholder',
                    'maxlength' => CopywritingProject::MAX_TITLE_LENGTH
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.description',
                'attr' => [
                    'placeholder' => 'form.description_placeholder',
                    'maxlength' => CopywritingProject::MAX_DESCRIPTION_LENGTH
                ]
            ])
            ->add('language', LanguageType::class, [
                'label' => 'form.language'
            ])
            ->add('exchange_site', EntityType::class, [
                'class' => ExchangeSite::class,
                'query_builder' =>  function (ExchangeSiteRepository $er) use ($options) {
                    return $er->createQueryBuilder('es')
                        ->where('es.user = :user')
                        ->andWhere('es.siteType IN (:types)')
                        ->andWhere('es.pluginStatus = 1')
                        ->setParameter('types', [ExchangeSite::COPYWRITING_TYPE, ExchangeSite::UNIVERSAL_TYPE])
                        ->setParameter('user', $options['customer']);
                },
                'required' => false,
                'mapped' => false,
                'placeholder' => 'form.exchange_site_placeholder',
                'choice_attr' => function (ExchangeSite $val, $key, $index) {
                    return ['data-language' => $val->getLanguage()];
                },
                'label' => 'form.exchange_site'
            ])
            ->add('recurrent', HiddenType::class, [
                'label' => 'form.recurrent',
                'required' => false
            ])
            ->add('recurrent_period', IntegerType::class, [
                'label' => 'form.period',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.number_placeholder',
                    'max' => 365,
                ]
            ])
            ->add('recurrent_total', IntegerType::class, [
                'label' => 'form.recurrent_total',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.number_placeholder',
                    'max' => 9999999,
                ]
            ])
            ->add('writer_category', WriterCategoryType::class, [
                'label' => 'form.writer_category',
                'choice_attr' => $this->getAdditionalAttributes($options['category_price']),
            ])
            ->add(
                'orders',
                CollectionType::class,
                [
                    'label' => false,
                    'entry_type'    => CopywritingOrderType::class,
                    'allow_add'          => true,
                    'allow_delete'       => true,
                    'delete_empty'       => true,
                    'entry_options' => [
                        'label' => false,
                        'calculator_price_service' => $options['calculator_price_service'],
                    ],
                    'by_reference' => false,
                    'prototype_name' => '__parentId__',
                    'error_bubbling' => false,
                ]
            )
            ->add('template', CheckboxType::class, [
                'label' => 'form.template',
                'required' => false
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.confirm',
                'attr' => [
                    'class' => 'btn btn-primary j-submit',
                    'data-bind' => 'visible: articles().length',
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'CoreBundle\Entity\CopywritingProject',
            'translation_domain' => 'copywriting',
        ]);

        $resolver->setRequired(['customer', 'calculator_price_service', 'category_price']);
    }

    /**
     * @param $prices
     * @return array
     */
    protected function getAdditionalAttributes($prices)
    {
        $result = [];
        foreach (CopywritingProject::WRITER_CATEGORIES as $category) {
            $result[$category . '.title'] = [
                'icon' => 'icon_' . $category,
                'text' => $category . '.text',
                'cost' => $prices[$category],
                'data-bind' => 'checked: writerCategory, event: { change: categoryChanged }',
            ];
        }

        return $result;
    }
}
