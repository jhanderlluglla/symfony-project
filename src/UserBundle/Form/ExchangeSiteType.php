<?php

namespace UserBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Category;
use CoreBundle\Entity\User;
use UserBundle\Form\Filters\LanguageType;

/**
 * Class ExchangeSiteType
 *
 * @package UserBundle\Form
 */
class ExchangeSiteType extends CopywritingSiteType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('majesticTrustFlow', HiddenType::class)
            ->add('majesticRefDomains', HiddenType::class)
            ->add('alexaRank', HiddenType::class)
            ->add('age', HiddenType::class)
            ->add('maximumCredits', HiddenType::class)
            ->add('hideUrl', CheckboxType::class, [
                'required' => false,
                'label' => 'hide_url',
                'label_attr' => ['class' => 'checkbox-inline']
            ])
            ->add('trustedWebmaster', CheckboxType::class, [
                'required' => false,
                'label' => 'webmaster',
            ])
            ->add('credits', TextType::class, [
                'label' => 'credits',
                'required' => true,
                'attr' => [
                    'maxlength' => 10,
                    'pattern' => '\d*\.?\d*'
                ]
            ])
            ->add('minWordsNumber', IntegerType::class, [
                'label' => 'min_words_number',
                'required' => true,
                'attr' => [
                    'placeholder' => 'form.placeholder_number',
                    'maxlength' => 10
                ],
            ])
            ->add('maxLinksNumber', IntegerType::class, [
                'label' => 'max_links_number',
                'required' => true,
                'attr' => [
                    'placeholder' => 'form.placeholder_number',
                    'maxlength' => 10
                ],
            ])
            ->add('metaTitle', CheckboxType::class, [
                'required' => false,
                'label' => 'form.meta_title_label',
                'label_attr' => ['class' => 'checkbox-inline']
            ])
            ->add('metaDescription', CheckboxType::class, [
                'required' => false,
                'label' => 'form.meta_description_label',
                'label_attr' => ['class' => 'checkbox-inline']
            ])
            ->add('headerOneSet', CheckboxType::class, [
                'required' => false,
                'label' => 'form.header_one_set_label',
                'label_attr' => ['class' => 'checkbox-inline']
            ])
            ->add('headerTwoStart', IntegerType::class, [
                'required' => false,
                'label' => 'form.header_two_start_end',
                'attr' => [
                    'placeholder' => 'form.min',
                    'range-type' => "min",
                    'class' => 'form-control_small',
                    'max' => 999,
                ],
            ])
            ->add('headerTwoEnd', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.max',
                    'range-type' => "max",
                    'class' => 'form-control_small',
                    'max' => 999,
                ],
            ])
            ->add('headerThreeStart', IntegerType::class, [
                'required' => false,
                'label' => 'form.header_three_start_end',
                'attr' => [
                    'placeholder' => 'form.min',
                    'range-type' => "min",
                    'class' => 'form-control_small',
                    'max' => 999,
                ],
            ])
            ->add('headerThreeEnd', IntegerType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.max',
                    'range-type' => "max",
                    'class' => 'form-control_small',
                    'max' => 999,
                ],
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
                'attr' => [
                    'placeholder' => 'form.min'
                ],
                'placeholder' => false,
            ])
            ->add('minImagesNumber', IntegerType::class, [
                'required' => true,
                'label' => 'form.images_range',
                'attr' => [
                    'range-type' => "min",
                    'placeholder' => 'form.min',
                    'class' => 'form-control_small',
                    'max' => 999,
                ],
            ])
            ->add('maxImagesNumber', IntegerType::class, [
                'required' => true,
                'attr' => [
                    'range-type' => "max",
                    'class' => 'form-control_small m-l-md m-r-md',
                    'placeholder' => 'form.max',
                    'max' => 999,
                ],
            ])
            ->add('publicationRules', TextareaType::class, [
                'label' => 'other_rules',
                'required' => false,
                'attr' => [
                    'placeholder' => 'form.placeholder_rules',
                    'maxlength' => 1500
                ],
            ])
            ->add('categories', EntityType::class, array(
                'required' => true,
                'multiple'=> true,
                'class' => Category::class,
                'choice_label' => 'multiselectName',
                'query_builder' => function (EntityRepository $er) {
                    return $er
                        ->createQueryBuilder('c')
                        ->where('c.parent IS NOT NULL')
                        ->orderBy('c.lft', 'ASC');
                },
                'label' => 'categories',
            ))
            ->add('tags', TextType::class, array(
                'required' => true,
                'label' => 'tags',
                'attr' => ['class' => 'tagsinput']
            ))
            ->add('acceptEref', CheckboxType::class, array(
                'required' => false,
                'label' => 'accept_eref',
            ))
            ->add('acceptWeb', CheckboxType::class, array(
                'required' => false,
                'label' => 'accept_web',
            ))
            ->add('acceptSelf', CheckboxType::class, array(
                'required' => false,
                'label' => 'accept_self',
            ))
            ->add('language', LanguageType::class, [
                'label' => 'language',
            ])
            ->add('authorizedAnchor', ChoiceType::class, [
                'choices' => [
                    'authorized_anchor_unoptimized' => 'unoptimized',
                    'authorized_anchor_semioptimized' => 'semioptimized',
                    'authorized_anchor_optimized' => 'optimized',
                ],
                'label' => 'authorized_anchor',
            ])
            ->add('acceptCommission', CheckboxType::class, [
                'required' => true,
                'mapped' => false,
            ])
            ->add('nofollowLink', CheckboxType::class, array(
                'required' => false,
                'label' => 'nofollow_link',
            ))
            ->add('sponsorisedArticle', CheckboxType::class, array(
                'required' => false,
                'label' => 'sponsorised_article',
            ))
            ->add('additionalExternalLink', CheckboxType::class, array(
                'required' => false,
                'label' => 'additional_external_link',
            ))
            ->add('countAdditionalExternalLink', IntegerType::class, array(
                'required' => false,
                'label' => false,
            ))
            ->add('apiKey', TextType::class, [
                'required' => false,
                'attr' => array(
                    'readonly' => true,
                ),
            ])
        ;

        $builder->get('age')
            ->addModelTransformer(new CallbackTransformer(
                function ($age) {
                    if ($age instanceof \DateTime) {
                        return $age->format('Y-m-d');
                    }

                    return '';
                },
                function ($age) {
                    return new \DateTime($age);
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ExchangeSite::class,
            'translation_domain' => 'exchange_site',
            'user' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_exchange_site';
    }
}
