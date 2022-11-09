<?php

namespace UserBundle\Form\Netlinking;

use CoreBundle\Repository\DirectoriesListRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\User;
use UserBundle\Form\Filters\LanguageType;

/**
 * Class AddFirstStepType
 *
 * @package UserBundle\Form\Netlinking
 */
class AddFirstStepType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['user'];

        $builder
            ->add('directoryList', EntityType::class, array(
                'required' => true,
                'class' => DirectoriesList::class,
                'choice_label' => 'name',
                'placeholder' => 'form.directory_list',
                'query_builder' => function (DirectoriesListRepository $er) use ($user) {
                    return $er->getNotEmptyDirectoriesList(['user' => $user]);
                },
                'choice_attr' => function ($val, $key, $index) {

                    return ['data-words-count' => $val->getWordsCount()];
                },
                'label' => '',
                'attr' => [
                    'class' => 'chosen-select'
                ]
            ))
            ->add('urls', CollectionType::class, array(
                'entry_type' => NetlinkingUrlsType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
                'entry_options' => [
                    'label' => false,
                    'attr' => [
                        'class' => 'netlinking_url__item',
                    ],
                ],
                'prototype_name' => '__parentId__',
                'error_bubbling' => false
            ))
            ->add('frequencyDirectory', TextType::class, [
                'label' => 'form.frequency_tasks',
                'required' => true,
                'attr' => [
                    'maxlength' => 3,
                    'pattern' => '\d*',
                    'class' => 'form-control_small',
                    'size' => 4

                ],
            ])
            ->add('frequencyDay', TextType::class, [
                'label' => 'form.frequency_day',
                'required' => true,
                'attr' => [
                    'maxlength' => 3,
                    'pattern' => '\d*',
                    'class' => 'form-control_small',
                    'size' => 4

                ],
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'form.comment',
                'required' => false,
                'attr' => [
                    'maxlength' => 1500,
                ],
            ])
            ->add('save',  SubmitType::class, [
                'label' => 'form.next',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'netlinking',
            'user' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netlinking_add_first_step';
    }
}