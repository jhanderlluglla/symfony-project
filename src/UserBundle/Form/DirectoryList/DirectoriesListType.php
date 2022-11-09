<?php

namespace UserBundle\Form\DirectoryList;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use CoreBundle\Entity\DirectoriesList;

/**
 * Class DirectoriesListType
 *
 * @package UserBundle\Form\DirectoryList
 */
class DirectoriesListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'name',
                'required' => true,
                'attr' => [
                    'maxlength' => 250,
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'save',
                'attr' => ['class' => 'btn btn-primary btn-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DirectoriesList::class,
            'translation_domain' => 'directories_list',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_directories_list';
    }
}
