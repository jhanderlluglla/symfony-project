<?php
namespace CoreBundle\Menu;

use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Message;
use CoreBundle\Repository\CopywritingOrderRepository;
use CoreBundle\Repository\ExchangeSiteRepository;
use CoreBundle\Repository\MessageRepository;
use CoreBundle\Repository\NetlinkingProjectRepository;
use CoreBundle\Repository\UserRepository;
use CoreBundle\Services\AccessManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManager;

use Knp\Menu\MenuItem;
use Knp\Menu\FactoryInterface;

use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\User;

class MenuBuilder
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $translationDomain = 'menu';

    /** @var AccessManager */
    protected $accessManager;

    /**
     * @param FactoryInterface $factory
     * @param TranslatorInterface $translator
     * @param Router $router
     * @param TokenStorage $tokenStorage
     * @param TokenStorage $entityManager
     * @param AccessManager $accessManager
     */
    public function __construct(FactoryInterface $factory, $translator, $router, TokenStorage $tokenStorage, $entityManager, AccessManager $accessManager)
    {
        $this->factory       = $factory;
        $this->translator    = $translator;
        $this->router        = $router;
        $this->entityManager = $entityManager;
        $this->accessManager = $accessManager;

        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * @param Request $request
     *
     * @return MenuItem
     */
    public function createMainMenu(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();

        $menu = $this->factory->createItem('root', ['childrenAttributes' => ['class' => 'nav metismenu', 'id' => 'side-menu']]);
        $menu->setCurrent($this->request->getRequestUri());

        return $this->buildeMenu($menu, $this->getMenuArray());
    }

    /**
     * @param MenuItem    $menu
     * @param array       $menuArray
     * @param null|string $name
     *
     * @return MenuItem
     */
    private function buildeMenu($menu, $menuArray, $name = null)
    {
        foreach ($menuArray as $menuItem) {
            $show = isset($menuItem['show']) ? $menuItem['show']:true;

            if (!$show) {
                continue;
            }

            $itemName = $this->translator->trans($menuItem['name'], array(), $this->translationDomain);

            if (is_array($menuItem['route'])) {
                $uri = ($menuItem['route']['route'] == '#') ? '#':$this->router->generate($menuItem['route']['route'], $menuItem['route']['parameters']);
            } else {
                $uri = ($menuItem['route'] == '#') ? '#':$this->router->generate($menuItem['route']);
            }

            $options = array(
                'uri' => $uri,
            );

            $options['attributes'] = array_merge(array(
            ), !empty($menuItem['attributes']) ? $menuItem['attributes']: []);

            $options['linkAttributes'] = array_merge(array(
            ), !empty($menuItem['linkAttributes']) ? $menuItem['linkAttributes']: []);

            $options['childrenAttributes'] = array_merge(array(
            ), !empty($menuItem['childrenAttributes']) ? $menuItem['childrenAttributes']: []);

            if (!empty($menuItem['extras'])) {
                $options['extras'] = $menuItem['extras'];
            }

            if (!is_null($name)) {
                $menu[$name]->addChild($itemName, $options);
            } else {
                $menu->addChild($itemName, $options);
            }

            if ($this->request->getRequestUri() === $uri) {
                if (!is_null($name)) {
                    $menu[$name]->setCurrent(true);
                    $menu[$name][$itemName]->setCurrent(true);
                }else{
                    $menu[$itemName]->setCurrent(true);
                }
            }

            if (!empty($menuItem['menu'])) {
                $this->buildeMenu($menu, $menuItem['menu'], $itemName);
            }
        }

        return $menu;
    }

    /**
     * @return array
     *
     * @throws \CoreBundle\Exceptions\UnknownUserSetting
     */
    public function getMenuArray()
    {
        $countCopywriting = [
            'waitingCount' => 0,
            'toAdminCount' => 0,
            'completedCount' => 0,
            'progressCount' => 0,
            'waitingExpressCount' => 0,
            'progressExpressCount' => 0,
            'waitingOrdersCount' => 0,
            'pendingExpressCount' => 0,
            'toAdminExpressCount' => 0,
        ];

        try {
            /** @var CopywritingOrderRepository $copywritingOrderRepository */
            $copywritingOrderRepository = $this->entityManager->getRepository(CopywritingOrder::class);
            $countCopywriting = $copywritingOrderRepository->getCountProjects($this->user);
        } catch (\Exception $exception) {
            foreach ($countCopywriting as &$item) {
                $item = 0;
            }
        }

        $countNetlinking = [
            'waitingCount' => 0,
            'progressCount' => 0,
            'noStartCount' => 0,
            'finishedCount' => 0,
        ];

        try {
            /** @var NetlinkingProjectRepository $netlinkingProjectRepository */
            $netlinkingProjectRepository = $this->entityManager->getRepository(NetlinkingProject::class);
            $countNetlinking = $netlinkingProjectRepository->getCount($this->user);
        } catch (\Exception $exception) {
            foreach ($countNetlinking as &$item) {
                $item = 0;
            }
        }

        try {
            /** @var ExchangeSiteRepository $exchangeSiteRepository */
            $exchangeSiteRepository = $this->entityManager->getRepository(ExchangeSite::class);
            $countExchange = $exchangeSiteRepository->getCount($this->user);
        } catch (\Exception $exception) {
            $countExchange['receivedProposalCount'] = 0;
            $countExchange['finishedProposalCount'] = 0;
        }

        try {
            /** @var MessageRepository $messageRepository */
            $messageRepository = $this->entityManager->getRepository(Message::class);
            $countMessage = $messageRepository->getCountUnreadMessages($this->user);
        } catch (\Exception $exception) {
            $countMessage = 0;
        }

        $writerCopywritingMainCount = $countCopywriting['progressCount'];
        if ($writerCopywritingMainCount === 0) {
            $writerCopywritingMainCount = $countCopywriting['waitingExpressCount'] + $countCopywriting['waitingOrdersCount'];
        }

        $menu = [
            [
                'route' => 'user_dashboard',
                'name' => 'dashboard',
                'extras' => [
                    'icon' => 'fa fa-th-large',
                ],
            ],
            [
                'route' => '#',
                'name' => 'my_account.main',
                'extras' => [
                    'icon' => 'fa fa-user-circle',
                ],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'menu' => [
                    [
                        'route' => 'invoice_list',
                        'name' => 'my_account.invoice',
                        'extras' => [
                            'icon' => 'fa fa-bank',
                        ],
                        'show' => $this->user->hasRole(User::ROLE_SUPER_ADMIN) || $this->user->hasRole(User::ROLE_WEBMASTER),
                    ],
                    [
                        'route' => 'admin_replenish_account',
                        'name' => 'my_account.replenish_account',
                        'related_routs' => ['admin_replenish_account_success', 'admin_replenish_account_cancel'],
                        'extras' => [
                            'icon' => 'fa fa-euro',
                        ],
                        'show' => $this->user->hasRole(User::ROLE_WEBMASTER),
                    ],
                    [
                        'route' => 'admin_replenish_requests',
                        'name' => 'my_account.replenish_requests',
                        'extras' => [
                            'icon' => 'fa fa-euro',
                        ],
                        'show' => $this->user->hasRole(User::ROLE_SUPER_ADMIN),
                    ],
                    [
                        'route' => 'admin_transaction',
                        'name' => 'my_account.transaction',
                        'extras' => [
                            'icon' => 'fa fa-bar-chart-o',
                        ],
                    ],
                    [
                        'route' => 'withdraw_request',
                        'name' => $this->user->isWebmaster() ? 'my_account.withdraw_earning' : 'my_account.withdraw_requests',
                        'extras' => [
                            'icon' => 'fa fa-money',
                        ],
                        'show' => $this->user->isWebmaster() || $this->accessManager->canManageEarning()
                    ],
                    [
                        'route' => 'user_email_notification',
                        'name' =>'email_notification',
                        'extras' => [
                            'icon' => 'fa fa-bell',
                        ],
                        'show' => $this->user->isWebmaster(),
                    ]
                ]
            ],
            [
                'route' => '#',
                'name' => 'user.main',
                'extras' => [
                    'icon' => 'fa fa-group',
                ],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'show' => $this->accessManager->canManageWebmasterUser() || $this->accessManager->canManageWriterUser(),
                'menu' => [
                    [
                        'route' => 'user_add',
                        'name' => 'user.add_new',
                        'show' => $this->user->isSuperAdmin()
                    ],
                    [
                        'route' => [
                            'route' => 'user_list',
                            'parameters' => ['role' => 'seo'],
                        ],
                        'name' => 'user.seos',
                        'show' => $this->accessManager->canManageWriterUser()
                    ],
                    [
                        'route' => [
                            'route' => 'user_list',
                            'parameters' => ['role' => 'webmaster'],
                        ],
                        'name' => 'user.webmasters',
                        'show' => $this->accessManager->canManageWebmasterUser()
                    ],
                    [
                        'route' => [
                            'route' => 'user_list',
                            'parameters' => ['role' => 'administrator'],
                        ],
                        'name' => 'user.administrators',
                        'show' => $this->user->isSuperAdmin()
                    ],
                    [
                        'route' => 'admin_affiliation',
                        'name' => 'user.statistic',
                        'show' => $this->user->isSuperAdmin()
                    ],
                ]
            ],
            [
                'route' => '#',
                'name' => 'affiliation.main',
                'extras' => [
                    'icon' => 'fa fa-handshake-o',
                ],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'show' => $this->user->isShowAffiliation(),
                'menu' => [
                    [
                        'route' => 'admin_affiliation',
                        'name' => 'affiliation.main',
                    ],
                    [
                        'route' => 'admin_affiliation',
                        'name' => 'affiliation.contract',
                    ],
                ]
            ],
            [
                'route' => '#',
                'name' => 'exchange_site.main',
                'extras' => [
                    'icon' => 'fa fa-copy',
                    'count' => $countExchange['receivedProposalCount']
                ],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'related_routs' => ['admin_exchange_site_add', 'admin_exchange_site_edit', 'user_exchange_site_find'],
                'show' => $this->user->hasRole(User::ROLE_WEBMASTER) || $this->accessManager->canManageNetlinkingProject(),
                'menu' => [
                    [
                        'route' => 'admin_exchange_site',
                        'name' => 'exchange_site.management',
                    ],
                    [
                        'route' => 'user_exchange_site_proposals',
                        'name' => 'exchange_site.proposals_received',
                        'extras' => [
                            'count' => $countExchange['receivedProposalCount'],
                        ],
                    ],
                    [
                        'route' => 'user_exchange_site_history',
                        'name' => 'exchange_site.history',
                        'show' => $this->user->isWebmaster(),
                        'extras' => [
                            'count' => $this->user->isWebmaster() ? $countExchange['finishedProposalCount'] : 0,
                        ],
                    ],
                ]
            ],
            [
                'route' => '#',
                'name' => $this->user->hasRole([User::ROLE_WRITER, User::ROLE_WRITER_NETLINKING, User::ROLE_WRITER_COPYWRITING]) ? 'netlinking.annuaire' : 'netlinking.main',
                'extras' => [
                    'icon' => 'fa fa-paper-plane',
                    'count' => (
                        ($this->user->isWriterNetlinking())
                            ? $countNetlinking['progressCount']
                            : ($this->user->isWebmaster()
                                ? $countNetlinking['noStartCount']
                                : $countNetlinking['waitingCount'])),
                ],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'show' => $this->accessManager->canManageNetlinkingProject() || (!$this->user->hasRole(User::ROLE_WRITER_COPYWRITING) && !$this->user->hasRole(User::ROLE_WRITER_ADMIN)),
                'related_routs' => ['admin_directories_list', 'netlinking_status', 'netlinking_all', 'backlinks_all'],
                'menu' => [
                    [
                        'route' => 'admin_directories_list',
                        'name' => 'netlinking.directories_list',
                        'show' => $this->user->hasRole(User::ROLE_WEBMASTER),
                    ],
                    [
                        'route' => 'netlinking_add',
                        'name' => 'netlinking.add',
                        'show' => $this->user->hasRole(User::ROLE_WEBMASTER),
                    ],
                    [
                        'route' => [
                            'route' => 'netlinking_status',
                            'parameters' => ['status' => 'getnew'],
                        ],
                        'name' => 'netlinking.get_new',
                        'show' => (
                            ($this->user->hasRole(User::ROLE_WRITER) || $this->user->hasRole(User::ROLE_WRITER_NETLINKING)) &&
                            ($this->entityManager->getRepository(NetlinkingProject::class)->filter(['affected_user' => $this->user, 'status' => NetlinkingProject::STATUS_IN_PROGRESS], true) == 0)
                        ),
                    ],
                    [
                        'route' => 'netlinking_all',
                        'name' => 'netlinking.current_projects',
                        'extras' => [
                            'count' => $countNetlinking['progressCount']
                        ],
                    ],
                    [
                        'route' => [
                            'route' => 'netlinking_status',
                            'parameters' => ['status' => 'waiting'],
                        ],
                        'name' => ($this->user->hasRole(User::ROLE_WEBMASTER) ? 'netlinking.projects_pending_webmaster' : 'netlinking.projects_pending'),
                        'show' => ($this->user->hasRole(User::ROLE_WEBMASTER) || $this->accessManager->canManageNetlinkingProject()),
                        'extras' => [
                            'count' => $countNetlinking['waitingCount']
                        ],
                    ],
                    [
                        'route' => [
                            'route' => 'netlinking_status',
                            'parameters' => ['status' => 'nostart'],
                        ],
                        'name' => 'netlinking.projects_not_started',
                        'show' => $this->user->hasRole(User::ROLE_WEBMASTER),
                        'extras' => [
                            'count' => $countNetlinking['noStartCount']
                        ],
                    ],
                    [
                        'route' => [
                            'route' => 'netlinking_status',
                            'parameters' => ['status' => 'finished'],
                        ],
                        'name' => 'netlinking.completed_projects',
                        'show' => ($this->user->hasRole(User::ROLE_WEBMASTER) || $this->accessManager->canManageNetlinkingProject()),
                        'extras' => [
                            'count' => $countNetlinking['finishedCount']
                        ],
                    ],
                    [
                        'route' => 'user_exchange_site_find',
                        'name' => 'exchange_site.find_sites',
                        'show' => $this->user->isWebmaster() || $this->user->isAdmin(),
                    ],
                    [
                        'route' => 'submissions_all',
                        'name' => 'netlinking.submissions',
                        'show' => $this->user->isAdmin(),
                    ],
                    [
                        'route' => 'backlinks_all',
                        'name' => 'netlinking.backlinks',
                        'show' => $this->user->isWriterNetlinking() || $this->user->isAdmin(),
                    ],
                ]
            ],
            [
                'route' => '#',
                'name' => 'copywriting.main',
                'extras' => [
                    'icon' => 'fa fa-pencil',
                    'count' => ($this->user->isWriterCopywriting()
                                    ? $writerCopywritingMainCount
                                    : ($this->user->isWebmaster()
                                        ? $countCopywriting['completedCount']
                                        : ($this->user->isAdmin()
                                            ? $countCopywriting['toAdminCount'] : 0))),
                    'express' => ($this->user->isWriter() || $this->user->hasRole(User::ROLE_WRITER_COPYWRITING)
                                    ? $countCopywriting['waitingExpressCount'] + $countCopywriting['progressExpressCount']
                                    : ($this->user->isWebmaster()
                                        ? $countCopywriting['pendingExpressCount'] + $countCopywriting['progressExpressCount']
                                        : ($this->user->isAdmin()
                                            ? $countCopywriting['pendingExpressCount'] + $countCopywriting['progressExpressCount'] + $countCopywriting['toAdminExpressCount'] : 0)))
                ],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'show' => !$this->user->hasRole(User::ROLE_WRITER_NETLINKING) && !($this->user->hasRole(User::ROLE_WRITER_ADMIN) && !$this->accessManager->canManageCopywritingProject()),
                'related_routes' => ['copywriting_project_create', 'copywriting_order_list'],
                'menu' => [
                    [
                        'route' => 'admin_copywriting_sites',
                        'name' => 'copywriting.site_management',
                        'show' => $this->user->hasRole(User::ROLE_WEBMASTER) || $this->accessManager->canManageCopywritingProject(),
                    ],
                    [
                        'route' => 'copywriting_project_create',
                        'name' => 'copywriting.create',
                        'show' => $this->user->hasRole(User::ROLE_WEBMASTER),
                    ],
                    [
                        'route' => [
                            'route' => 'copywriting_order_list',
                            'parameters' => ['status' => 'waiting']
                        ],
                        'name' => 'copywriting.pending',
                        'extras' => [
                            'count' => $this->user->isWebmaster() || $this->user->isSuperAdmin() || $this->user->isWriterAdmin() ? $countCopywriting['waitingCount'] :0,
                            'express' => $this->user->isWebmaster() || $this->user->isSuperAdmin() || $this->user->isWriterAdmin() ? $countCopywriting['pendingExpressCount'] :0,
                        ],
                        'show' => $this->user->isWebmaster() || $this->user->isSuperAdmin() || $this->user->isWriterAdmin(),
                    ],
                    [
                        'route' => 'copywriting_waiting_orders',
                        'name' => 'copywriting.waiting_take_projects',
                        'show' => $this->user->isWriterCopywriting() && !($this->user->hasRole(User::ROLE_WRITER_ADMIN) && $this->accessManager->canManageCopywritingProject()),
                        'extras' => [
                            'count' => $this->user->isWriterCopywriting() ?
                                ($countCopywriting['waitingOrdersCount'] == 0 && $countCopywriting['waitingExpressCount'] == 0 && $countCopywriting['progressCount'] == 0 ?
                                    $countCopywriting['pendingForWriter'] : $countCopywriting['waitingOrdersCount']) : 0,
                            'express' => $this->user->isWriterCopywriting() ? $countCopywriting['waitingExpressCount'] :0,
                        ],
                    ],
                    [
                        'route' => [
                            'route' => 'copywriting_order_list',
                            'parameters' => ['status' => $this->user->isWebmaster() ?
                                [CopywritingOrder::STATUS_PROGRESS, CopywritingOrder::STATUS_DECLINED, CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN] :
                                [CopywritingOrder::STATUS_PROGRESS, CopywritingOrder::STATUS_DECLINED] ]
                        ],
                        'name' => 'copywriting.in_progress',
                        'extras' => [
                            'count' => $countCopywriting['progressCount'],
                            'express' => $countCopywriting['progressExpressCount'],
                        ],
                    ],
                    [
                        'route' => [
                            'route' => 'copywriting_order_list',
                            'parameters' => ['status' => 'submitted_to_admin']
                        ],
                        'name' => $this->user->isAdmin() ? 'copywriting.submitted_to_admin' : 'copywriting.submitted_to_admin_webmaster',
                        'show' => $this->user->isAdmin(),
                        'extras' => [
                            'count' => $this->user->isAdmin() ? $countCopywriting['toAdminCount'] :0,
                            'express' => $this->user->isAdmin() ? $countCopywriting['toAdminExpressCount'] :0,
                        ],
                    ],
                    [
                        'route' => [
                            'route' => 'copywriting_order_list',
                            'parameters' => ['status' => 'submitted_to_admin']
                        ],
                        'name' => 'copywriting.under_review',
                        'show' => $this->user->isWriter() || $this->user->hasRole(User::ROLE_WRITER_COPYWRITING),
                        'extras' => [
                            'count' => $this->user->isWriter() || $this->user->hasRole(User::ROLE_WRITER_COPYWRITING) ? $countCopywriting['underReviewCount'] :0,
                            'express' => $this->user->isWriter() || $this->user->hasRole(User::ROLE_WRITER_COPYWRITING) ? $countCopywriting['underReviewExpressCount'] :0,
                        ],
                    ],
                    [
                        'route' => [
                            'route' => 'copywriting_order_list',
                            'parameters' => ['status' => 'completed']
                        ],
                        'name' => 'copywriting.completed',
                        'show' => $this->user->isWebmaster() || $this->accessManager->canManageCopywritingProject(),
                        'extras' => [
                            'count' => $this->user->isWebmaster() || $this->accessManager->canManageCopywritingProject() ? $countCopywriting['completedCount'] :0,
                        ],
                    ],
                    [
                        'route' => 'copywriting_order_statistics',
                        'name' => 'copywriting.statistics',
                        'show' =>  $this->accessManager->canManageCopywritingProject(),
                    ],
                ]
            ],
            [
                'route' => 'plugin',
                'name' => 'plugin',
                'extras' => [
                    'icon' => 'fa fa-globe',
                ],
                'show' => $this->user->isWebmaster(),
            ],
            [
                'route' => '#',
                'name' => 'messages.main',
                'extras' => [
                    'icon' => 'fa fa-envelope',
                    'count' => $countMessage,
                ],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'related_routs' => ['message_all', 'message_add', 'message_delete', 'message_view', 'message_reply', 'message_view'],
                'menu' => [
                    [
                        'route' => 'message',
                        'name' => 'messages.list',
                    ],
                    [
                        'route' => [
                            'route' => 'message_all',
                            'parameters' => ['mode' => 'incoming']
                        ],
                        'name' => 'messages.incoming',
                        'extras' => [
                            'count' => $countMessage,

                        ],
                    ],
                    [
                        'route' => [
                            'route' => 'message_all',
                            'parameters' => ['mode' => 'outgoing']
                        ],
                        'name' => 'messages.outgoing',
                    ],
                    [
                        'route' => 'message_add',
                        'name' => 'messages.new',
                    ],
                ]
            ],
            [
                'route' => 'article_blog',
                'name' => 'article_blog',
                'extras' => [
                    'icon' => 'fa fa-newspaper-o',
                ],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'show' => $this->user->hasRole(User::ROLE_SUPER_ADMIN),
            ],
            [
                'route' => 'admin_images',
                'name' => 'admin_images.main',
                'extras' => [
                    'icon' => 'fa fa-file-image-o',
                ],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'show' => $this->user->hasRole(User::ROLE_SUPER_ADMIN),
            ],
            [
                'route' => '#',
                'name' => 'settings.main',
                'extras' => [
                    'icon' => 'fa fa-gears',
                ],
                'related_routs' => ['admin_static_page_add', 'admin_static_page_edit'],
                'childrenAttributes' => [
                    'class' => 'nav nav-second-level'
                ],
                'show' => $this->user->hasRole(User::ROLE_SUPER_ADMIN),
                'menu' => [
                    [
                        'route' => 'admin_settings',
                        'name' => 'settings.settings',
                    ],
                    [
                        'route' => 'admin_pages',
                        'name' => 'settings.pages',
                    ],
                    [
                        'route' => 'admin_static_page',
                        'name' => 'settings.static_page',
                    ],
                    [
                        'route' => 'admin_category',
                        'name' => 'settings.category',
                    ],
                    [
                        'route' => 'admin_directory',
                        'name' => 'settings.directory',
                    ],
                    [
                        'route' => 'admin_email_template',
                        'name' => 'settings.email_templates',
                    ],
                ]
            ]
        ];

        return $menu;
    }
}
