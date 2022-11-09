<?php

namespace UserBundle\Form\Netlinking;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use CoreBundle\Entity\User;

/**
 * Class CopyWriterSelectType
 *
 * @package UserBundle\Form\Netlinking
 */
class CopyWriterSelectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('copywriter', EntityType::class, [
                'class' => User::class,
                'attr' => [
                    'class' => 'assign_writer_form js_assign_writer_form'
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.enabled = 1')
                        ->andWhere('u.roles LIKE :role_writer')
                        ->andWhere('u.roles NOT LIKE :role_writer_copywriting')
                        ->orWhere('u.roles LIKE :role_writer_netlinking')
                        ->setParameter('role_writer', '%'.User::ROLE_WRITER.'%')
                        ->setParameter('role_writer_netlinking', '%'.User::ROLE_WRITER_NETLINKING.'%')
                        ->setParameter('role_writer_copywriting', '%'.User::ROLE_WRITER_COPYWRITING.'%');
                },
                'choice_attr' => function (User $choiceValue, $key, $value) {
                    return ['data-language' => $choiceValue->getWorkLanguage()];
                },
                'placeholder' => 'form.choose_option',
                'label' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'netlinking',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'copy_writer_select';
    }
}