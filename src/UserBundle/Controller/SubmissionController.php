<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\Job;
use CoreBundle\Entity\User;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Repository\JobRepository;
use Doctrine\ORM\EntityNotFoundException;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SubmissionController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws EntityNotFoundException
     */
    public function indexAction(Request $request)
    {
        if (!$this->get('core.service.access_manager')->canManageNetlinkingProject()) {
            throw new AccessDeniedHttpException();
        }
        /** @var JobRepository $jobRepository */
        $jobRepository = $this->getDoctrine()->getRepository(Job::class);
        $filters = [];

        $page = $request->query->get('page', 1);
        $userId = $request->query->get('user-id');

        if ($userId) {
            $user = $this->getDoctrine()->getRepository(User::class)->find($userId);
            if (is_null($user)) {
                throw new EntityNotFoundException();
            }
            if ($user->isWriterNetlinking()) {
                $filters['affectedToUser'] = $user;
            } else {
                $filters['user'] = $user;
            }
        }

        $builder = $jobRepository->getJobsForAdmin($filters);

        return $this->render('submission/index.html.twig', [
            'collection' => PagerfantaAdapterFactory::getPagerfantaInstance($builder, $page),
        ]);
    }
}
