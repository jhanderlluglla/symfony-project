<?php

namespace UserBundle\Twig\Extension;

use CoreBundle\Entity\Job;
use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Repository\ScheduleTaskRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\User;
use CoreBundle\Entity\Settings;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use UserBundle\Services\NetlinkingService;

/**
 * Class NetlinkingProjectExtension
 *
 * @package UserBundle\Twig\Extension
 */
class NetlinkingProjectExtension extends AbstractExtension
{

    const ESTIMATE_PER_DAY = 10;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var NetlinkingService
     */
    private $netlinkingService;

    /**
     * @var array
     */
    private static $estimatedLaunchDate = [];

    /**
     * @var array
     */
    private $settings = [];

    /**
     * @var array
     */
    private $routesShowStoppedState = [
        'netlinking_all',
        'netlinking_detail',
        'user_dashboard',
        'search'
    ];

    /**
     * NetlinkingProjectExtension constructor.
     *
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     * @param RequestStack        $requestStack
     * @param NetlinkingService   $netlinkingService
     */
    public function __construct($entityManager, $translator, $requestStack, $netlinkingService)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->netlinkingService = $netlinkingService;

        $this->settings = $this->entityManager->getRepository(Settings::class)->getSettingsArrayKeyValue();
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'netlinking_webmaster_status',
                [$this, 'netlinking_webmaster_status'],
                ['is_safe' => ['html']]
            ),

            new TwigFunction(
                'netlinking_webmaster_is_stopped',
                [$this, 'netlinking_webmaster_is_stopped'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'netlinking_project_status',
                [$this, 'netlinking_project_status'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'netlinking_waiting_time',
                [$this, 'netlinking_waiting_time']
            ),
        ];
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     *
     * @return string
     */
    public function netlinking_webmaster_status(NetlinkingProject $netlinkingProject)
    {
        /** @var User $webmaster */
        $webmaster = $netlinkingProject->getUser();

        /** @var  $jobRepository */
        $jobRepository = $this->entityManager->getRepository(Job::class);

        if ($webmaster->webmasterCanPay($this->settings[Settings::TARIFF_WEB])) {
            $latestTakeAt = $jobRepository->getLatestJobOfNetlinkingProject($netlinkingProject);
            if (!is_null($latestTakeAt)) {
                $class = "text-success";
                $latestTaskDate = new \DateTime($latestTakeAt);
                $message =
                    $this->translator->trans('table.statuses.last_submission', [], 'netlinking')
                    . ': '
                    . $latestTaskDate->format('d/m/Y');
            } else {
                $class = "text-warning";
                $message = $this->translator->trans('table.statuses.none_submission', [], 'netlinking');
            }
        } else {
            $class = 'text-danger';
            $message = $this->translator->trans('table.statuses.insufficient_webmaster_funds', [], 'netlinking');
        }

        return '<span class="' .$class. '">' .$message. '</span>';
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param boolean           $isAdmin
     *
     * @return string|boolean
     */
    public function netlinking_webmaster_is_stopped(NetlinkingProject $netlinkingProject, $isAdmin = true )
    {
        $route = $this->requestStack->getCurrentRequest()->attributes->get('_route');
        if ( in_array($route, $this->routesShowStoppedState) && ($netlinkingProject->getStatus() == 'in_progress')){
            $isWebmasterCan = $this->netlinkingService->isProjectCanBeProduced($netlinkingProject);
            if (!$isWebmasterCan) {
                if ( $isAdmin ) {
                    $message = $message = $this->translator->trans('table.statuses.insufficient_webmaster_funds', [], 'netlinking');
                }else{
                    $message = $this->translator->trans('table.statuses.insufficient_funds', [], 'netlinking');
                }
                return $message;
            }
        }
        return false;
    }

    /**
     * @param string $date
     *
     * @return string
     */
    public function netlinking_project_status($date)
    {
        $now = new \DateTime('today midnight');
        $dateTime = new \DateTime($date);

        $daysDifference = $dateTime->setTime(0,0,0)->diff($now)->days;
        if ($daysDifference) {
            $class = 'text-danger';
            $message = $this->translator->trans('table.statuses.days_late', [
                '%days%' => $daysDifference
            ], 'netlinking');
        } else {
            $class = 'text-info';
            $message = $this->translator->trans('table.statuses.task_day', [], 'netlinking');
        }

        return '<span class="' .$class. '">' .$message. '</span>';
    }

    /**
     * @return int
     *
     * @throws null|\Doctrine\ORM\NonUniqueResultException
     */
    public function netlinking_waiting_time()
    {
        /** @var \DateTime[] $query */
        $query = $this->entityManager->getRepository(NetlinkingProject::class)
            ->createQueryBuilder('np')
            ->select('np.startedAt')
            ->addSelect('np.affectedAt')
            ->andWhere('np.startedAt IS NOT NULL')
            ->andWhere('np.affectedAt IS NOT NULL')
            ->orderBy('np.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($query === null) {
            return null;
        }

        return $query['affectedAt']->diff($query['startedAt'])->days ?: 1;
    }
}
