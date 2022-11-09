<?php

namespace UserBundle\Form\Filters;

use CoreBundle\Entity\ExchangeSiteTtfCategories;
use CoreBundle\Entity\TtfCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UserBundle\Form\Filters\NumberRangeType;

class TtfCategoryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('category', EntityType::class, ['class' => TtfCategory::class, 'placeholder' => 'form.ttf_category_placeholder', 'attr' => ['class' => 'chosen-select']])
            ->add('rate', NumberRangeType::class, ['label' => 'form.rate']);
    }

}
