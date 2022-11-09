<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\User;
use CoreBundle\Exceptions\WorkflowTransitionEntityException;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Repository\ExchangePropositionRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Pagerfanta\Pagerfanta;
use GuzzleHttp\Client;
use CoreBundle\Entity\ExchangeProposition;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use UserBundle\Security\ExchangePropositionVoter;
use UserBundle\Security\MainVoter;

/**
 * Class ExchangeSiteProposalsController
 *
 * @package UserBundle\Controller
 */
class ExchangeSiteProposalsController extends ExchangeSiteAbstract
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

        $this->denyAccessUnlessGranted(ExchangePropositionVoter::ACTION_SHOW_LIST, ExchangeProposition::class);

        $this->get('doctrine.orm.entity_manager')->getFilters()->enable('softdeleteable');

        $page = $request->query->get('page', 1);
        $modificationPage = $request->query->get('modification-page', 1);
        $finishedPage = $request->query->get('finished-page', 1);

        $proposalsFilter = [
            'status' => [ExchangeProposition::STATUS_ACCEPTED],
            'type' => ExchangeProposition::EXTERNAL_TYPE,
        ];
        $modificationsFilter = [
            'modification_status' => [
                ExchangeProposition::MODIFICATION_STATUS_1,
                ExchangeProposition::MODIFICATION_STATUS_4,
            ],
            'type' => ExchangeProposition::EXTERNAL_TYPE,
        ];
        $finishedFilter = [
            'status' => ExchangeProposition::STATUS_PUBLISHED,
            'type' => ExchangeProposition::EXTERNAL_TYPE,
        ];

        if ($user->isWebmaster()) {
            $proposalsFilter['user'] = $user;
            $proposalsFilter['status'] = [
                ExchangeProposition::STATUS_AWAITING_WEBMASTER,
                ExchangeProposition::STATUS_ACCEPTED
            ];

            $modificationsFilter['user'] = $user;
        }

        /** @var ExchangePropositionRepository $exchangePropositionRepository */
        $exchangePropositionRepository = $this->getDoctrine()->getRepository(ExchangeProposition::class);

        $queryBuilder = $exchangePropositionRepository->filter($proposalsFilter);
        $pagerfanta = PagerfantaAdapterFactory::getPagerfantaInstance($queryBuilder, $page);

        $modificationQueryBuilder = $exchangePropositionRepository->filter($modificationsFilter);
        $modificationPagerfanta = PagerfantaAdapterFactory::getPagerfantaInstance($modificationQueryBuilder, $modificationPage);

        $finishedBuilder = $exchangePropositionRepository->filter($finishedFilter, ['finishedAt' => 'desc']);
        $finishedPagerfanta = PagerfantaAdapterFactory::getPagerfantaInstance($finishedBuilder, $finishedPage);

        return $this->render('exchange_site_proposals/index.html.twig', [
            'docPath' => $this->getParameter('docs_local_path') . '/',
            'collection' => $pagerfanta,
            'modification' => $modificationPagerfanta,
            'finished' => $finishedPagerfanta,
        ]);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return JsonResponse
     */
    public function detailsAction(Request $request, $id)
    {
        $translator = $this->get('translator');

        $entity = $this->existsProposal($id);
        if (!($entity instanceof ExchangeProposition)) {
            return $this->json($entity);
        }

        $result = $this->canRead($entity);
        if (!empty($result)) {
            return $this->json($result);
        }

        return $this->json([
            'result' => 'success',
            'title' => $translator->trans('modal.title', [], 'exchange_site_proposals'),
            'body' => $this->renderView('exchange_site_proposals/details.html.twig',
                [
                    'entity' => $entity,
                ])
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function historyAction(Request $request)
    {
        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 20);

        $filter = [
            'status' => [ExchangeProposition::STATUS_PUBLISHED],
            'type' => ExchangeProposition::EXTERNAL_TYPE,
            'user' => $this->getUser(),
        ];
        $exchangePropositionRepository = $this->getDoctrine()->getRepository(ExchangeProposition::class);
        $queryBuilder = $exchangePropositionRepository->filter($filter, false, ['publishedAt' => 'desc']);
        $pagerfanta = new Pagerfanta(PagerfantaAdapterFactory::getAdapterInstance($queryBuilder));
        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        return $this->render('exchange_site_proposals/history.html.twig', [
            'collection' => $pagerfanta,
        ]);
    }

    /**
     * Preview of document
     *
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     */
    public function previewAction(Request $request, $id)
    {

        $entity = $this->existsProposal($id);
        if (!($entity instanceof ExchangeProposition)) {
            throw new EntityNotFoundException();
        }

        $documentImagePath = $this->getParameter('upload_docs_dir') . DIRECTORY_SEPARATOR . $entity->getDocumentImage();

        $result = $this->canRead($entity);
        if (!empty($result)) {
            throw new AccessDeniedException($documentImagePath);
        }

        $fs = new Filesystem();

        if (!$fs->exists($documentImagePath)) {
            throw new FileNotFoundException();
        }

        $content = file_get_contents($documentImagePath);
        $content = str_ireplace('<img src="', '<img src="' . $this->getParameter('docs_local_path') . '/', $content);

        return new Response($content);
    }

    /**
     * Show modal window
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function modalAction(Request $request)
    {
        $translator = $this->get('translator');

        $id   = $request->query->get('id');
        $mode = $request->query->get('mode');

        $entity = $this->existsProposal($id);
        if (!($entity instanceof ExchangeProposition)) {
            return $this->json($entity, Response::HTTP_BAD_REQUEST);
        }

        $result = $this->canRead($entity);
        if (!empty($result)) {
            return $this->json($result, Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'result' => 'success',
            'title' => $translator->trans('modal.' .$mode. '.title', [], 'exchange_site_proposals'),
            'body' => $this->renderView('exchange_site_proposals/' .$mode. '.html.twig', [
                'id' => $id,
                'entity' => $entity,
            ]),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function acceptAction(Request $request)
    {
        $translator = $this->get('translator');

        $id  = $request->query->get('id');

        $entity = $this->existsProposal($id);
        if (!($entity instanceof ExchangeProposition)) {
            return $this->json($entity, Response::HTTP_BAD_REQUEST);
        }

        $result = $this->canRead($entity);
        if (!empty($result)) {
            return $this->json($result, Response::HTTP_FORBIDDEN);
        }

        if ($this->applyTransaction($entity, ExchangeProposition::TRANSITION_ACCEPT, $resultTransition)) {
            return $resultTransition;
        }

        return $this->json([
            'result' => 'success',
            'title' => $translator->trans('modal.validation.title', [], 'exchange_site_proposals'),
            'body' => $this->renderView('exchange_site_proposals/validation.html.twig', [
                'id' => $id,
                'entity' => $entity,
            ]),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function validateAction(Request $request)
    {
        $translator = $this->get('translator');

        $id  = $request->request->get('id');
        $url  = $request->request->get('url');
        $user = $this->getUser();

        $entity = $this->existsProposal($id);

        $result = $this->canRead($entity);
        if (!empty($result)) {
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        if (empty($url)) {
            return $this->json([
                'result' => 'fail',
                'error' => $translator->trans('modal.validation.errors.url', [], 'exchange_site_proposals'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $urlValidator = Validation::createValidator();
        if (0 !== count($urlValidator->validate($url, new Url()))) {
            return $this->json([
                'result' => 'fail',
                'error' => $translator->trans('modal.validation.errors.url_invalid', [], 'exchange_site_proposals'),
            ], Response::HTTP_BAD_REQUEST);
        }

        $exchangeSiteUrl = $entity->getExchangeSite()->getUrl();

        $userUrlArr = parse_url($url);
        $userUrlDomain = str_ireplace("www.", "", $userUrlArr["host"]);

        $exchangeSiteUrlArr = parse_url($exchangeSiteUrl);
        $exchangeSiteUrlDomain = str_ireplace("www.", "", $exchangeSiteUrlArr["host"]);

        if ($userUrlDomain != $exchangeSiteUrlDomain) {
            return $this->json([
                'result' => 'fail',
                'error' => $translator->trans('modal.validation.errors.domains', ['%user_url%' => $userUrlDomain, '%site_url%' => $exchangeSiteUrlDomain], 'exchange_site_proposals'),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$user->isSuperAdmin()) {
            $client = new Client([
                'base_uri' => $userUrlArr['scheme'] . '://' . $userUrlArr['host'],
                'timeout' => 2.0,
            ]);

            try {
                $response = $client->request('GET', isset($userUrlArr['path']) ? $userUrlArr['path'] : '/');
            } catch (\Exception $e) {
                return $this->json([
                    'result' => 'fail',
                    'error' => $translator->trans('modal.validation.errors.none_access', [], 'exchange_site_proposals'),
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($response && $response->getStatusCode() !== Response::HTTP_OK) {
                return $this->json([
                    'result' => 'fail',
                    'error' => $translator->trans('modal.validation.errors.none_access', [], 'exchange_site_proposals'),
                ], Response::HTTP_BAD_REQUEST);
            }

            $html = $response->getBody()->getContents();

            if ($html) {
                $errors = 0;

                $crawler = new Crawler($html);

                $urls = $entity->getCheckLinksUrls();

                $parsedUrl = [];
                $crawler = $crawler->filter('a');
                foreach ($crawler as $domElement) {
                    $href = trim(html_entity_decode($domElement->getAttribute('href')), "/");

                    if (strrpos($href, "http") === false) {
                        $parsedUrl[] = $exchangeSiteUrlArr["scheme"] . '://' . $exchangeSiteUrlArr["host"] . "/" . $href;
                    } else {
                        $parsedUrl[] = $href;
                    }
                }

                foreach ($urls as $checkUrl) {
                    $trimmedCheckUrl = trim(html_entity_decode($checkUrl), "/");
                    if (in_array($trimmedCheckUrl, $parsedUrl)) {
                        $key = array_search($trimmedCheckUrl, $parsedUrl);
                        unset($urls[$key]);
                    } else {
                        $errors++;
                    }
                }

                if (!empty($errors)) {
                    return $this->json([
                        'result' => 'fail',
                        'error' => $translator->trans('modal.validation.errors.link_not_found', ['%link%' => implode(', ', $urls)], 'exchange_site_proposals'),
                    ], Response::HTTP_BAD_REQUEST);
                }
            }
        }

        $entity->setPagePublish($url);

        $this->applyTransaction($entity, ExchangeProposition::TRANSITION_PUBLISH);

        return $this->json([
            'result' => 'success',
            'title' => $translator->trans('modal.validation.success.done', [], 'exchange_site_proposals'),
            'body' => $translator->trans('modal.validation.success.publication_validated', ['%money%' => $entity->getCredits()], 'exchange_site_proposals'),
            'id' => $id,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function modificationAction(Request $request)
    {
        $translator = $this->get('translator');

        $id  = $request->request->get('id');
        $comment  = $request->request->get('comment');

        $entity = $this->existsProposal($id);
        if (!($entity instanceof ExchangeProposition)) {
            return $this->json($entity, Response::HTTP_BAD_REQUEST);
        }

        $result = $this->canRead($entity);
        if (!empty($result)) {
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        $entity->setComments($comment);

        if ($this->applyTransaction($entity, ExchangeProposition::TRANSITION_CHANGE, $resultTransition)) {
            return $resultTransition;
        }

        return $this->json([
            'result' => 'success',
            'title' => $translator->trans('modal.modification.success.done', [], 'exchange_site_proposals'),
            'body' => $translator->trans('modal.modification.success.change_request_taken', ['%credits%' => $entity->getCredits()], 'exchange_site_proposals'),
            'id' => $id,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function refuseAction(Request $request)
    {
        $translator = $this->get('translator');

        $id  = $request->request->get('id');
        $comment  = $request->request->get('comment');

        $entity = $this->existsProposal($id);
        if (!($entity instanceof ExchangeProposition)) {
            return $this->json($entity, Response::HTTP_BAD_REQUEST);
        }

        $result = $this->canRead($entity);
        if (!empty($result)) {
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        $entity->setComments($comment);

        if ($this->applyTransaction($entity, ExchangeProposition::TRANSITION_REFUSE, $resultTransition)) {
            return $resultTransition;
        }

        return $this->json([
            'result' => 'success',
            'body' => $translator->trans('modal.refuse.been_refused', [], 'exchange_site_proposals'),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function modificationAcceptAction(Request $request)
    {
        $translator = $this->get('translator');

        $id  = $request->query->get('id');

        $entity = $this->existsProposal($id);

        $result = $this->canRead($entity);
        if (!empty($result)) {
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        $entity
            ->setModificationStatus(ExchangeProposition::MODIFICATION_STATUS_2)
            ->setViewed(false)
        ;

        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'status' => 'success',
            'message' => $translator->trans('modal.modification.change_article.success', [], 'exchange_site_proposals')
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function modificationRefuseAction(Request $request)
    {
        $translator = $this->get('translator');

        $id  = $request->request->get('id');
        $comment = $request->request->get('comment');
        $final = $request->request->get('final');

        $entity = $this->existsProposal($id);

        $result = $this->canRead($entity);
        if (!empty($result)) {
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        $entity
            ->setModificationStatus(ExchangeProposition::MODIFICATION_STATUS_3)
            ->setModificationRefuseComment($comment)
            ->setViewed(false)
        ;

        if ($final == 1) {
            $entity->setModificationClose(true);
        }

        $this->getDoctrine()->getManager()->flush();

        return $this->json([
            'status' => 'success',
            'body' => $translator->trans('modal.modification_refuse.been_refused', [], 'exchange_site_proposals'),
        ]);
    }

    /**
     * @param ExchangeProposition $entity
     *
     * @return array
     */
    protected function canRead($entity)
    {
        $translator = $this->get('translator');

        if (!$entity->canSellerRead($this->getUser()) && !$this->get('core.service.access_manager')->canManageNetlinkingProject()) {
            return [
                'result' => 'fail',
                'title' => $translator->trans('modal.error', [], 'exchange_site_proposals'),
                'body' => $translator->trans('modal.access_denide', [], 'exchange_site_proposals')
            ];
        }

        return [];
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return JsonResponse
     *
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function impossibleAction(Request $request, $id)
    {
        /** @var ExchangeProposition $exchangeProposition */
        $exchangeProposition = $this->getDoctrine()->getRepository(ExchangeProposition::class)->find($id);

        $translator = $this->get('translator');

        if (is_null($exchangeProposition)) {
            throw new EntityNotFoundException();
        }

        if ($exchangeProposition->getStatus() === ExchangeProposition::STATUS_IMPOSSIBLE) {
            throw new BadRequestHttpException('Entity already impossible');
        }

        $copywritingOrder = $exchangeProposition->getCopywritingOrders();

        $this->denyAccessUnlessGranted("copywritingOrder.".MainVoter::IMPOSSIBLE, $copywritingOrder);

        $exchangeProposition->setImpossibleComment($request->request->get('comment'));

        $copywritingOrderService = $this->get('core.service.copywriting_order');
        $copywritingOrderService->applyTransition($copywritingOrder, CopywritingOrder::TRANSITION_IMPOSSIBLE);

        return new JsonResponse([
            'status' => true,
            'message' => $translator->trans('modal.impossible.success', [], 'exchange_site_proposals'),
            'redirectUrl' => $this->generateUrl('copywriting_order_list', [
                    'status' => [CopywritingOrder::STATUS_PROGRESS, CopywritingOrder::STATUS_DECLINED]
            ]),
        ]);
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     * @param $transition
     * @param null $result
     *
     * @return null|JsonResponse
     */
    private function applyTransaction(ExchangeProposition $exchangeProposition, $transition, &$result = null)
    {
        $exchangePropositionService = $this->get('core.service.exchange_proposition');

        try {
            $exchangePropositionService->applyTransition($exchangeProposition, $transition);
        } catch (WorkflowTransitionEntityException $exception) {
            $result = $this->json(['status' => 'fail', 'message' => $exception->getMessage()], $exception->getStatusCode());
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return Response
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteAction(Request $request, $id)
    {
        /** @var ExchangeProposition $exchangeProposition */
        $exchangeProposition = $this->getDoctrine()->getRepository(ExchangeProposition::class)->find($id);

        $translator = $this->get('translator');

        $this->denyAccessUnlessGranted(
            ExchangePropositionVoter::ACTION_DELETE,
            ExchangeProposition::class,
            $translator->trans('errors.delete_proposal_with_copywriting_order', [], 'exchange_site_proposals')
        );

        if (!in_array($exchangeProposition->getStatus(), [ExchangeProposition::STATUS_REFUSED, ExchangeProposition::STATUS_EXPIRED, ExchangeProposition::STATUS_IMPOSSIBLE])) {
            $details = ['rejection_reason' => $translator->trans('more_details.deleteProposition', [], 'transaction')];
            $this->get('core.service.exchange_proposition')->refund(
                $exchangeProposition,
                new TransactionDescriptionModel('proposal.writing_ereferer_del', ['%url%' => $exchangeProposition->getExchangeSite()->getUrl()]),
                $details
            );
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($exchangeProposition);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json(['status' => 'success']);
        } else {
            return $this->redirectToRoute('user_exchange_site_find', ['tab' => 'proposals']);
        }
    }
}
