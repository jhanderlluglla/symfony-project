<?php

namespace UserBundle\Form;

use CoreBundle\Entity\ExchangeSite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UserBundle\Form\Filters\LanguageType;
use CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;


class CopywritingSiteType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('url', TextType::class, [
                'label' => 'url',
                'required' => true,
                'attr' => [
                    'maxlength' => 250,
                ]
            ])
            ->add('language', LanguageType::class, [
                'label' => 'language',
            ])
            ->add('apiKey', HiddenType::class, [
                'label' => 'api_key',
            ])
            ->add('save',  SubmitType::class, [
                'label' => 'save',
                'attr' => [
                    'class' => 'btn btn-primary',
                    'disabled' => 'disabled'
                ]
            ])
        ;
        /** @var User $user */
        $user = $options['user'];
        if (!is_null($user) && $user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $builder
                ->add('user', EntityType::class, array(
                    'required' => false,
                    'class' => User::class,
                    'choice_label' => 'fullName',
                    'placeholder' => 'select_webmaster',
                    'label' => 'affected_webmaster',
                    'query_builder' => function(EntityRepository $er) {
                        return $er
                            ->createQueryBuilder('u')
                            ->where("u.roles LIKE '%" .User::ROLE_WEBMASTER. "%'")
                            ->orderBy('u.fullName', 'ASC');
                    },
                ));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ExchangeSite::class,
            'translation_domain' => 'exchange_site',
            'validation_groups' => 'copywriting',
            'user' => null
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_exchange_site';
    }
}