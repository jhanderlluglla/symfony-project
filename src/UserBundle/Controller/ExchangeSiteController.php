<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\AbstractMetricsEntity;
use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Rubric;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Repository\ExchangeSiteRepository;
use CoreBundle\Repository\SettingsRepository;
use CoreBundle\Services\AwisInfo;
use CoreBundle\Services\BwaInfo;
use CoreBundle\Services\GoogleNewsInfo;
use CoreBundle\Services\MajesticInfo;
use CoreBundle\Services\MementoInfo;
use CoreBundle\Services\Metrics\MetricsManager;
use CoreBundle\Services\MozInfo;
use CoreBundle\Utils\ExchangeSiteUtil;
use Doctrine\ORM\EntityNotFoundException;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Translation\Translator;
use UserBundle\Form\ExchangeSiteType;
use UserBundle\Security\MainVoter;
use UserBundle\Services\CopywritingArticleProcessor;

/**
 * Class ExchangeSiteController
 *
 * @package UserBundle\Controller
 */
class ExchangeSiteController extends AbstractCRUDController
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->isWriterAdmin() && !$this->get('core.service.access_manager')->canManageNetlinkingProject()) {
            throw new AccessDeniedHttpException();
        }

        $filters = $request->query->all();
        $queryBuilder = $this->getCollectionData($request, $filters);

        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 20);

        $pagerfanta = new Pagerfanta(PagerfantaAdapterFactory::getAdapterInstance($queryBuilder));

        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        $blogsAsJson = [];
        $blogsModel = $this->get("core.blogs.model");
        foreach ($pagerfanta as $item) {
            $blogsAsJson[] = $blogsModel->transformItem($item);
        }

        return $this->render($this->prepareIndexTemplate(), [
            'collection' => $pagerfanta,
            'additionalData' => $this->getAdditionalData($request),
            'jsonBlogs' => json_encode($blogsAsJson),
        ]);
    }

    /**
     * @param ExchangeSite[]|Directory[] $items
     * @param DirectoriesList $directoriesList
     */
    public function transformForGrid(array $items, DirectoriesList $directoriesList)
    {
        $result = [];

        foreach ($items as $item) {
            $result[] = $this->transformItem($item, $directoriesList);
        }
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getApiKeyAction(Request $request)
    {
        return $this->json([
            'api_key' => ExchangeSiteUtil::genAccessToken(),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updatePartnerAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $exchangeSiteRepository = $em->getRepository(ExchangeSite::class);
        $logger = $this->get('monolog.logger.wp_requests');
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader) {
            return $this->json([
                'status' => 'fail',
                'code' => 'no_token',
                'message' => "Your query does't have token",
            ], 400);
        }

        $token = explode(' ', $authHeader)[1];

        /** @var ExchangeSite $partner */
        $partner = $exchangeSiteRepository->findOneBy(['apiKey' => $token]);
        if (!$partner) {
            return $this->json([
                'status' => 'fail',
                'code' => 'wrong_token',
                'message' => 'Your token is incorrect',
            ], 400);
        }

        $data = $request->getContent();

        if (empty($data)) {
            return $this->json([
                'status' => 'fail',
                'code' => 'empty_data',
                'message' => "Your request data is empty",
            ], 400);
        }

        try {
            $data = json_decode($data, true);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'fail',
                'code' => 'invalid_json',
                'message' => "Your json is invalid",
            ], 400);
        }

        $logger->info("Request from WP", [
            'content' => $data,
            'pluginUrl' => $partner->getPluginUrl(),
            'pluginStatus' => $partner->isPluginStatus()
        ]);

        if (empty($data['website_url']) || empty($data['plugin_url']) || empty($data['categories'])) {
            return $this->json([
                'status' => 'fail',
                'code' => 'invalid_data',
                'message' => "Website url or plugin_url or categories is empty",
            ], 400);
        }

        try {
            if (empty($data['version'])) {
                return $this->json([
                    'status' => "ok"
                ], 200);
            }

            if ($partner->getPluginUrl() !== null && !empty($data['test_connection'])) {
                if ($partner->getPluginUrl() !== $data['plugin_url']) {
                    $partner->setPluginUrl($data['plugin_url']);
                    $this->checkConnection($partner);
                    $em->flush();
                }
                if ($partner->isPluginStatus()) {
                    return $this->json([
                        'status' => 'ok'
                    ], 200);
                } else {
                    return $this->json([
                        'status' => 'fail',
                        'code' => 'plugin_not_connected',
                        'message' => "Plugin is not connected",
                    ], 400);
                }
            }

            $partner->setPluginUrl($data['plugin_url']);
            $this->checkConnection($partner);

            $partner->setUrl($data['website_url']);

            foreach ($data['categories'] as $category) {
                $rubric = new Rubric();
                $rubric->setName($category['name']);
                $rubric->setExtId($category['internal_id']);
                $partner->addRubric($rubric);
            }

            $em->persist($partner);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'fail',
                'code' => 'server_error',
                'message' => "Something happen try latter",
            ], 500);
        }

        return $this->json([
            'status' => "ok"
        ], 200);
    }

    private function checkConnection($partner)
    {
        /** @var CopywritingArticleProcessor $copywritingArticleProcessor */
        $copywritingArticleProcessor = $this->get('user.copywriting.article_processor');
        $em = $this->getDoctrine()->getManager();

        $response = $copywritingArticleProcessor->testPluginConnection($partner);
        if ($response['status'] === false) {
            $partner->setPluginStatus(false);
            $em->flush();

            if (isset($response['curlInfo']) && $response['curlInfo']['http_code'] === 403) {
                return $this->json([
                    'status' => 'fail',
                    'code' => 'blocked_by_firewall',
                    'message' => "Connection was blocked by firewall",
                ], 400);
            }

            return $this->json([
                'status' => 'fail',
                'code' => 'plugin_not_connected',
                'message' => "Plugin is not connected",
            ], 400);
        } else {
            $partner->setPluginStatus(true);
        }
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkCreditsAction(Request $request)
    {
        $translator = $this->get('translator');

        /** @var User $user */
        $user = $this->getUser();
        $userRole = $user->getAccountTypeString();

        $url = trim($request->query->get('url'));
        $id = $request->query->get('id');
        $type = $request->query->get('type');
        $language = $request->query->get('language');

        $urlInfo = parse_url($url);

        if (empty($urlInfo["host"])) {
            return $this->json([
                'section' => 'host',
                'message' => $translator->trans('check_credits.errors.host', [], 'exchange_site'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $domain = str_ireplace("www.", "", $urlInfo["host"]);

        /** @var BwaInfo $bwaInfo */
        $bwaInfo = $this->get('core.service.bwa_info');
        $bwaAge = $bwaInfo->getDomainCreation($domain);

        /** @var MajesticInfo $majesticInfo */
        $majesticInfo = $this->get('core.service.majestic_info');

        $trustFlow = $majesticInfo->getTrustFlow($domain);
        $refDomains = $majesticInfo->getRefDomains($domain);

        /** @var AwisInfo $awisInfo */
        $awisInfo = $this->get('core.service.awis_info');
        $alexaRank = $awisInfo->getAlexaRank($domain);

        $age = floatval(ExchangeSiteUtil::dateDifference(new \DateTime(), new \DateTime($bwaAge), '%y.%m'));

        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->getDoctrine()->getRepository(Settings::class);

        $percents = $settingsRepository->getSettingsByIdentificators([
            Settings::COMMISSION_PERCENT,
            Settings::WITHDRAW_PERCENT
        ]);

        $credits = ExchangeSiteUtil::creditAlgo($trustFlow, $refDomains, $age);
        $creditPurchasePrice = $settingsRepository->getSettingValue('prix_achat_credit');
        $credits['cred'] *= $creditPurchasePrice;

        /** @var MetricsManager $metricsManager */
        $semrushService = $this->get('core.service.semrush_info');
        $semrushTraffic = $semrushService->getSemrushTraffic($domain, $language);
        $semrushTrafficCost = $semrushService->getSemrushTrafficCost($domain, $language);

        $credits['cred'] = max($credits['cred'], $semrushTraffic / 100, $semrushTrafficCost / 100);

        $response = [
                'user_role' => $userRole,
                'trust_flow' => $trustFlow,
                'ref_domains' => $refDomains,
                'alexa_rank' => $alexaRank,
                'domain_creation' => $bwaAge,
                'age' => $age,
                'percents' => $percents
            ] + $credits;

        if ($id == 0) {
            /** @var ExchangeSiteRepository $exchangeSiteRepository */
            $exchangeSiteRepository = $this->getDoctrine()->getRepository(ExchangeSite::class);

            $exchangeSite = $exchangeSiteRepository->checkSiteDuplicate($urlInfo['host']);
            if ($exchangeSite) {
                $siteType = $exchangeSite->getSiteType();
                if ($siteType === $type || $user !== $exchangeSite->getUser()) {
                    $siteType = ExchangeSite::UNIVERSAL_TYPE;
                }
                $transId = 'check_credits.errors.' . (empty($siteType) ? 'duplicate' : 'duplicate_' . $siteType);
                $message = $translator->trans($transId, ['%url%' => $url, '%id%' => $exchangeSite->getId()], 'exchange_site');
                if ($user !== $exchangeSite->getUser()) {
                    $message = $translator->trans('check_credits.errors.duplicate_by_another', ['%url%' => $url], 'exchange_site');
                }

                return $this->json([
                    'section' => 'duplicate',
                    'message' => $message,
                ] + $response, Response::HTTP_BAD_REQUEST);
            }
        }

        return $this->json($response);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function manageWebsitesAction(Request $request)
    {
        $this->getDoctrine()->getEntityManager()->getFilters()->enable('softdeleteable');
        $filters['user'] = $this->getUser();

        $exchangeSites = $this->getDoctrine()->getRepository(ExchangeSite::class)->filter($filters);
        $adapter = new DoctrineORMAdapter($exchangeSites);

        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 10);

        $exchangeSiteCollection = new Pagerfanta($adapter);
        $exchangeSiteCollection
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        return $this->render('exchange_site/plugin.html.twig', [
            'exchangeSiteCollection' => $exchangeSiteCollection,
            'additionalData' => $this->getAdditionalData($request),
        ]);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return JsonResponse
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws EntityNotFoundException
     */
    public function setTypeAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var ExchangeSite $exchangeSite */
        $exchangeSite = $this->getDoctrine()->getRepository(ExchangeSite::class)->find($id);

        if (is_null($exchangeSite)) {
            throw new EntityNotFoundException();
        }

        $type = $request->get('type');
        $validator = $this->get('validator');
        $translator = $this->get('translator');

        $errors = $validator->validate($exchangeSite);
        if ($exchangeSite->getSiteType() === ExchangeSite::COPYWRITING_TYPE
            && $type !== ExchangeSite::COPYWRITING_TYPE
            && count($errors) > 0
        ) {
            $location = $this->generateUrl('admin_exchange_site_edit', [
                'id' => $id,
                'type' => $type,
            ]);
            return $this->json([
                'status' => 'success',
                'message' => $translator->trans('redirect_to_edit', [], 'exchange_site'),
                'location' => $location,
            ]);
        }

        if (empty($type)) {
            $type = null;
        }

        $response = [
            'status' => 'success',
            'message' => $translator->trans($type === null ? 'successfully_site_disabled' : 'successfully_changed_type', ['%type%' => $type], 'exchange_site'),
            'type' => $translator->trans('site_type.' . $type, [], 'exchange_site'),
        ];

        if ($exchangeSite->getSiteType() === ExchangeSite::EXCHANGE_TYPE || $exchangeSite->getSiteType() === ExchangeSite::UNIVERSAL_TYPE) {
            $response['resultLocation'] = $this->generateUrl('admin_copywriting_sites');
        }

        $exchangeSite->setSiteType($type);
        $errors = $validator->validateProperty($exchangeSite, 'siteType');

        if (count($errors) > 0) {
            return $this->json([
                'status' => 'error',
                'message' => $translator->trans('invalid_data', [], 'exchange_site'),
            ]);
        }

        $em->flush();

        return $this->json($response);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return JsonResponse
     *
     * @throws EntityNotFoundException
     */
    public function setAutoPublishAction(Request $request, $id)
    {
        /** @var ExchangeSite $exchangeSite */
        $exchangeSite = $this->getDoctrine()->getRepository(ExchangeSite::class)->find($id);

        if (is_null($exchangeSite)) {
            throw new EntityNotFoundException();
        }

        if ($request->get('enable') !== '1') {
            $enable = false;
        } else {
            $enable = true;
        }

        $exchangeSite->setAutoPublish($enable);

        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'status' => 'success',
            'message' => $this->get('translator')->trans('successfully_changed_autopublish.' . ($enable ? 'enabled' : 'disabled'), [], 'exchange_site'),
        ]);
    }

    /**
     * @param ExchangeSite $entity
     * @param array $options
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm($entity, $options = [])
    {
        $options['user'] = $this->getUser();

        return $this->createForm(ExchangeSiteType::class, $entity, $options);
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
    protected function getEntityObject()
    {
        return new ExchangeSite();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'exchange_site';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('admin_exchange_site');
    }

    protected function getAdditionalData(Request $request)
    {
        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->getDoctrine()->getRepository(Settings::class);

        $percents = $settingsRepository->getSettingsByIdentificators([
            Settings::COMMISSION_PERCENT,
            Settings::WITHDRAW_PERCENT
        ]);

        return [
            Settings::WEBMASTER_ADDITIONAL_PAY => $settingsRepository->getSettingValue(Settings::WEBMASTER_ADDITIONAL_PAY),
            'percents' => $percents,
            Settings::PLUGIN_MORE_INFORMATION => $settingsRepository->getSettingValue(Settings::PLUGIN_MORE_INFORMATION),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCollectionData(Request $request, $filters = [])
    {
        $this->getDoctrine()->getEntityManager()->getFilters()->enable('softdeleteable');
        $filters = $filters + [
                'user' => $this->getUser(),
                'nonOwner' => false,
                'siteType' => [ExchangeSite::EXCHANGE_TYPE, ExchangeSite::UNIVERSAL_TYPE],
            ];

        return $this->getDoctrine()->getRepository($this->getEntity())->filter($filters);
    }

    /**
     * @param Request $request
     * @param ExchangeSite $entity
     */
    protected function beforeInsert(Request $request, $entity)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $entity->setUser($user);
        }

        $entity->setSiteType(ExchangeSite::EXCHANGE_TYPE);
    }

    /**
     * @param Request $request
     * @param ExchangeSite $entity
     */
    protected function afterInsert(Request $request, $entity)
    {
        $this->updateMetrics($entity);
        $this->getDoctrine()->getManager()->flush();
    }

    /**
     * @inheritdoc
     */
    protected function beforeUpdate(Request $request, $oldEntity, $entity)
    {
        $type = $request->query->get('type');
        if ($type !== null) {
            $validator = $this->get('validator');
            $entity->setSiteType($type);
            $errors = $validator->validateProperty($entity, 'siteType');

            if (count($errors) > 0) {
                $entity->setSiteType(null);
            }
        }
    }

    /**
     * @return null|string
     */
    protected function getVoterNamespace()
    {
        return MainVoter::EXCHANGE_SITE;
    }

    /**
     * @param AbstractMetricsEntity $entity
     */
    private function updateMetrics(AbstractMetricsEntity $entity)
    {
        /** @var MetricsManager $metricsManager */
        $metricsManager = $this->get('core.service.metrics_manager');
        $metrics = $metricsManager->updateMetrics($entity->getSite());
        $metricsManager->updateMetricsEntitiesByMetrics($entity, $metrics);
    }

    /**
     * @param Request $request
     * @param Directory $oldEntity
     * @param Directory $entity
     */
    protected function afterUpdate(Request $request, $oldEntity, $entity)
    {
        if ($oldEntity->getSite() !== $entity->getSite()) {
            $this->updateMetrics($entity);
            $this->getDoctrine()->getManager()->flush();
        }
    }
}
