<?php

namespace UserBundle\Form\User;
use CoreBundle\Entity\Constant\Country;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use CoreBundle\Entity\User;

/**
 * Class ProfileFormType
 *
 * @package UserBundle\Form\User
 */
class ProfileFormType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
                'label' => 'registration.full_name',
                'required' => true,
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
                'required' => false,
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
                'required' => false,
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
            ->remove('current_password')
            ->remove('username')
        ;

        if(isset($options['user']) && ($options['user']->isWriterAdmin() || $options['user']->isWriter())){
            $builder->add('wordsPerDay', TextType::class, [
                'required' => false,
                'label' => 'registration.words_per_day',
                'attr' => [
                    'pattern' => "^[0-9]{1,10}$",
                    'maxlength' => 10
                ]
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\ProfileFormType';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'user',
            'user' => null,
            'role_choices' => [],
        ]);
    }
}
