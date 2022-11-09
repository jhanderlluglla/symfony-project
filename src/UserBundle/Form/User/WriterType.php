<?php

namespace UserBundle\Form\User;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\CallbackTransformer;

use CoreBundle\Entity\User;
use UserBundle\Form\Filters\LanguageType;

/**
 * Class WriterType
 *
 * @package UserBundle\Form\User
 */
class WriterType extends AdminFieldsType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm( $builder, $options);

        $builder
            ->add('spending', MoneyType::class, [
                'label' => 'form.spending',
                'grouping' => true,
                'required' => false,
                'currency' => 'EUR',
                'attr' => [
                    'help' => $options['spending_helper'],
                    'pattern' => "^[0-9]{1,8}(\.[0-9]{1,2})?$",
                    'maxlength' => 11
                ],
            ])
//            ->add('trusted', CheckboxType::class, [
//                'required' => false,
//                'label' => 'form.trusted',
//            ])
//            ->add('projectHiddenEditor', CheckboxType::class, [
//                'required' => false,
//                'label' => 'form.project_hidden_editor',
//            ])
//            ->add('bonusProjects', CheckboxType::class, [
//                'required' => false,
//                'label' => 'form.bonus_projects',
//            ])
            ->add('copyWriterRate', MoneyType::class, [
                'label' => 'form.copywriting_rate',
                'currency' => 'EUR',
                'grouping' => true,
                'required' => false,
                'attr' => [
                    'pattern' => "^[0-9]{1,8}(\.[0-9]{1,2})?$",
                    'maxlength' => 11
                ]
            ])

            ->add('roles', ChoiceType::class, [
                'required' => true,
                'label' => 'form.role',
                'choices' => $options['role_choices'],
            ])
            ->add('workLanguage', LanguageType::class, ['label' => 'registration.work_language'])
            ->add('permission', UserPermissionType::class, [
                'mapped' => false,
                'required' => false,
            ])
        ;

        $builder->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($roles) {
                    return $roles[0];
                },
                function ($roles) {
                    return [$roles];
                }
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'user',
            'spending_helper' => '',
            'affiliation_tariff_helper' => '',
            'role_choices' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'edit_writer';
    }
}