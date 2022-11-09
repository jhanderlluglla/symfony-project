<?php

namespace UserBundle\Form\Filters;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Expression;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NumberRangeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('min', IntegerType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'min', 'range-type' => 'min', 'range' => $builder->getName()],
                'required' => false,
                'constraints' => new Callback(array($this, 'validate'))
            ])
            ->add('max', IntegerType::class, [
                'label' => false,
                'attr' => ['placeholder' => 'max', 'range-type' => 'max', 'range' => $builder->getName()],
                'required' => false,
                'constraints' => new Callback(array($this, 'validate')),
            ]);
    }

    public function validate($value, ExecutionContextInterface $context)
    {
        $data = $context->getObject()->getParent()->getData();

        if ($data['min'] != null && $data['max'] != null && $data['min'] > $data['max']) {
            $context->buildViolation('errors.invalid_range')
                ->addViolation();
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'exchange_site_find',
        ]);
    }
}
