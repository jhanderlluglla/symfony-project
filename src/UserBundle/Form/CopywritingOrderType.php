<?php

namespace UserBundle\Form;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Services\CalculatorPriceService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use CoreBundle\Entity\CopywritingKeyword;
use UserBundle\Form\Filters\NumberRangeType;
use UserBundle\Form\Transformer\KeywordsTransformer;

/**
 * Class CopywritingOrderType
 *
 * @package UserBundle\Form
 */
class CopywritingOrderType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'label' => 'form.title_text',
                'attr' => [
                    "data-bind" => "value: title, valueUpdate: ['afterkeydown', 'input']",
                    'maxlength' => 250
                ]
            ])
            ->add('wordsNumber', IntegerType::class, [
                'required' => true,
                'label' => 'form.number_words',
                'attr' => [
                    "data-bind" => "value: wordsNumber",
                    'min' => 100,
                    'max' => 9999999
                ]
            ])
            ->add('instructions', TextareaType::class, [
                'required' => false,
                'label' => 'form.instructions',
                'attr' => [
                    "data-bind" => "value: instructions, valueUpdate: ['afterkeydown', 'input']",
                    'maxlength' => 2500
                ]
            ])
            ->add('metaTitle', CheckboxType::class, [
                'required' => false,
                'label' => 'form.meta_title',
                'attr' => [
                    "data-bind" => "checked: metaTitle"
                ]
            ])
            ->add('metaDescription', CheckboxType::class, [
                'required' => false,
                'label' => 'form.meta_description',
                'attr' => [
                    "data-bind" => "checked: metaDescription"
                ]
            ])
            ->add('headerOneSet', CheckboxType::class, [
                'required' => false,
                'label' => 'form.header_one_set',
                'attr' => [
                    "data-bind" => "checked: headerOneSet"
                ]
            ])
            ->add('headerTwoStart', IntegerType::class, [
                'required' => false,
                'label' => 'form.heaer_two_start_end',
                'attr' => [
                    'class' => 'form-control_small',
                    'placeholder' => 'form.min',
                    'range-type' => "min",
//                    "data-bind" => "value: headerTwoStart, valueUpdate: ['afterkeydown', 'input']",
                    'max' => 999,
                ]
            ])
            ->add('headerTwoEnd', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control_small',
                    'placeholder' => 'form.max',
                    'range-type' => "max",
//                    "data-bind" => "value: headerTwoEnd, valueUpdate: ['afterkeydown', 'input']",
                    'max' => 999,
                ]
            ])
            ->add('headerThreeStart', IntegerType::class, [
                'required' => false,
                'label' => 'form.heaer_three_start_end',
                'attr' => [
                    'class' => 'form-control_small',
                    'placeholder' => 'form.min',
                    'range-type' => "min",
                    "data-bind" => "value: headerThreeStart, valueUpdate: ['afterkeydown', 'input']",
                    'max' => 999,
                ]
            ])
            ->add('headerThreeEnd', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control_small',
                    'placeholder' => 'form.max',
                    'range-type' => "max",
                    "data-bind" => "value: headerThreeEnd, valueUpdate: ['afterkeydown', 'input']",
                    'max' => 999,
                ]
            ])
            ->add('boldText', ChoiceType::class, [
                'choices' => ['yes' => true, 'no' => false, 'optional' => null],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'label' => 'form.bold_text',
                'label_attr' => [
                    "class" => "radio-inline"
                ],
                'placeholder' => false,
            ])
            ->add('italicText', ChoiceType::class, [
                'choices' => ['yes' => true, 'no' => false, 'optional' => null],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'label' => 'form.italic_text',
                'label_attr' => [
                    "class" => "radio-inline"
                ],
                'placeholder' => false,
            ])
            ->add('quotedText', ChoiceType::class, [
                'choices' => ['yes' => true, 'no' => false, 'optional' => null],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'label' => 'form.quoted_text',
                'label_attr' => [
                    "class" => "radio-inline"
                ],
                'placeholder' => false,
            ])
            ->add('ulTag', ChoiceType::class, [
                'choices' => ['yes' => true, 'no' => false, 'optional' => null],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'label' => 'form.ul_tag',
                'label_attr' => [
                    "class" => "radio-inline"
                ],
                'placeholder' => false,
            ])
            ->add('keywords', TextType::class, [
                'required' => false,
                'label' => 'form.keywords',
                'attr' => ['class' => 'tagsinput']
            ])
            ->add('keywordsPerArticleFrom', IntegerType::class, [
                'required' => false,
                'label' => 'form.keywords_per_article',
                'attr' => [
                    'max' => '999',
                    'step' => 1,
                    'range-type' => "min",
                    'class' => 'form-control_small',
                    'placeholder' => 'form.min',
                    "data-bind" => "value: keywordsPerArticleFrom, valueUpdate: ['afterkeydown', 'input']"
                ]
            ])
            ->add('keywordsPerArticleTo', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'max' => '999',
                    'step' => 1,
                    'range-type' => "max",
                    'class' => 'form-control_small',
                    'placeholder' => 'form.max',
                    "data-bind" => "value: keywordsPerArticleTo, valueUpdate: ['afterkeydown', 'input']"
                ]
            ])
            ->add('keywordInMetaTitle', CheckboxType::class, [
                'required' => false,
                'label' => 'form.keyword_meta_title',
                'attr' => [
                    "data-bind" => "checked: keywordInMetaTitle"
                ]
            ])
            ->add('keywordInHeaderOne', CheckboxType::class, [
                'required' => false,
                'label' => 'form.keyword_header_one',
                'attr' => [
                    "data-bind" => "checked: keywordInHeaderOne"
                ]
            ])
            ->add('keywordInHeaderTwo', CheckboxType::class, [
                'required' => false,
                'label' => 'form.keyword_header_two',
                'attr' => [
                    "data-bind" => "checked: keywordInHeaderTwo"
                ]
            ])
            ->add('keywordInHeaderThree', CheckboxType::class, [
                'required' => false,
                'label' => 'form.keyword_header_three',
                'attr' => [
                    "data-bind" => "checked: keywordInHeaderThree"
                ]
            ])
            ->add(
                'images',
                CollectionType::class,
                [
                    'entry_type'    => CopywritingImageType::class,
                    'allow_add'     => true,
                    'allow_delete'  => true,
                    'delete_empty'  => true,
                    'entry_options' => [
                        'label' => false,
                    ],
                    'by_reference' => false,
                    'prototype_name' => '__nestedId__',
                ]
            )
            ->add('imagesPerArticleFrom', IntegerType::class, [
                'required' => false,
                'label' => 'form.images_range',
                'attr' => [
                    'max' => '999',
                    'step' => 1,
                    'range-type' => "min",
                    'class' => 'form-control_small',
                    'placeholder' => 'form.min',
                    "data-bind" => "value: imagesPerArticleFrom, valueUpdate: ['afterkeydown', 'input']"
                ]
            ])
            ->add('imagesPerArticleTo', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'max' => '999',
                    'step' => 1,
                    'range-type' => "max",
                    'class' => 'form-control_small',
                    'placeholder' => 'form.max',
                    "data-bind" => "value: imagesPerArticleTo, valueUpdate: ['afterkeydown', 'input']"
                ]
            ])
            ->add('express', CheckboxType::class, [
                'required' => false,
                'label' => 'form.express',
                'attr' => [
                    "data-bind" => "checked: express"
                ]
            ]);

        $builder->get('keywords')
            ->addModelTransformer(new KeywordsTransformer())
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use($options) {
            /** @var CopywritingOrder $order */
            $order = $event->getData();

            /** @var CalculatorPriceService $calculatorPriceService */
            $calculatorPriceService = $options['calculator_price_service'];

            $basePrice = $calculatorPriceService->getBasePrice($order->getWordsNumber(), CalculatorPriceService::TOTAL_KEY);
            $imagesPrice = $calculatorPriceService->getImagesPrice($order->getImagesPerArticleTo(), CalculatorPriceService::TOTAL_KEY);
            $metaDescriptionPrice = $calculatorPriceService->getMetaDescriptionPrice($order->isMetaDescription(), CalculatorPriceService::TOTAL_KEY);

            $expressPrice = 0;
            if($order->isExpress()) {
                $expressPrice = $calculatorPriceService->getExpressPrice($order->getWordsNumber(), CalculatorPriceService::TOTAL_KEY);
                $writerExpressPrice = $calculatorPriceService->getExpressPrice($order->getWordsNumber(), CalculatorPriceService::WRITER_KEY);
                $order->setExpressBonus($expressPrice);
                $order->setWriterExpressBonus($writerExpressPrice);
            }

            $amount = $basePrice + $imagesPrice + $expressPrice + $metaDescriptionPrice;
            $order->setAmount(round($amount, 2));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CoreBundle\Entity\CopywritingOrder',
            'translation_domain' => 'copywriting',
        ));

        $resolver->setRequired(['calculator_price_service']);
    }
}
