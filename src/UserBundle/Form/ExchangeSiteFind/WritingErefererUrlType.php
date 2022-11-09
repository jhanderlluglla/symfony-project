<?php

namespace UserBundle\Form\ExchangeSiteFind;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class WritingErefererUrlType
 *
 * @package UserBundle\Form\ExchangeSiteFind
 */
class WritingErefererUrlType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url', UrlType::class, [
                'label' => 'modal.writing_ereferer.form.url',
                'required' => true,
                'attr' => [
                    'class' => 'writing_ereferer_url'
                ]
            ])
            ->add('anchor', TextType::class, [
                'label' => 'modal.writing_ereferer.form.anchor',
                'required' => true,
                'attr' => [
                    'class' => 'writing_ereferer_anchor'
                ]
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'exchange_site_find',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'user_writing_ereferer_url';
    }
}