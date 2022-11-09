<?php

namespace UserBundle\Form\Netlinking;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use UserBundle\Entity\NetlinkingUrlAnchorsFlowEntity;

/**
 * Class NetlinkingUrlAnchors
 *
 * @package UserBundle\Form\Netlinking
 */
class NetlinkingUrlAnchors extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url', HiddenType::class, [
                'label' => false,
                'required' => false,
            ])
            ->add('anchors', CollectionType::class, array(
                'entry_type' => NetlinkingAnchorType::class,
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
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NetlinkingUrlAnchorsFlowEntity::class,
            'translation_domain' => 'netlinking',
            'status' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netlinking_url_anchors';
    }
}
