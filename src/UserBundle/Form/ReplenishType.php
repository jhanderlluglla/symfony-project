<?php

namespace UserBundle\Form;

use CoreBundle\Entity\ReplenishRequest;
use CoreBundle\Services\ReplenishAccountService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReplenishType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('amount', TextType::class,[
                'attr' => [
                    'placeholder' => 'amount_placeholder',
                    'class' => 'j-replenish_amount-input',
                    'pattern' => '\d*\.?\d*'
                ]
            ])
            ->add('requestType', ChoiceType::class,[
                'choices' => [
                    ReplenishRequest::PAYPAL_TYPE => ReplenishRequest::PAYPAL_TYPE,
                    ReplenishRequest::WIRE_TRANSFER_TYPE => ReplenishRequest::WIRE_TRANSFER_TYPE
                ],
                'choice_attr' => function($choiceValue, $key) {
                    return ['class' => 'j-replenish_'.strtolower($key)];
                },
                'data' => ReplenishRequest::PAYPAL_TYPE,
                'expanded' => true,
                'multiple' => false,
                'attr' => ['class' => 'j-replenish_requestType'],
            ])
            ->add('replenish_account', SubmitType::class, [
                'attr' => ['class' => 'btn btn-primary j-finish'],
                'label' => 'finish',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ReplenishRequest::class,
            'translation_domain' => 'replenish_account',
        ]);
    }
}
