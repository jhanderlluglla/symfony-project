<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UserBundle\Form\Filters\LanguageType;

class ArticleBlogType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'entity.title',
            ])
            ->add('urlPath', TextType::class, [
                'label' => 'entity.url_path',
            ])
            ->add('text', TextareaType::class, [
                'label' => 'entity.text',
                'attr' => ['class' => 'summernote'],
            ])
            ->add('metaKeywords', TextType::class,[
                'label' => 'form.meta_keywords',
                'required' => false,
            ])
            ->add('metaDescription', TextareaType::class,[
                'label' => 'form.meta_description',
                'required' => false,
            ])
            ->add('isEnable', CheckboxType::class, [
                'required' => false,
                'label' => 'entity.is_enable',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'form.submit',
                'attr' => ['class' => 'btn-primary'],
            ])
            ->add('language', LanguageType::class, [
                'label' => 'language',
                'translation_domain' => 'general'
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'CoreBundle\Entity\ArticleBlog',
            'translation_domain' => 'article_blog',
        ]);
    }
}
