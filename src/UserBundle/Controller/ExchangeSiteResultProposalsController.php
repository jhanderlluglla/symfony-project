<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\User;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Pagerfanta\Pagerfanta;
use CoreBundle\Entity\ExchangeProposition;

/**
 * Class ExchangeSiteResultProposalsController
 *
 * @package UserBundle\Controller
 *
 * todo has not finished - need to process status = 50 - modify
 */
class ExchangeSiteResultProposalsController extends ExchangeSiteAbstract
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        $queryBuilder = $this->getDoctrine()->getRepository(ExchangeProposition::class)->getWebmasterProposition($user);

        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 20);

        $pagerfanta = new Pagerfanta(PagerfantaAdapterFactory::getAdapterInstance($queryBuilder));

        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        return $this->render('exchange_site_result_proposals/index.html.twig', [
            'collection' => $pagerfanta,
        ]);
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
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'result' => 'success',
            'title' => $translator->trans('modal.' .$mode. '.title', [], 'exchange_site_result_proposals'),
            'body' => $this->renderView('exchange_site_result_proposals/' .$mode. '.html.twig', [
                'id' => $id,
                'mode' => $mode,
                'entity' => $entity,
            ]),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \CoreBundle\Exceptions\UnknownNotificationName
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

        $em = $this->getDoctrine()->getManager();
        $entity
            ->setModificationComment($comment)
            ->setModificationStatus(ExchangeProposition::MODIFICATION_STATUS_1)
        ;

        $em->persist($entity);
        $em->flush();

        $replace = [
            '%link%' => $this->generateUrl('user_exchange_site_proposals', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $serviceMailer = $this->get('core.service.mailer');
        $serviceMailer->sendToUser(User::NOTIFICATION_CHANGE_PROPOSAL, $entity->getExchangeSite()->getUser(), $replace);

        return $this->json([
            'result' => 'success',
            'title' => $translator->trans('modal.modification.success.done', [], 'exchange_site_result_proposals'),
            'body' => $translator->trans('modal.modification.success.change_request_taken', [], 'exchange_site_result_proposals'),
            'message' => $translator->trans('modal.modification.success.message', [], 'exchange_site_result_proposals'),
            'id' => $id,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function voteAction(Request $request)
    {
        $translator = $this->get('translator');

        $id  = $request->request->get('id');
        $comment  = $request->request->get('comment');
        $rating  = $request->request->get('rating');

        $entity = $this->existsProposal($id);
        if (!($entity instanceof ExchangeProposition)) {
            return $this->json($entity, Response::HTTP_BAD_REQUEST);
        }

        $result = $this->canRead($entity);
        if (!empty($result)) {
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();
        $entity
            ->setRateStars($rating)
            ->setRateComment($comment)
        ;

        $em->persist($entity);
        $em->flush();

        return $this->json([
            'result' => 'success',
            'title' => $translator->trans('modal.vote.success.done', [], 'exchange_site_result_proposals'),
            'body' => $translator->trans('modal.vote.success.message', [], 'exchange_site_result_proposals'),
            'id' => $id,
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

        if (!$entity->canBuyerRead($this->getUser())) {
            return [
                'result' => 'fail',
                'title' => $translator->trans('modal.error', [], 'exchange_site_proposals'),
                'body' => $translator->trans('modal.access_denide', [], 'exchange_site_proposals')
            ];
        }

        return [];
    }
}