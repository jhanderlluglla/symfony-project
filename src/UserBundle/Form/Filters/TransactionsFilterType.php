<?php

namespace UserBundle\Form\Filters;

use CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionsFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add('user_id', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->innerJoin('u.transactions', 't')
                        ->groupBy('u.id');
                },
                'placeholder' => 'filter.all_users',
                'label' => 'filter.user_name',
                'required' => false,
            ])
        ;
    }

    public function getBlockPrefix()
    {
        return 'transaction_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'translation_domain' => 'transaction',
            'csrf_protection'   => false,
            'validation_groups' => array(
                User::class,
                'filter',
            ),
        ));
    }
}
