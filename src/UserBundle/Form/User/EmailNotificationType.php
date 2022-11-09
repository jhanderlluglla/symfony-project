<?php

namespace UserBundle\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use CoreBundle\Entity\User;

/**
 * Class SettingsType
 *
 * @package UserBundle\Form\User
 */
class EmailNotificationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['fieldOptions'] as $field) {
            $builder
                ->add($field['name'], CheckboxType::class, [
                    'mapped' => false,
                    'required' => $field['required'],
                    'label' => $field['label'],
                    'attr' => $field['attr']
                ]);
        }

        $builder
            ->add('save',  SubmitType::class, [
                'label' => 'save',
                'attr' => ['class' => 'btn btn-primary pull-right m-t-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'user',
            'fieldOptions' => [],
            'validation_groups' => array(User::VALIDATION_GROUP_UPDATE_NOTIFICATION),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'user_profile_email_notification';
    }
}
