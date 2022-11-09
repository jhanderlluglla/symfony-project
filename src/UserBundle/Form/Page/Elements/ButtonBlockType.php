<?php

namespace UserBundle\Form\Page\Elements;


use CoreBundle\Entity\Page\Elements\ButtonBlock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ButtonBlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['label' => 'form.title'])
            ->add('icon', ChoiceType::class, [
                'label' => 'form.icon',
                'choices' => [ButtonBlock::DIRECTORY_ICON, ButtonBlock::REDACTION_ICON, ButtonBlock::EXCHANGE_ICON],
                'choice_label' => function ($value) {
                    return 'form.' . $value;
                },
            ])
            ->add('text', TextareaType::class, [
                'attr' => ['class' => 'summernote'],
                'label' => 'form.text'
            ])
            ->add('button', CustomButtonType::class, ['label' => 'form.button'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'CoreBundle\Entity\Page\Elements\ButtonBlock',
            'translation_domain' => 'pages',
        ]);
    }
}