<?php

namespace UserBundle\Form\Directory;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use CoreBundle\Entity\Directory;
use CoreBundle\Entity\Category;
use CoreBundle\Entity\User;

class DirectoryAddType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var User $user */
        $user = $options['user'];
        if (!is_null($user)) {
            if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
                $builder
                    ->add('webmasterPartner', EntityType::class, array(
                        'required' => false,
                        'class' => User::class,
                        'choice_label' => 'fullName',
                        'placeholder' => 'form.webmaster_partner',
                        'query_builder' => function(EntityRepository $er) {
                            return $er
                                ->createQueryBuilder('u')
                                ->where("u.roles LIKE '%" .User::ROLE_WEBMASTER. "%'")
                                ->orderBy('u.fullName', 'ASC');
                        },
                        'label' => '',
                        'attr' => [
                            'class' => 'chosen-select'
                        ]
                    ));
            }
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'form.name',
                'required' => true,
            ])
            ->add('categories', EntityType::class, array(
                'required' => true,
                'multiple'=> true,
                'class' => Category::class,
                'choice_label' => 'multiselectName',
                'query_builder' => function(EntityRepository $er) {
                    return $er
                        ->createQueryBuilder('c')
                        ->where('c.parent IS NOT NULL')
                        ->orderBy('c.lft', 'ASC');
                },
                'label' => 'form.categories',
                'attr' => [
                    'class' => 'chosen-select'
                ]
            ))
            ->add('webmasterOrder', TextType::class, [
                'label' => 'form.webmaster_order',
                'required' => false,
            ])
            ->add('personalAccountWebmaster', CheckboxType::class, [
                'label' => 'form.webmaster_account',
                'required' => false,
            ])
            ->add('tariffWebmasterPartner', MoneyType::class, [
                'label' => 'form.tariff_webmaster_partner',
                'grouping' => true,
                'required' => false,
            ])
            ->add('webmasterAnchor', CheckboxType::class, [
                'label' => 'form.webmaster_anchor',
                'required' => false,
            ])
            ->add('tariffExtraWebmaster', MoneyType::class, [
                'label' => 'form.tariff_extra_webmaster',
                'grouping' => true,
                'required' => false,
            ])
            ->add('tariffExtraSeo', MoneyType::class, [
                'label' => 'form.tariff_extra_seo',
                'grouping' => true,
                'required' => false,
            ])
            ->add('age', DateType::class, [
                'label' => 'form.age',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('pageRank', TextType::class, [
                'label' => 'form.page_rank',
                'required' => false,
            ])
            ->add('totalBacklink', TextType::class, [
                'label' => 'form.total_back_link',
                'required' => false,
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'form.instructions',
                'required' => false,
            ])
            ->add('acceptInnerPages', CheckboxType::class, [
                'label' => 'form.accept_inner_pages',
                'required' => false,
            ])
            ->add('acceptLegalInfo', CheckboxType::class, [
                'label' => 'form.accept_legal_info',
                'required' => false,
            ])
            ->add('acceptCompanyWebsites', CheckboxType::class, [
                'label' => 'form.accept_company_websites',
                'required' => false,
            ])
            ->add('nddTarget', UrlType::class, [
                'label' => 'form.ndd_target',
                'required' => false,
            ])
            ->add('linkSubmission', UrlType::class, [
                'label' => 'form.link_submission',
                'required' => false,
            ])
            ->add('pageCount', TextType::class, [
                'label' => 'form.page_count',
                'required' => false,
            ])
            ->add('minWordsCount', TextType::class, [
                'label' => 'form.min_words_count',
                'required' => false,
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'form.active',
                'required' => false,
            ])
            ->add('vipState', CheckboxType::class, [
                'label' => 'form.vip_state',
                'required' => false,
            ])
            ->add('vipText', TextareaType::class, [
                'label' => 'form.vip_text',
                'required' => false,
            ])
            ->add('save',  SubmitType::class, [
                'label' => 'save',
                'attr' => ['class' => 'btn btn-primary btn-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Directory::class,
            'translation_domain' => 'directory',
            'user' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_directory';
    }
}