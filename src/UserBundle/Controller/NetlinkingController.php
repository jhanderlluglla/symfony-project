<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Exceptions\NotEnoughMoneyDetailException;
use CoreBundle\Exceptions\NotEnoughMoneyException;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Repository\ScheduleTaskRepository;
use CoreBundle\Services\CalculatorNetlinkingPrice;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\User;
use CoreBundle\Repository\DirectoryBacklinksRepository;
use CoreBundle\Repository\JobRepository;
use CoreBundle\Repository\NetlinkingProjectRepository;

use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use UserBundle\Entity\NetlinkingFlowEntity;
use UserBundle\Form\Netlinking\CopyWriterSelectType;
use UserBundle\Form\Netlinking\CreateNetlinkingFlow;
use UserBundle\Form\Netlinking\EditFormType;
use UserBundle\Security\NetlinkingProjectVoter;
use UserBundle\Services\NetlinkingService;

use Doctrine\ORM\EntityNotFoundException;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Class NetlinkingController
 *
 * @package UserBundle\Controller
 */
class NetlinkingController extends Controller
{

    public const ACTION_JOB_IMPOSSIBLE = 'job_impossible';
    public const ACTION_JOB_COMPLETE = 'job_complete';

    /**
     * @param Request $request
     * @param string $status
     *
     * @return Response
     * @throws EntityNotFoundException
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     */
    public function indexAction(Request $request, $status = 'current')
    {

        /** @var User $user */
        $user = $this->getUser();
        if ($user->isWriterAdmin() && !$this->get('core.service.access_manager')->canManageNetlinkingProject()) {
            throw new AccessDeniedHttpException();
        }

        /** @var NetlinkingProjectRepository $netLinkingRepository */
        $netLinkingRepository = $this->getDoctrine()->getRepository(NetlinkingProject::class);

        /** @var DirectoryBacklinksRepository $backlinksRepository */
        $backlinksRepository = $this->getDoctrine()->getRepository(DirectoryBacklinks::class);

        /** @var ScheduleTaskRepository $scheduleRepository */
        $scheduleRepository = $this->getDoctrine()->getRepository(ScheduleTask::class);

        $filter = $this->getFilter($status);
        $filter['query'] = $request->get('query');

        $userId = $request->get('user-id');
        $queryBuilder = null;
        if ($userId) {
            /** @var User $affectedUser */
            $affectedUser = $this->getDoctrine()->getRepository(User::class)->find($userId);
            if (is_null($affectedUser)) {
                throw new EntityNotFoundException("User with $userId not found");
            }
            if ($affectedUser->isWriterNetlinking()) {
                $filter['affected_user'] = $affectedUser;
            } else {
                $filter['user'] = $affectedUser;
            }
        }

        $oldestStartedAt = [];
        if (isset($filter['affected_user'])) {
            list($queryBuilder, $oldestStartedAt) = $netLinkingRepository->getNetlinkingProjectForWriter($filter['affected_user'], $filter);
        } else {
            $queryBuilder = $netLinkingRepository->filter($filter);
        }

        $page = $request->query->get('page', 1);

        $pagerfanta = PagerfantaAdapterFactory::getPagerfantaInstance($queryBuilder, $page);
        $minDatedScheduleTasks = $scheduleRepository->getMinDatedScheduleTasksByProjects($pagerfanta->getIterator()->getArrayCopy());

        return $this->render('netlinking/index.html.twig', [
            'collection' => $pagerfanta,
            'oldestStartedAt' => $oldestStartedAt,
            'status' => $status,
            'scheduleTasksMinDated' => $minDatedScheduleTasks,
            'statistics' => $backlinksRepository->getStatisticsByProjects($pagerfanta->getCurrentPageResults()),
            'tasksStatistic' => $scheduleRepository->getTaskStatisticsByProjects($pagerfanta->getCurrentPageResults()),
            'changeWriterForm' => $this->createForm(CopyWriterSelectType::class),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();

        $directoryList = $this->getDoctrine()->getRepository(DirectoriesList::class)->getNotEmptyDirectoriesList(['user' => $user]);

        if (!$directoryList->getQuery()->getResult()) {
            return $this->render('netlinking/add.html.twig', ['formLock' => true]);
        }

        $netlinkingFlowEntity = new NetlinkingFlowEntity();

        $options = [
            'method' => Request::METHOD_POST,
            'user' => $user,
        ];

        /** @var CreateNetlinkingFlow $flow */
        $flow = $this->get('user.form.flow.create_netlinking');
        $flow->setGenericFormOptions($options);
        $flow->bind($netlinkingFlowEntity);

        $errors = [];
        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if (empty($netlinkingFlowEntity->getUrls())) {
                $errors['urls'] = $this->get('translator')->trans('form.error_urls', [], 'netlinking');
            }

            if ($flow->nextStep()) {
                $form = $flow->createForm();
            } else {
                /** @var NetlinkingService $netlinkingService */
                $netlinkingService = $this->get('user.netlinking');

                $flashes = [];
                
                $netlinkingService->createNetlinkingProject($netlinkingFlowEntity, $user, $errors, $flashes);

                foreach ($flashes as $flash) {
                    $this->addFlash(
                        $flash['type'],
                        $flash['message']
                    );
                }

                if (empty($errors)) {
                    $flow->reset();

                    return $this->redirectToRoute(
                        'netlinking_status',
                        ['status' => $netlinkingFlowEntity->getDirectoryList()->getContainsType() === DirectoriesList::CONTAINS_ONLY_BLOG ? 'current' : 'waiting']
                    );
                }
            }
        }

        $currentStepNumber = $flow->getCurrentStepNumber();

        if (!empty($errors)) {
            $currentStepNumber = 1;
            $form = $flow->getFormForStep($currentStepNumber, $options);

            foreach ($errors as $key => $message) {
                if ($form->has($key)) {
                    $form->get($key)->addError(new FormError($message));
                }
            }
        }

        $firstStepView = $flow->getFormForStep($flow->getFirstStepNumber(), $options)->createView();
        if ($currentStepNumber === $flow->getFirstStepNumber()) {
            $formView = $firstStepView;
        } else {
            $formView = $form->createView();
        }

        return $this->render('netlinking/add.html.twig', [
            'firstStep' => $firstStepView,
            'form' => $formView,
            'currentStepNumber' => $currentStepNumber,
            'entity' => $netlinkingFlowEntity,
            'mode' => 'add',
            'status' => null,
            'errors' => $errors,
        ]);
    }

    /**
     * @param Request $request
     * @param string $status
     * @param integer $id
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function editAction(Request $request, $status, $id)
    {
        /** @var NetlinkingProject $netlinkingProject */
        $netlinkingProject = $this->getDoctrine()->getRepository(NetlinkingProject::class)->find($id);

        if (is_null($netlinkingProject)) {
            throw new EntityNotFoundException();
        }

        $this->denyAccessUnlessGranted('edit', $netlinkingProject);

        /** @var User $user */
        $user = $this->getUser();

        $options = [
            'method' => Request::METHOD_PUT,
            'user' => $user,
            'status' => $status,
        ];

        $netlinkingFlowEntity = new NetlinkingFlowEntity();
        $netlinkingFlowEntity->fill($netlinkingProject);

        $errors = [];

        $form = $this->createForm(EditFormType::class, $netlinkingFlowEntity, $options);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $anchors = $netlinkingFlowEntity->getUrlAnchors()[0]->getAnchors();
            $comment = $netlinkingFlowEntity->getComment();

            $netlinkingService = $this->get('user.netlinking');
            $netlinkingService->updateNetlinkingProject($netlinkingProject, $comment, $anchors);

            return $this->redirectToRoute('netlinking_status', ['status' => $status]);
        }

        return $this->render('netlinking/edit.html.twig', [
            'form' => $form->createView(),
            'entity' => $netlinkingFlowEntity,
            'mode' => 'edit',
            'status' => $status,
            'errors' => $errors,
        ]);
    }


    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function assignMassAction(Request $request)
    {
        $translator = $this->get('translator');

        try {
            $ids = $request->request->get('projectIds');
            $writerId = $request->request->get('writerId');
            $writers = [];

            $netlinkingProjects = $this->getDoctrine()->getRepository(NetlinkingProject::class)->findBy(['id' => $ids]);
            /** @var User $affectedToUser */
            $affectedToUser = $this->getDoctrine()->getRepository(User::class)->find($writerId);
            if (count($netlinkingProjects) === 0 || is_null($affectedToUser)) {
                throw new EntityNotFoundException();
            }

            $netlinkingSchedule = $this->get('user.netlinking_schedule');
            /** @var NetlinkingProject $netlinkingProject */
            foreach ($netlinkingProjects as $netlinkingProject) {
                $writers[$netlinkingProject->getId()] = [
                    'fullName' => $affectedToUser->getFullName(),
                    'editWriterUrl' => $this->generateUrl('user_edit', ['id' => $affectedToUser->getId()]),
                ];
                if ($netlinkingProject->getAffectedToUser() === null) {
                    $netlinkingSchedule->createSchedule($netlinkingProject);
                }
            }

            $this->getDoctrine()->getRepository(NetlinkingProject::class)->assignMass($ids, $affectedToUser, $this->getUser());
        } catch (\Exception $e) {
            return $this->json(
                [
                    'result' => 'fail',
                    'title' => $translator->trans('modal.error', [], 'netlinking'),
                    'body' => $translator->trans('modal.project_error', [], 'netlinking'),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json([
            'result' => 'success',
            'message' => $translator->trans('modal.message.change_writer', [], 'netlinking'),
            'writers' => $writers,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteMassAction(Request $request)
    {
        $translator = $this->get('translator');

        try {
            $ids = $request->request->get('ids');
            if (!empty($ids) && is_array($ids)) {
                $this->getDoctrine()->getRepository(NetlinkingProject::class)->deleteMass($ids);
            }
        } catch (\Exception $e) {
            return $this->json(
                [
                    'result' => 'fail',
                    'title' => $translator->trans('modal.error', [], 'netlinking'),
                    'body' => $translator->trans('modal.project_error', [], 'netlinking'),
                ], Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json(
            [
                'result' => 'success',
                'title' => '',
                'body' => '',
            ]
        );
    }

    /**
     * @param Request $request
     * @param string  $status
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $status, $id)
    {
        $netlinkingProject = $this->getDoctrine()->getRepository(NetlinkingProject::class)->find($id);

        if (is_null($netlinkingProject)) {
            throw new EntityNotFoundException();
        }

        $this->denyAccessUnlessGranted('delete', $netlinkingProject);

        $em = $this->getDoctrine()->getManager();
        $em->remove($netlinkingProject);
        $em->flush();

        return $this->redirectToRoute('netlinking_status', ['status' => $status]);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function startAction(Request $request, $id)
    {
        $netlinkingProject = $this->getDoctrine()->getRepository(NetlinkingProject::class)->find($id);

        if (is_null($netlinkingProject)) {
            throw new EntityNotFoundException();
        }

        $this->denyAccessUnlessGranted('view', $netlinkingProject);

        /** @var NetlinkingService $netlinkingService */
        $netlinkingService = $this->get('user.netlinking');

        if (!$netlinkingService->start($netlinkingProject)) {
            $this->addFlash(
                'error',
                $netlinkingService->getErrorMessage()
            );
        }

        $parameters = ['status' => 'nostart'];

        /** @var User $user */
        $user = $this->getUser();
        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $parameters = ['status' => 'waiting'];
        }

        return $this->redirectToRoute('netlinking_status', $parameters);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function inprogressAction(Request $request, $id)
    {
        $netlinkingProject = $this->getDoctrine()->getRepository(NetlinkingProject::class)->find($id);

        if (is_null($netlinkingProject)) {
            throw new EntityNotFoundException();
        }

        $copyWriterUser = null;
        if ($request->isMethod(Request::METHOD_POST)) {
            $copyWriterId = $request->request->get('copyWriter');

            $copyWriterUser = $this->getDoctrine()->getRepository(User::class)->find($copyWriterId);
        }

        $netlinkingService = $this->get('user.netlinking');
        $netlinkingService->inProgress($netlinkingProject, $copyWriterUser);

        return $this->redirectToRoute('netlinking_all');
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function stopAction(Request $request, $id)
    {
        $netlinkingProject = $this->getDoctrine()->getRepository(NetlinkingProject::class)->find($id);

        if (is_null($netlinkingProject)) {
            throw new EntityNotFoundException();
        }

        $this->denyAccessUnlessGranted('view', $netlinkingProject);

        /** @var NetlinkingService $netlinkingService */
        $netlinkingService = $this->get('user.netlinking');
        $netlinkingService->stop($netlinkingProject);

        return $this->redirectToRoute('netlinking_status', ['status' => 'current']);
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function detailAction(Request $request, $id)
    {
        /** @var NetlinkingProject $netlinkingProject */
        $netlinkingProject = $this->getDoctrine()->getRepository(NetlinkingProject::class)->find($id);

        if (is_null($netlinkingProject)) {
            throw new EntityNotFoundException();
        }

        $this->denyAccessUnlessGranted('view', $netlinkingProject);

        /** @var User $user */
        $user = $this->getUser();

        $tasksBuilder = $this->getDoctrine()->getRepository(ScheduleTask::class)->getTasks($netlinkingProject, $user);
        $tasks = $tasksBuilder->getQuery()->getResult();

        if ($request->isXmlHttpRequest()) {
            switch ($request->get('tab-type')) {
                case 'directories':
                    $tasks = array_filter($tasks, function ($element) {
                        return $element->getDirectory() !== null;
                    });
                    break;
                case 'blogs':
                    $tasks = array_filter($tasks, function ($element) {
                        return $element->getExchangeSite() !== null;
                    });
                    break;
                case 'impossible':
                    $tasks = array_filter($tasks, function ($element) {
                        $job = $element->getJob();
                        return $job !== null && $job->getStatus() === Job::STATUS_IMPOSSIBLE;
                    });
                    break;
                case 'summary':
                    $tasks = array_filter($tasks, function ($element) {
                        return $element->getJob() !== null;
                    });
                    break;
                case 'found':
                    $tasks = array_filter($tasks, function ($element) {
                        $job = $element->getJob();
                        if ($job !== null && $job->getDirectoryBacklink() !== null) {
                            return $job->getDirectoryBacklink()->getStatus() === DirectoryBacklinks::STATUS_FOUND;
                        }
                        return false;
                    });
                    break;
                case 'awaiting':
                    $tasks = array_filter($tasks, function ($element) {
                        $job = $element->getJob();
                        if ($job !== null && $job->getDirectoryBacklink() !== null) {
                            return $job->getDirectoryBacklink()->getStatus() === DirectoryBacklinks::STATUS_NOT_FOUND_YET;
                        }
                        return false;
                    });
                    break;
                case 'notFound':
                    $tasks = array_filter($tasks, function ($element) {
                        $job = $element->getJob();
                        if ($job !== null && $job->getDirectoryBacklink() !== null) {
                            return $job->getDirectoryBacklink()->getStatus() === DirectoryBacklinks::STATUS_NOT_FOUND;
                        }
                        return false;
                    });
                    break;
                default:
                    $tasks = [];
            }

            return $this->render('netlinking/summary_task_table.html.twig', ['tasks' => $tasks]);
        }

        $currentCost = $this->getDoctrine()->getRepository(Job::class)->getCurrentCost($netlinkingProject);
        $remainingCost = 0;

        /** @var CalculatorNetlinkingPrice $calculatorNetlinkingPrice */
        $calculatorNetlinkingPrice = $this->get('core.service.calculator_netlinking_price');

        foreach ($tasks as $task) {
            if ($task->getJob() === null) {
                $remainingCost += $calculatorNetlinkingPrice->getWebmasterCost($task,$netlinkingProject->getDirectoryList()->getWordsCount());
            }
        }

        /** @var DirectoryBacklinksRepository $directoryBacklinkRepository */
        $directoryBacklinkRepository = $this->getDoctrine()->getRepository(DirectoryBacklinks::class);

        $statistics = $directoryBacklinkRepository->getStatisticsByProjects([$netlinkingProject]);
        return $this->render('netlinking/detail.html.twig', [
            'netlinkingProject' => $netlinkingProject,
            'tasks' => $tasks,
            'currentCost' => $currentCost,
            'remainingCost' => $remainingCost,
            'calculatorNetlinkingPrice' => $calculatorNetlinkingPrice,
            'statistics' => $statistics,
        ]);
    }

    /**
     * @param Request $request
     * @param integer $taskId
     *
     * @return Response
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function detailWriterAction(Request $request, $taskId)
    {
        $translator = $this->get('translator');
        /** @var ScheduleTask $scheduleTask */
        $scheduleTask = $this->getDoctrine()->getRepository(ScheduleTask::class)->find($taskId);
        $directory = $scheduleTask->getDirectory();
        $netlinkingProject = $scheduleTask->getNetlinkingProject();

        if (is_null($scheduleTask)) {
            throw new BadRequestHttpException($translator->trans('modal.task_error', [], 'netlinking'));
        }

        $this->denyAccessUnlessGranted('view', $netlinkingProject);

        if ($scheduleTask->getDirectory()) {
            $netlinkingService = $this->get('user.netlinking');
            $data = $netlinkingService->detailWriter($scheduleTask);

            return $this->json(
                [
                    'status' => true,
                    'title' => $translator->trans('modal.submission_detail', [], 'netlinking'),
                    'body' => $this->renderView(
                        'netlinking/modal/detail_writer.html.twig',
                        [
                            'netlinking' => $netlinkingProject,
                            'directory' => $directory,
                            'data' => $data,
                            'job' => $scheduleTask->getJob() ? $scheduleTask->getJob() : null,
                            'scheduleTaskId' => $scheduleTask->getId()
                        ]
                    ),
                ]
            );
        } else {
            return $this->json(
                [
                    'status' => true,
                    'title' => $translator->trans('modal.submission_detail', [], 'netlinking'),
                    'body' => $this->renderView('netlinking/modal/detail_copywriting_order.html.twig', [
                        'order' => $scheduleTask->getJob()->getExchangeProposition()->getCopywritingOrders()
                    ])
                ]
            );
        }
    }

    /**
     * @param Request $request
     * @param $jobId
     * @param $type
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function jobCompleteImpossibleAction(Request $request, $jobId, $type)
    {
        $translator = $this->get('translator');

        /** @var Job $job */
        $job = $this->getDoctrine()->getRepository(Job::class)->find($jobId);

        if (is_null($job)) {
            throw new NotFoundHttpException($translator->trans('modal.task_error', [], 'netlinking'));
        }

        $this->denyAccessUnlessGranted('view', $job->getNetlinkingProject());

        $comment = $request->request->get('comment');

        if ($type === self::ACTION_JOB_COMPLETE) {
            try {
                $this->container->get('core.service.job')->completeJob($job, $comment);
            } catch (NotEnoughMoneyDetailException $e) {
                throw new NotEnoughMoneyException($translator->trans('modal.customer_not_enough_funds', [], 'netlinking'));
            }
        } else {
            $this->container->get('core.service.job')->impossibleJob($job, $comment);
        }

        return $job->getCostWriter();
    }

    /**
     * @param Request $request
     * @param $jobId
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function jobCompleteAction(Request $request, $jobId)
    {

        $costWriter = $this->jobCompleteImpossibleAction($request, $jobId, self::ACTION_JOB_COMPLETE);

        return new JsonResponse([
            'status' => true,
            'message' => $this->get('translator')->trans('modal.comment.submission_possible', [], 'netlinking'),
            'cost' => $costWriter,
        ]);
    }

    /**
     * @param Request $request
     * @param int $scheduleTaskId
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function jobImpossibleAction(Request $request, $scheduleTaskId)
    {
        /** @var ScheduleTask $scheduleTask */
        $scheduleTask = $this->getDoctrine()->getRepository(ScheduleTask::class)->find($scheduleTaskId);

        if (!$scheduleTask) {
            throw new NotFoundHttpException('Task #' . $scheduleTaskId . ' not found');
        }

        if (!$scheduleTask->getJob()) {
            $jobService = $this->container->get('core.service.job');
            $job = $jobService->createJobForDirectory($scheduleTask);
            $job->setAffectedAt($job->getNetlinkingProject()->getAffectedAt());
            $job->setAffectedToUser($job->getNetlinkingProject()->getAffectedToUser());

            $em = $this->get('doctrine.orm.entity_manager');
            $em->persist($job);
            $em->flush();
        } else {
            $job = $scheduleTask->getJob();
        }

        $this->jobCompleteImpossibleAction($request, $job->getId(), self::ACTION_JOB_IMPOSSIBLE);

        return new JsonResponse([
            'status' => true,
            'message' => $this->get('translator')->trans('modal.comment.submission_impossible', [], 'netlinking'),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function checkUrlAction(Request $request)
    {
        $result = [
            'result' => 'success',
            'message' => '',
        ];

        $url = $request->query->get('url');

        $netlinkingProject = $this->getDoctrine()->getRepository(NetlinkingProject::class)->findOneBy(['url' => rtrim($url, '/')]);
        if (!is_null($netlinkingProject)) {
            $result = [
                'result' => 'fail',
                'message' => $this->get('translator')->trans('form.url_exists', ['%%url%%' => $url], 'netlinking'),
            ];
        }

        return $this->json($result);
    }

    /**
     * @param string $status
     *
     * @return array
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     */
    private function getFilter($status)
    {
        /** @var User $user */
        $user = $this->getUser();

        $filter = [];

        if ($this->get('core.service.access_manager')->canManageNetlinkingProject()) {
            $filter = [
                'status' => $status,
            ];

            switch ($status) {
                case 'current':
                    $filter['user_role'] = User::ROLE_WEBMASTER_STRING;
                    break;
            }
        } elseif ($user->hasRole(User::ROLE_WEBMASTER)) {
            $filter = [
                'status' => $status,
                'user' => $user,
            ];

            switch ($status) {
                case 'current':
                    $filter['user_role'] = User::ROLE_WEBMASTER_STRING;
                    break;
            }
        } elseif ($user->hasRole(User::ROLE_WRITER) || $user->hasRole(User::ROLE_WRITER_NETLINKING)) {

            if ($status == 'getnew') {
                $count = $this->getDoctrine()->getRepository(NetlinkingProject::class)->filter([
                    'affected_user' => $user,
                    'status' => NetlinkingProject::STATUS_IN_PROGRESS,
                ], true);

                if ($count > 0) {
                    $this->createAccessDeniedException();
                }
            }

            $filter = [
                'status' => $status,
                'user_role' => User::ROLE_WRITER_STRING,
            ];

            switch ($status) {
                case 'current':
                    $filter['affected_user'] = $user;
                    break;
            }
        }

        return $filter;
    }

    /**
     * @param Request $request
     * @param $id
     * @return Response
     * @throws EntityNotFoundException
     */
    public function reportAction(Request $request, $id)
    {
        /** @var NetlinkingProjectRepository $netlinkingProjectRepository */
        $netlinkingProjectRepository = $this->getDoctrine()->getRepository(NetlinkingProject::class);
        /** @var NetlinkingProject $netlinkingProject */
        $netlinkingProject = $netlinkingProjectRepository->find($id);

        if(is_null($netlinkingProject)){
            throw new EntityNotFoundException("Project with $id don't exist");
        }
        $this->denyAccessUnlessGranted('view', $netlinkingProject);

        /** @var JobRepository $jobRepository */
        $jobRepository = $this->getDoctrine()->getRepository(Job::class);
        $jobs = $jobRepository->getJobsByNetlinkingProject($netlinkingProject);

        return $this->render('netlinking/report.html.twig',[
            'collection' => $jobs,
            'project' => $netlinkingProject,
        ]);
    }

    /**
     * @param Request $request
     * @param $jobId
     *
     * @return JsonResponse
     *
     * @throws OptimisticLockException
     */
    public function rejectAction(Request $request, $jobId)
    {
        if (!$this->get('core.service.access_manager')->canManageNetlinkingProject()) {
            throw new AccessDeniedHttpException();
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            /** @var JobRepository $jobRepository */
            $jobRepository = $this->getDoctrine()->getRepository(Job::class);
            /** @var Job $job */
            $job = $jobRepository->find($jobId);

            if (is_null($job)) {
                throw new NotFoundHttpException();
            }

            $this->get('core.service.job')->rejectJob($job, $request->get('comment'));

            return $this->json([
                'status' => true,
                'message' => $this->container->get('translator')->trans('job_rejected', [], 'netlinking'),
            ]);
        }

        $form = $form = $this->renderView('submission/reject_form.html.twig');

        return new JsonResponse([
            'status' => true,
            'title' => $this->get('translator')->trans('table.reject_task', [], 'submission'),
            'body' => $form
        ]);
    }

    /**
     * @param Request $request
     * @param $jobId
     * @return JsonResponse
     * @throws EntityNotFoundException
     */
    public function ratingAction(Request $request, $jobId)
    {
        /** @var Job $job */
        $job = $this->getDoctrine()->getRepository(Job::class)->find($jobId);

        if(is_null($job)){
            throw new EntityNotFoundException("Job with $jobId not found");
        }

        $job->setRating($request->get('rating') === 'true' ? true : false);
        $job->setComment($request->get('comment'));
        $job->setRatingAddedAt(new \DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->persist($job);
        $em->flush();

        $translator = $this->get('translator');
        return $this->json([
            'result' => 'success',
            'message' => $translator->trans('modal.feedback_accepted', [], 'netlinking'),
        ]);
    }

    /**
     * @param int $scheduleTaskId
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function doScheduleTaskAction($scheduleTaskId)
    {
        /** @var ScheduleTask $scheduleTask */
        $scheduleTask = $this->getDoctrine()->getRepository(ScheduleTask::class)->find($scheduleTaskId);

        if (!$scheduleTask) {
            throw new NotFoundHttpException('Task #' . $scheduleTaskId . ' not found');
        }

        $translator = $this->get('translator');
        $jobService = $this->container->get('core.service.job');
        $transactionService = $this->container->get('core.service.transaction');
        $em = $this->container->get('doctrine.orm.entity_manager');

        $job = $jobService->createJobForDirectory($scheduleTask);

        try {
            $transactionService->checkMoney($job->getNetlinkingProject()->getUser(), $job->getCostWebmaster());

            $em->persist($job);
            $em->flush();

            $job->setAffectedAt($job->getNetlinkingProject()->getAffectedAt());

            $jobService->takeToWorkJob($job, $this->getUser());
        } catch (NotEnoughMoneyDetailException $e) {
            throw new NotEnoughMoneyException($translator->trans('modal.customer_not_enough_funds', [], 'netlinking'));
        }

        return $this->json([
            'status' => true,
            'message' => $this->get('translator')->trans('modal.take_to_work_success', [], 'netlinking'),
            'jobId' => $job->getId(),
        ]);
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return Response
     */
    public function evolutionAction(Request $request, $id)
    {
        $netlinkingProject = $this->getDoctrine()->getRepository(NetlinkingProject::class)->find($id);

        $this->denyAccessUnlessGranted(NetlinkingProjectVoter::VIEW, $netlinkingProject);

        return $this->render('netlinking/evolution.html.twig', [
            'netlinkingProject' => $netlinkingProject,
        ]);
    }
}
