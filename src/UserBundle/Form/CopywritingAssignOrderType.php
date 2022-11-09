<?php

namespace UserBundle\Form;

use CoreBundle\Entity\User;
use CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CopywritingAssignOrderType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('copywriter', EntityType::class, [
                'class' => User::class,
                'attr' => [
                    'class' => 'js_assign_writer_form'
                ],
                'query_builder' => function (UserRepository $er) {
                    return $er->filter(['roles' => [User::ROLE_WRITER, User::ROLE_WRITER_COPYWRITING]])->andWhere('u.enabled = 1');
                },
                'choice_attr' => function (User $choiceValue, $key, $value) {
                    return ['data-language' => $choiceValue->getWorkLanguage()];
                },
                'label' => false,
                'placeholder' => 'choose_option',
                'translation_domain' => 'copywriting',
                ]);
    }
}
