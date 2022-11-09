<?php

namespace UserBundle\Form\ExchangeSiteFind;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use Symfony\Component\Validator\Constraints\Valid;

/**
 * Class WritingErefererType
 *
 * @package UserBundle\Form\ExchangeSiteFind
 */
class WritingErefererType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('urls', CollectionType::class, array(
                'entry_type' => WritingErefererUrlType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'constraints' => new Valid(),
                'label' => false,
            ))
            ->add('instructions', TextareaType::class, [
                'label' => 'modal.writing_ereferer.form.instructions',
                'required' => false,
            ])
            ->add('countWords', HiddenType::class, [
                'required' => false
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
        return 'user_writing_ereferer';
    }
}