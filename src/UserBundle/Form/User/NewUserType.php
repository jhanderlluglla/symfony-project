<?php

namespace UserBundle\Form\User;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\OptionsResolver\OptionsResolver;
use UserBundle\Form\Filters\LanguageType;

/**
 * Class NewUserType
 *
 * @package UserBundle\Form\User
 */
class NewUserType extends RegistrationFormType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('workLanguage', LanguageType::class, ['label' => 'registration.work_language'])
            ->add('roles', ChoiceType::class, [
                'required' => true,
                'label' => 'form.role',
                'choices' => $options['role_choices'],
                'attr' => ['id' => 'form_user_role']
            ])
            ->add('permission', UserPermissionType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->remove('conditions')
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
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'new_user';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'role_choices' => [],
        ]);
    }
}
