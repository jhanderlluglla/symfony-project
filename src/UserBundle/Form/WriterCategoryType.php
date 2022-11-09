<?php

namespace UserBundle\Form;

use CoreBundle\Entity\CopywritingProject;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WriterCategoryType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $keys = array_map(function ($elem){ return $elem . ".title";}, CopywritingProject::WRITER_CATEGORIES);
        $values = CopywritingProject::WRITER_CATEGORIES;
        $resolver->setDefaults(array(
            'choices' => array_combine($keys, $values),
            'choices_as_values' => true,
            'expanded' => true,
            'multiple' => false,
            'required' => true,
        ));

    }

    /**
     * @return null|string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}