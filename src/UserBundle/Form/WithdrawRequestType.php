<?php

namespace UserBundle\Form;

use CoreBundle\Entity\WithdrawRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithdrawRequestType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('withdrawAmount', TextType::class, [
                'label' => 'withdraw.amount',
                'attr' => [
                    'placeholder' => 'withdraw.amount_placeholder']
            ])
            ->add('invoice', FileType::class, [
                'label' => 'upload_invoice'
            ])
            ->add('paypal', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'withdraw.placeholder']
            ])
            ->add('swift', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'withdraw.placeholder']
            ])
            ->add('iban', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'withdraw.placeholder']
            ])
            ->add('confirm', CheckboxType::class, [
                'label' => 'confirm_label',
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'withdraw.placeholder',
                    'class' => 'j-withdraw-confirm']
            ])
            ->add('send', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-primary j-send-btn',
                    'disabled' => 'true']
                ])
            ->add('companyName', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'withdraw.placeholder']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WithdrawRequest::class,
            'translation_domain' => 'withdraw',
        ]);
    }
}