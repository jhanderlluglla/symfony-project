<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Repository\SettingsRepository;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Form\CopywritingSiteType;
use UserBundle\Form\Filters\CopywritingSiteFilterType;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use Pagerfanta\Pagerfanta;
use UserBundle\Security\MainVoter;


class ExchangeSiteCopywritingController extends AbstractCRUDController
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $filters = [];
        if ($request->get('query')) {
            $filters['copywriting_site_filter']['search_query'] = $request->get('query');
        }
        $queryBuilder = $this->getCollectionData($request, $filters);
        $perPage = 20;
        $page = $request->get('page', 1);
        $pagerfanta = new Pagerfanta(PagerfantaAdapterFactory::getAdapterInstance($queryBuilder));
        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        $template = $this->prepareIndexTemplate();
        if ($request->isXmlHttpRequest()) {
            $template = 'copywriting_sites/filter.html.twig';
        }

        return $this->render($template, [
            'collection' => $pagerfanta,
            'maxPerPage' => $perPage,
            'countResults' => $pagerfanta->count(),
            'additionalData' => $this->getAdditionalData($request)
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCollectionData(Request $request, $filters = [])
    {
        $this->getDoctrine()->getEntityManager()->getFilters()->enable('softdeleteable');
        $filters = $filters + [
                'user' => $this->getUser(),
                'filter' => false,
                'siteType' => [ExchangeSite::COPYWRITING_TYPE, ExchangeSite::UNIVERSAL_TYPE],
            ];

        return $this->getDoctrine()->getRepository($this->getEntity())->filter($filters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new ExchangeSite();
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return ExchangeSite::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('admin_copywriting_sites');
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'copywriting_sites';
    }

    /**
     * @param ExchangeSite $entity
     * @param array        $options
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm($entity, $options = [])
    {
        $options['user'] = $this->getUser();

        return $this->createForm(CopywritingSiteType::class, $entity, $options);
    }

    /**
     * @param Request      $request
     * @param ExchangeSite $entity
     */
    protected function beforeInsert(Request $request, $entity)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $entity->setUser($user);
        }
        $entity->setSiteType(ExchangeSite::COPYWRITING_TYPE);
    }


    /**
     * @inheritdoc
     */
    protected function getAdditionalData(Request $request)
    {

        $filterForm = $this->createForm(CopywritingSiteFilterType::class);
        $filterForm->handleRequest($request);

        /** @var SettingsRepository $settingRepository */
        $settingRepository = $this->getDoctrine()->getRepository(Settings::class);

        return [
            'plugin' => $settingRepository->getSettingsByIdentificators([Settings::PLUGIN_MORE_INFORMATION]),
            'filter_form' => $filterForm->createView()
            ];
    }

    /**
     * @return null|string
     */
    protected function getVoterNamespace()
    {
        return MainVoter::COPYWRITING_SITES;
    }
}