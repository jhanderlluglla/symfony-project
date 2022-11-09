<?php

namespace UserBundle\Form\Netlinking;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;

use UserBundle\Entity\NetlinkingUrlFlowEntity;

/**
 * Class NetlinkingUrls
 *
 * @package UserBundle\Form\Netlinking
 */
class NetlinkingUrlsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url', UrlType::class, [
                'label' => 'form.url',
                'required' => true,
                'attr' => [
                    'class' => 'netlinking_url',
                    'maxlength' => 250,
                ]
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NetlinkingUrlFlowEntity::class,
            'translation_domain' => 'netlinking',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netlinking_urls';
    }
}