<?php

namespace UserBundle\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use CoreBundle\Entity\User;


class ModifyBalanceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('balance', TextType::class, [
                'required' => true,
                'label' => 'form.balance',
            ])
            ->add('save',  SubmitType::class, [
                'label' => 'save',
                'attr' => ['class' => 'btn btn-primary btn-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'user',
            'validation_groups' => ['modify_balance'],
        ]);
    }
}