<?php

namespace UserBundle\Form\User;

use CoreBundle\Entity\Constant\Country;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use CoreBundle\Entity\User;

class RegistrationFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attributes = $builder->get('plainPassword')->getOptions();
        $attrs = $attributes['options']['attr'];
        $attrs['pattern'] = "^[^\s]+$";
        $attributes['options']['attr'] = $attrs;
        $builder->add('plainPassword', RepeatedType::class, $attributes);

        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.email',
                'translation_domain' => 'FOSUserBundle',
                'attr' => [
                    'minlength' => 3,
                    'maxlength' => 254
                ]
            ])
            ->add('fullName', TextType::class, [
                'required' => true,
                'label' => 'registration.full_name',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 75
                ]
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'registration.phone',
                'attr' => [
                    'pattern' => "^[-+.\d\s\(\)]{2,26}$",
                    'maxlength' => 26
                ]
            ])
            ->add('address', TextType::class, [
                'required' => true,
                'label' => 'registration.address',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 250
                ]
            ])
            ->add('zip', TextType::class, [
                'required' => false,
                'label' => 'registration.zip',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 50
                ]
            ])
            ->add('city', TextType::class, [
                'required' => true,
                'label' => 'registration.city',
                'attr' => [
                    'minlength' => 1,
                    'maxlength' => 75
                ]
            ])
            ->add('company', TextType::class, [
                'required' => false,
                'label' => 'registration.company',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 250,
                    'class' => 'user_company'
                ]
            ])
            ->add('webSite', TextType::class, [
                'required' => false,
                'label' => 'registration.web_site',
                'attr' => [
                    'minlength' => 2,
                    'maxlength' => 250
                ]
            ])
            ->add('country', CountryType::class, [
                'required' => true,
                'placeholder' => 'registration.country_placeholder',
                'label' => 'registration.country',
                'attr' => [
                  'class' => 'user_country'
                ],
                'choice_attr' => function ($choiceValue) {
                    return ['data-european-country' => Country::isEuropeanCountryExceptFrance($choiceValue) ? 1 : 0];
                },
            ])
            ->add('vatNumber', TextType::class, [
                'label' => 'registration.vat_number',
                'required' => false,
                'attr' => [
                  'class' => 'user_vat-number'
                ]
            ])
            ->add('conditions', CheckboxType::class, [
                'required' => true,
                'label' => 'registration.conditions',
                'mapped' => false,
            ])
            ->remove('username')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'user',
        ]);
    }
}
