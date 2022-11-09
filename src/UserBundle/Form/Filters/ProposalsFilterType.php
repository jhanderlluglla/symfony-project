<?php
namespace UserBundle\Form\Filters;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Class SettingsFilterType
 *
 * @package UserBundle\Form\Filters
 */
class ProposalsFilterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setMethod('POST')
            ->add('id', IntegerType::class, [
                'required' => false,
                'label' => 'filter_form.search'
            ])
            ->add('save', SubmitType::class, array(
                'attr' => array('class' => 'btn btn-primary btn'),
                'label' => 'filter_form.filter',
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'proposal_filter';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'general'
        ]);
    }
}