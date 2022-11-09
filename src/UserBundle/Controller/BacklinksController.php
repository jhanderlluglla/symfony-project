<?php

namespace UserBundle\Controller;

use CoreBundle\Repository\DirectoryBacklinksRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\User;
use UserBundle\Security\DirectoryBacklinksVoter;

/**
 * Class BacklinksAction
 *
 * @package UserBundle\Controller
 */
class BacklinksController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function backlinksAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->denyAccessUnlessGranted(DirectoryBacklinks::class . '.' . DirectoryBacklinksVoter::ACTION_SHOW, null);

        /** @var DirectoryBacklinksRepository $directoryBacklinksRepository */
        $directoryBacklinksRepository = $this->getDoctrine()->getRepository(DirectoryBacklinks::class);

        $queryBuilder = $directoryBacklinksRepository->findByStatus($user, DirectoryBacklinks::STATUS_NOT_FOUND_YET);

        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 20);

        $adapter = new DoctrineORMAdapter($queryBuilder);

        $pagerfanta = new Pagerfanta($adapter);

        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        return $this->render('backlinks/index.html.twig', [
            'collection' => $pagerfanta,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateStatusAction(Request $request)
    {
        $translator = $this->get('translator');

        $backlinkId = $request->request->get('backlinkId');
        $backlinkUrl = $request->request->get('backlink');
        $status = $request->request->get('status');

        /** @var DirectoryBacklinks $directoryBacklink */
        $directoryBacklink = $this->getDoctrine()->getRepository(DirectoryBacklinks::class)->find($backlinkId);
        if (is_null($directoryBacklink)) {
            return $this->json(
                [
                    'result' => 'fail',
                    'title' => $translator->trans('modal.error', [], 'netlinking'),
                    'body' => $translator->trans('modal.directory_backlink_error', [], 'netlinking'),
                ], Response::HTTP_BAD_REQUEST
            );
        }
        $netlinkingProject = $directoryBacklink->getJob()->getNetlinkingProject();

        $this->denyAccessUnlessGranted('view', $netlinkingProject);

        $em = $this->getDoctrine()->getManager();
        $directoryBacklink
            ->setStatus($status)
            ->setStatusType(DirectoryBacklinks::STATUS_TYPE_MANUALLY)
            ->setBacklink($backlinkUrl)
            ->setDateFound(new \DateTime())
        ;
        $em->persist($directoryBacklink);
        $em->flush();

        $mailer = $this->get('core.service.mailer');

        $replace = [
            '%url%' => $directoryBacklink->getJob()->getScheduleTask()->getTaskUrl(),
            '%backlink%' => $backlinkUrl,
        ];

        $mailer->sendToUser(User::NOTIFICATION_BACKLINK_FOUND, $netlinkingProject->getUser(), $replace);

        return $this->json([
            'result' => 'success',
            'title' => $translator->trans('table.update_success', [], 'backlinks'),
            'body' => $translator->trans('table.action_performed_successfully', [], 'backlinks'),
        ]);
    }
}
