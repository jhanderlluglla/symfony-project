<?php

namespace UserBundle\Form\Netlinking;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use UserBundle\Entity\NetlinkingFlowEntity;

/**
 * Class AddFirstStepType
 *
 * @package UserBundle\Form\Netlinking
 */
class AddSecondStepType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('urlAnchors', CollectionType::class, array(
                'entry_type' => NetlinkingUrlAnchors::class,
                'allow_add' => true,
                'allow_delete' => false,
                'by_reference' => false,
                'label' => false,
                'entry_options' => [
                    'label' => false,
                ],
                'prototype_name' => '__parentId__',
                'error_bubbling' => false
            ))
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();
                $exchangeSites = $data->getDirectoryList()->getExchangeSite();
                $showAcceptRulesField = false;
                foreach ($exchangeSites as $exchangeSite) {
                    if ($exchangeSite->getNofollowLink() || $exchangeSite->getSponsorisedArticle() || $exchangeSite->getAdditionalExternalLink()) {
                        $showAcceptRulesField = true;
                        break;
                    }
                }
                if ($showAcceptRulesField){
                    $form->add('acceptRules', CheckboxType::class, [
                        'required' => true,
                        'mapped' => false,
                        'label' => 'form.accept_rules'
                    ]);
                }
            })
            ->add('save',  SubmitType::class, [
                'label' => 'form.confirm',
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
            'data_class' => NetlinkingFlowEntity::class,
            'translation_domain' => 'netlinking',
            'user' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'netlinking_add_second_step';
    }
}