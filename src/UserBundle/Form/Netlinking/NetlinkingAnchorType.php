<?php

namespace UserBundle\Form\Netlinking;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use UserBundle\Entity\NetlinkingAnchorFlowEntity;

/**
 * Class NetlinkingAnchorType
 *
 * @package UserBundle\Form\Netlinking
 */
class NetlinkingAnchorType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('directory', HiddenType::class, array(
                'label' => false,
                'required' => false,
            ))
            ->add('exchangeSite', HiddenType::class, array(
                'label' => false,
                'required' => false,
            ))
            ->add('url', HiddenType::class, [
                'label' => false,
                'required' => true,
            ])
            ->add('webmasterAnchor', HiddenType::class, [
                'label' => false,
            ])
        ;
        if ($options['status'] == 'finished') {
            $builder
                ->add('anchor', HiddenType::class, [
                    'required' => false,
                ])
            ;
        } else {
            $builder
                ->add('anchor', TextType::class, [
                    'label' => 'form.anchor',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'form.placeholder',
                        'maxlength' => 250
                    ]
                ])
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => NetlinkingAnchorFlowEntity::class,
            'translation_domain' => 'netlinking',
            'status' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netlinking_anchor';
    }
}