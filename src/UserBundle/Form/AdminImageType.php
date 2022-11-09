<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{TextareaType,TextType,HiddenType,SubmitType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminImageType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('author', TextType::class,[
                'label' => 'author',
                'required' => true,
            ])
            ->add('description', TextareaType::class,[
                'label' => 'description',
                'required' => true,
            ])
            ->add( 'filename', HiddenType::class)
            ->add('url', HiddenType::class)
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
                'attr' => ['class' => 'btn-primary', 'id' => 'submit_btn'],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'CoreBundle\Entity\AdminImage',
            'translation_domain' => 'admin_images',
        ]);
    }
}
