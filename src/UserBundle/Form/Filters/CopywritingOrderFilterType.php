<?php

namespace UserBundle\Form\Filters;

use CoreBundle\Entity\CopywritingKeyword;
use CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CopywritingOrderFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('copywriter', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.enabled = 1')
                        ->andWhere('u.roles LIKE :role_writer')
                        ->orWhere('u.roles LIKE :role_writer_copywriting')
                        ->andWhere('u.roles NOT LIKE :role_writer_netlinking')
                        ->setParameter('role_writer', '%'.User::ROLE_WRITER.'%')
                        ->setParameter('role_writer_netlinking', '%'.User::ROLE_WRITER_NETLINKING.'%')
                        ->setParameter('role_writer_copywriting', '%'.User::ROLE_WRITER_COPYWRITING.'%');
                },
                'placeholder' => 'filter.copywriter',
            ])
            ->add('rating', ChoiceType::class, [
                'choices' => [
                    'like' => 1,
                    'dislike' => 0,
                ],
                'label' => 'filter.rating.label',
                'placeholder' => 'filter.rating',
            ])
            ->add('keyword', TextType::class, [
                'label' => 'filter.keyword.label',
            ])
            ->add('keyword_title', CheckboxType::class, [
                'label' => 'filter.keyword_title.label',
            ])
            ->add('keyword_description', CheckboxType::class, [
                'label' => 'filter.keyword_description.label',
            ])
            ->add('keyword_content', CheckboxType::class, [
                'label' => 'filter.keyword_content.label',
            ]);
    }

    public function getBlockPrefix()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'required' => false,
            'allow_extra_fields' => true,
            'translation_domain' => 'copywriting',
            'validation_groups' => array(
                User::class,
                'filter',
            ),
        ));
    }
}
