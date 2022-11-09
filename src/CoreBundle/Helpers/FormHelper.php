<?php

namespace CoreBundle\Helpers;

use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType as SymfonyLanguageType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use UserBundle\Form\Filters\LanguageType;
use UserBundle\Form\Filters\NumberRangeType;

class FormHelper
{
    /** @var EntityManager */
    private $em;

    /**
     * FormHelper constructor.
     *
     * @param EntityManager $em
     */
    public function __construct($em)
    {
        $this->em = $em;
    }

    /**
     * @param FormInterface $form
     * @param array $data
     */
    public function formSetValues($form, $data)
    {
        foreach ($data as $fieldName => $value) {
            try {
                if (is_null($value) || empty($value)) {
                    continue;
                }

                /** @var Form $field */
                $field = $form->get($fieldName);

                switch (get_class($field->getConfig()->getType()->getInnerType())) {
                    case NumberRangeType::class:
                        $min = floatval($value['min']);
                        $max = floatval($value['max']);

                        if ($min !== 0.) {
                            $field->get('min')->setData($min);
                        }

                        if ($max !== 0.) {
                            $field->get('max')->setData($max);
                        }
                        break;

                    case CheckboxType::class:
                        if (!empty($value)) {
                            $field->setData(true);
                        }
                        break;

                    case ChoiceType::class:
                        $options = $field->getConfig()->getOptions();
                        $type = ChoiceType::class;
                        $options['data'] = $value;
                        $form->add($fieldName, $type, $options);
                        break;

                    case TextType::class:
                    case SymfonyLanguageType::class:
                    case LanguageType::class:
                        $field->setData($value);
                        break;

                    case EntityType::class:
                        $object = $this->em->getRepository($field->getConfig()->getOption('class'))->find($value);
                        if ($object) {
                            $field->setData($object);
                        }
                        break;
                }

            } catch (\OutOfBoundsException $exception) {
            }
        }
    }
}
