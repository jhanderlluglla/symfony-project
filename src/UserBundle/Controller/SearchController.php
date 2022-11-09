<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Entity\User;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Repository\NetlinkingProjectRepository;
use CoreBundle\Repository\ScheduleTaskRepository;
use CoreBundle\Repository\UserRepository;
use CoreBundle\Repository\DirectoryBacklinksRepository;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Form\Netlinking\CopyWriterSelectType;
use UserBundle\Security\SearchVoter;

class SearchController extends Controller
{
    /**
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function searchAction(Request $request)
    {
        $searchType = $request->get('search_type', 'netlinking_projects');
        $page = $request->get('page', 1);
        $query = trim($request->get('query'));

        switch ($searchType) {
            case 'netlinking_projects':
                $this->denyAccessUnlessGranted(SearchVoter::SEARCH_NETLINKING_PROJECT);

                /** @var NetlinkingProjectRepository $netLinkingRepository */
                $netLinkingRepository = $this->getDoctrine()->getRepository(NetlinkingProject::class);

                /** @var DirectoryBacklinksRepository $backlinksRepository */
                $backlinksRepository = $this->getDoctrine()->getRepository(DirectoryBacklinks::class);

                /** @var ScheduleTaskRepository $scheduleRepository */
                $scheduleRepository = $this->getDoctrine()->getRepository(ScheduleTask::class);

                $collection = [];
                $projects = $netLinkingRepository->searchProject($query, $this->getUser());

                $minDatedScheduleTasks = $scheduleRepository->getMinDatedScheduleTasksByProjects($projects);

                foreach ($projects as $project) {
                    $collection[$project->getStatus()][] = $project;
                }

                return $this->render('netlinking/search.html.twig', [
                    'collection' => $collection,
                    'changeWriterForm' => $this->createForm(CopyWriterSelectType::class),
                    'statistics' => $backlinksRepository->getStatisticsByProjects($projects),
                    'tasksStatistic' => $scheduleRepository->getTaskStatisticsByProjects($projects),
                    'scheduleTasksMinDated' => $minDatedScheduleTasks,
                ]);
            case 'users':
                $this->denyAccessUnlessGranted(SearchVoter::SEARCH_USERS);

                /** @var UserRepository $userRepository */
                $userRepository = $this->getDoctrine()->getRepository(User::class);

                $users = $userRepository->searchUsers($query);

                return $this->render('user/search.html.twig', [
                    'collection' => PagerfantaAdapterFactory::getPagerfantaInstance($users, $page)
                ]);

            case 'exchange_sites':
                $this->denyAccessUnlessGranted(SearchVoter::SEARCH_EXCHANGE_SITE);
                return $this->redirectToRoute('admin_exchange_site', [
                    'query' => $query,
                ]);
        }
    }
}
