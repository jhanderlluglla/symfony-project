<?php

namespace UserBundle\Form\DirectoryList;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;

/**
 * Class DirectoriesListRelationType
 *
 * @package UserBundle\Form\DirectoryList
 */
class DirectoriesListRelationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('wordsCount', HiddenType::class, [
                'required' => false,
            ])
            ->add('filter', HiddenType::class, [
                'required' => false,
            ])
            ->add('directories', EntityType::class, array(
                'class' => Directory::class,
                'required' => false,
                'choice_label' => 'name',
                'placeholder' => false,
                'label' => 'form.directories',
                'multiple' => true,
                'attr' => [
                ],
                'query_builder' => function (EntityRepository $er) use ($options) {
                    if (empty($options['filters'])) {
                        return null;
                    }
                    return $er->filter($options['filters']);
                },
            ))
            ->add('exchangeSite', EntityType::class, array(
                'class' => ExchangeSite::class,
                'required' => false,
                'choice_label' => 'url',
                'placeholder' => false,
                'label' => 'form.blogs',
                'multiple' => true,
                'attr' => [
                ],
                'query_builder' => function (EntityRepository $er) use ($options) {
                    if (empty($options['filters'])) {
                        return null;
                    }
                    return $er->filter($options['filters']);
                },
            ))
            ->add('save', ButtonType::class, [
              'label' => 'save',
              'attr' => ['class' => 'btn btn-primary']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DirectoriesList::class,
            'translation_domain' => 'directories_list',
            'filters' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_directories_list_relation';
    }
}
