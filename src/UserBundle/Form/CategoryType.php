<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use CoreBundle\Entity\Category;

class CategoryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $method = $builder->getMethod();

        if (strtoupper($method) == Request::METHOD_POST) {
            $builder
                ->add('parent', EntityType::class, array(
                    'required' => true,
                    'class' => 'CoreBundle\Entity\Category',
                    'label' => 'parent'
                ))
            ;
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'name',
                'required' => true,
            ])
            ->add('language', ChoiceType::class, [
                'label' => 'language',
                'required' => true,
                'choices' => $options['locales']
            ])
            ->add('save',  SubmitType::class, [
                'label' => 'save',
                'attr' => ['class' => 'btn btn-primary btn-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'translation_domain' => 'category',
            'locales' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_category';
    }
}