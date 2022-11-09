<?php

namespace UserBundle\Form;

use CoreBundle\Entity\Category;
use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingKeyword;
use CoreBundle\Entity\Rubric;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UserBundle\Services\CopywritingArticleProcessor;

class CopywritingArticleType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            [$this, 'onPreSubmitData']
        );

        /** @var CopywritingArticle $article */
        $article = $builder->getData();
        $order = $article->getOrder();

        if($article && $order->isMetaTitle()) {
            $builder
                ->add('metaTitle', TextType::class, ['label' => false, 'required' => false]);
        }

        if($article && $order->isMetaDescription()) {
            $builder
                ->add('metaDesc', TextAreaType::class, ['label' => false, 'required' => false]);
        }

        if($article && $order->getExchangeProposition()) {
            $exchangeSite = $order->getExchangeProposition()->getExchangeSite();
            if($exchangeSite->hasPlugin()) {
                $options = ['required' => false];
                if ($order->getImagesPerArticleFrom() || $order->getImagesPerArticleTo()) {
                    $options['required'] = true;
                }

                $builder->add('frontImage', UrlType::class, $options);
            }

            if(!$exchangeSite->getRubrics()->isEmpty()) {
                $builder->add('rubrics', EntityType::class, [
                    'class' => Rubric::class,
                    'multiple' => true,
                    'choices' => $exchangeSite->getRubrics(),
                    'required' => true
                ]);
            }
        }

        $builder
            ->add('text', TextareaType::class, ['label' => false, 'attr' => ['class' => 'summernote'], 'required' => false])
            ->add('nonconforms', CollectionType::class, [
                'entry_type'    => CopywritingArticleNonconformType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add'     => true,
                'allow_delete'  => true,
                'delete_empty' => true,
                'by_reference' => false,
            ])
            ->add('validate', SubmitType::class)
            ->add('validateAndSave', SubmitType::class)
            ->add('save', SubmitType::class)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'CoreBundle\Entity\CopywritingArticle',
            'allow_extra_fields' => true,
            'validation_groups' => function (FormInterface $form) {
                return $form->get('save')->isClicked() ? false : "Default";
            },
        ]);
    }

    public function onPreSubmitData(FormEvent $event)
    {
        /** @var CopywritingArticle $data */
        $data = $event->getData();
        $data['text'] = CopywritingArticleProcessor::prepareArticleText($data['text']);
        $event->setData($data);
    }
}
