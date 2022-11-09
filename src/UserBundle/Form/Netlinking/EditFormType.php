<?php

namespace UserBundle\Form\Netlinking;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\User;

/**
 * Class EditFirstStepType
 *
 * @package UserBundle\Form\Netlinking
 */
class EditFormType extends AddFirstStepType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('directoryList');
        $builder->remove('frequencyDirectory');
        $builder->remove('frequencyDay');
        $builder
            ->add('urlAnchors', CollectionType::class, array(
                'entry_type' => NetlinkingUrlAnchors::class,
                'allow_add' => true,
                'allow_delete' => false,
                'by_reference' => false,
                'label' => false,
                'entry_options' => [
                    'label' => false,
                    'status' => $options['status'],
                ],
                'prototype_name' => '__parentId__',
                'error_bubbling' => false
            ))
            ->add('save', SubmitType::class, [
                'label' => 'form.save',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'netlinking',
            'user' => null,
            'status' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netlinking_edit_first_step';
    }
}