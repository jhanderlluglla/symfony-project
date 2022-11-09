<?php

namespace UserBundle\Form\Filters;

use CoreBundle\Entity\Constant\Language;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageType extends ChoiceType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault(
            'choice_loader',
            new CallbackChoiceLoader(
                function () {
                    $result = [];
                    foreach (Language::getAll() as $languageCode) {
                        $result[$languageCode] = Intl::getLanguageBundle()->getLanguageName($languageCode);
                    }

                    return array_flip($result);
                }
            )
        );
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        if (!$options['empty_data']) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                if (is_null($event->getData())) {
                    $event->setData(\Locale::getDefault());
                }
            });
        }


    }
}
