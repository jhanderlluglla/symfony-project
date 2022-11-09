<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Entity\StaticPage;
use CoreBundle\Entity\User;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Repository\CopywritingOrderRepository;
use CoreBundle\Repository\DirectoryBacklinksRepository;
use CoreBundle\Repository\NetlinkingProjectRepository;
use CoreBundle\Repository\ScheduleTaskRepository;
use CoreBundle\Services\AccessManager;
use Doctrine\Common\Collections\Criteria;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Form\CopywritingAssignOrderType;
use UserBundle\Form\Netlinking\CopyWriterSelectType;

/**
 * Class DashboardController
 *
 * @package UserBundle\Controller
 */
class DashboardController extends Controller
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per-page', 20);

        /** @var DirectoryBacklinksRepository $backlinksRepository */
        $backlinksRepository = $this->getDoctrine()->getRepository(DirectoryBacklinks::class);

        /** @var NetlinkingProjectRepository $netLinkingRepository */
        $netLinkingRepository = $this->getDoctrine()->getRepository(NetlinkingProject::class);

        /** @var ScheduleTaskRepository $scheduleRepository */
        $scheduleRepository = $this->getDoctrine()->getRepository(ScheduleTask::class);

        /** @var CopywritingOrderRepository $copywritingOrderRepository */
        $copywritingOrderRepository = $this->getDoctrine()->getRepository(CopywritingOrder::class);

        /** @var AccessManager $accessManager */
        $accessManager = $this->get('core.service.access_manager');

        /** @var User $user */
        $user = $this->getUser();

        $copywritingFilters = [];
        $netlinkingFilters = [];
        $similarSites = [];
        if ($user->isWebmaster()) {
            $copywritingFilters = [
                'status' => CopywritingOrder::STATUS_COMPLETED,
                'consulted' => false,
                'customer' => $user,
            ];
            $netlinkingFilters = [
                'status' => NetlinkingProject::STATUS_IN_PROGRESS,
                'user' => $user,
            ];
            $similarSites = $this->get('core.service.filter')->getDealsForUser($user);
        }
        if ($user->isWriterCopywriting() || $user->isWriterNetlinking()) {
            $copywritingFilters = [
                'exclude_status' => [
                    CopywritingOrder::STATUS_COMPLETED,
                    CopywritingOrder::STATUS_SUBMITTED_TO_WEBMASTER,
                    CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN
                ],
                'copywriter' => $user,
            ];
            $netlinkingFilters = [
                'status' => 'current',
                'user_role' => User::ROLE_WRITER_STRING,
                'affected_user' => $user,
            ];
        }

        if ($accessManager->canManageCopywritingProject()) {
            $copywritingFilters = [
                'exclude_status' => [CopywritingOrder::STATUS_WAITING, CopywritingOrder::STATUS_COMPLETED],
            ];
        }
        if ($accessManager->canManageNetlinkingProject()) {
            $netlinkingFilters = [
                'status' => NetlinkingProject::STATUS_IN_PROGRESS,
            ];
        }

        $netlinkingProjects = [];
        $oldestStartedAt = [];

        if (!$user->hasRole(User::ROLE_WRITER_COPYWRITING) && !($user->hasRole(User::ROLE_WRITER_ADMIN) && !$accessManager->canManageNetlinkingProject())) {
            if ($user->isWriterNetlinking()) {
                list($queryBuilder, $oldestStartedAt) = $netLinkingRepository->getNetlinkingProjectForWriter($netlinkingFilters['affected_user'], $netlinkingFilters);
                $netlinkingProjects = $queryBuilder->getQuery()->getResult();
            } else {
                $netlinkingProjects = $netLinkingRepository->filter($netlinkingFilters, false, 'affectedAt')->getQuery()->getResult();
            }
        }

        $togetherPagerfanta = [];
        $copywritingProjectsPagerfanta = null;
        $netlinkingPagerfanta = [];

        if ($user->isWebmaster()) {
            $copywritingProjectsPagerfanta = new Pagerfanta(PagerfantaAdapterFactory::getAdapterInstance($copywritingOrderRepository->filter($copywritingFilters)));
            $copywritingProjectsPagerfanta
                ->setMaxPerPage($perPage)
                ->setCurrentPage($page)
            ;
            $adapter = new ArrayAdapter($netlinkingProjects);
            $netlinkingPagerfanta = new Pagerfanta($adapter);
            $netlinkingPagerfanta
                ->setMaxPerPage($perPage)
                ->setCurrentPage($page)
            ;
            $latestNetlinkingProjects = $netlinkingPagerfanta->getCurrentPageResults();
            $netlinkingProjectsStat = $latestNetlinkingProjects;
        } else {
            $copywritingProjects = [];
            if (!$user->hasRole(User::ROLE_WRITER_NETLINKING) && !($user->hasRole(User::ROLE_WRITER_ADMIN) && !$accessManager->canManageCopywritingProject())) {
                $copywritingProjects = $copywritingOrderRepository->filter($copywritingFilters, false, ['takenAt' => 'asc'])->getQuery()->getResult();
            }
            $netlinkingAndCopywriting = $this->mergeNetlinkingAndCopywriting($netlinkingProjects, $copywritingProjects);
            $adapter = new ArrayAdapter($netlinkingAndCopywriting);
            $togetherPagerfanta = new Pagerfanta($adapter);
            $togetherPagerfanta
                ->setMaxPerPage($perPage)
                ->setCurrentPage($page)
            ;
            $netlinkingProjectsStat = $netlinkingProjects;
        }

        $statistics = $backlinksRepository->getStatisticsByProjects($netlinkingProjectsStat);
        $tasksStatistic = $scheduleRepository->getTaskStatisticsByProjects($netlinkingProjectsStat);

        $help = null;

        if ($user->isWebmaster()) {
            if (empty($similarSites) && !$netlinkingPagerfanta->count() && !$copywritingProjectsPagerfanta->count()) {
                $page = $this->getDoctrine()->getRepository(StaticPage::class)->findByIdentificator(StaticPage::PAGE_HELP_WEBMASTER, $user->getLocale());
                $help = $page->getPageContent();
            }
        }

        $minDatedScheduleTasks = [];
        if (!empty($netlinkingPagerfanta)){
            $minDatedScheduleTasks = $scheduleRepository->getMinDatedScheduleTasksByProjects($netlinkingPagerfanta);
        }

        if (!empty($togetherPagerfanta) && User::ROLE_SUPER_ADMIN){
            $minDatedScheduleTasks = $scheduleRepository->getMinDatedScheduleTasksByProjects($togetherPagerfanta);
        }

        return $this->render('dashboard/index.html.twig', [
            'help' => $help,
            'sites' => $similarSites,
            'netlinkingProjects' => $netlinkingPagerfanta,
            'copywritingProjects' => $copywritingProjectsPagerfanta,
            'netlinkingAndCopywriting' => $togetherPagerfanta,
            'oldestStartedAt' => $oldestStartedAt,
            'status' => 'current',
            'statistics' => $statistics,
            'tasksStatistic' =>  $tasksStatistic,
            'changeWriterForm' => $this->createForm(CopyWriterSelectType::class),
            'assign_form' => $this->createForm(CopywritingAssignOrderType::class),
            'scheduleTasksMinDated' => $minDatedScheduleTasks,
        ]);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function hideCreditInfoAction(Request $request)
    {
        $user = $this->getUser();
        $user->setShowCredit(0);
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->json(['message' => $this->get('translator')->trans('ajax.read.success', [], 'message')]);
    }

    /**
     * @param array $netlinkingProjects
     * @param array $copywritingProjects
     * @return array
     */
    private function mergeNetlinkingAndCopywriting($netlinkingProjects, $copywritingProjects)
    {
        $i = 0;
        $j = 0;
        $k = 0;

        $n1 = count($netlinkingProjects);
        $n2 = count($copywritingProjects);
        $result = [];

        while ($i < $n1 && $j < $n2)
        {
            if ($netlinkingProjects[$i]->getAffectedAt() < $copywritingProjects[$j]->getTakenAt())
                $result[$k++] = $netlinkingProjects[$i++];
            else
                $result[$k++] = $copywritingProjects[$j++];
        }

        while ($i < $n1) {
            $result[$k++] = $netlinkingProjects[$i++];
        }

        while ($j < $n2) {
            $result[$k++] = $copywritingProjects[$j++];
        }

        return $result;
    }
}
