<?php

namespace Tests\CoreBundle;

use CoreBundle\DataFixtures\ORM\LoadTransactionTagData;
use CoreBundle\DataFixtures\Test\LoadCopywritingProjectData;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\Transaction;
use CoreBundle\Entity\User;
use CoreBundle\Repository\JobRepository;
use CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\AbstractTest;
use UserBundle\Services\BonusCalculator\CopywritingWriterBonusCalculator;

class CopywritingOrderTest extends AbstractTest
{
    /** @var MockObject */
    private $mockJobRepository;

    /** @var MockObject */
    private $mockUserRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->initMock();
    }

    public function testDayLateCalculating()
    {
        $order = new CopywritingOrder();

        $order->setStatus(CopywritingOrder::STATUS_WAITING);
        self::assertEquals(0, $order->getLateDays(), 'Case 0');

        $order
            ->setStatus(CopywritingOrder::STATUS_PROGRESS)
            ->setTakenAt(new \DateTime())
        ;
        self::assertEquals(0, $order->getLateDays(), 'Case 1');

        $order
            ->setStatus(CopywritingOrder::STATUS_PROGRESS)
            ->setTakenAt(new \DateTime('-1 day'))
        ;
        self::assertEquals(1, $order->getLateDays(), 'Case 2');

        $order
            ->setStatus(CopywritingOrder::STATUS_PROGRESS)
            ->setTakenAt(new \DateTime('-1 day -23 hours'))
        ;
        self::assertEquals(1, $order->getLateDays(), 'Case 3');

        $order
            ->setStatus(CopywritingOrder::STATUS_PROGRESS)
            ->setTakenAt(new \DateTime('-2 days -10 hours'))
        ;
        self::assertEquals(2, $order->getLateDays(), 'Case 4');

        $order
            ->setStatus(CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN)
            ->setTakenAt(new \DateTime('-5 days -10 hours'))
            ->setTimeInProgress(208800)
        ;
        self::assertEquals(2, $order->getLateDays(), 'Case 5');

        $order
            ->setStatus(CopywritingOrder::STATUS_PROGRESS)
            ->setTakenAt(new \DateTime('-5 days -10 hours'))
            ->setTimeInProgress(208800)
            ->setDeclinedAt(new \DateTime('-14 hours'))
        ;
        self::assertEquals(3, $order->getLateDays(), 'Case 6');
    }

    /**
     * @param $rules
     */
    private function initMock()
    {
        $this->mockUserRepository = $this->getMockBuilder(UserRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockJobRepository = $this->getMockBuilder(JobRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepository = [
            User::class => $this->mockUserRepository,
            Job::class => $this->mockJobRepository,
        ];

        $em = $this->container()->get('doctrine.orm.entity_manager');
        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEntityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturnCallback(
                function ($className) use ($mockRepository, $em) {
                    if (($i = array_search($className, array_keys($mockRepository))) !== false) {
                        return $mockRepository[array_keys($mockRepository)[$i]];
                    }

                    return $em->getRepository($className);
                }
            );

        $mockBonusCalculator = $this->getMockBuilder(CopywritingWriterBonusCalculator::class)
            ->enableArgumentCloning()
            ->enableOriginalClone()
            ->enableProxyingToOriginalMethods()
            ->setConstructorArgs([$mockEntityManager])
            ->getMock()
        ;

        $mockBonusCalculator->expects($this->any())
            ->method('countBonusForRating')
            ->willReturn(1)
        ;

        $this->container()->set('user.copywriting.writer_bonus_calculator', $mockBonusCalculator);
    }

    /** @dataProvider calculateBonusDataProvider */
    public function testCalculateBonus($order, $writer, $admin, $rules, $operations, $lateDays, $writerRewards, $adminRewards)
    {
        $this->loadFixtures([
            LoadCopywritingProjectData::class,
            LoadTransactionTagData::class,
        ]);

        if (isset($rules['userRating'])) {
            $this->mockUserRepository
                ->expects($this->any())
                ->method('getAverageCopywriterRating')
                ->willReturnCallback(
                    function () use ($rules) {
                        return $rules['userRating'];
                    }
                );
        }
        if (isset($rules['userMonthlyEarnings'])) {
            $this->mockUserRepository
                ->expects($this->any())
                ->method('getCopywriterEarningsForMonth')
                ->willReturn($rules['userMonthlyEarnings']/2)
            ;
            $this->mockJobRepository
                ->expects($this->any())
                ->method('getCopywriterEarningsByMonth')
                ->willReturn($rules['userMonthlyEarnings']/2)
            ;
        }

        /** @var CopywritingOrder $order */
        $order = $this->getObjectOf(CopywritingOrder::class, ['title' => $order]);

        /** @var User $writer */
        $writer = $this->getObjectOf(User::class, ['email' => $writer]);

        /** @var User $admin */
        $admin = $this->getObjectOf(User::class, ['email' => $admin]);

        $order->setMetaDescription(isset($rules['metaDescription']));
        $order->setWordsNumber($rules['words'] ?? 100);

        $copywritingOrderService = $this->container()->get('core.service.copywriting_order');
        $timeInProgress = 0;
        foreach ($operations as $operation) {
            switch ($operation['name']) {
                case 'create':
                    $order->setCreatedAt($operation['date']);
                    break;

                case 'take':
                    $order->setCopywriter($writer);
                    $copywritingOrderService->applyTransition($order, CopywritingOrder::TRANSITION_TAKE_TO_WORK);
                    $this->em()->refresh($order);
                    $order->setTakenAt($operation['date']);
                    break;

                case 'submitToAdmin':
                    $order->getArticle()->setText('<div>' . LoadCopywritingProjectData::generateRandomText($order->getWordsNumber()) .'</div>');
                    $copywritingOrderService->applyTransition($order, CopywritingOrder::TRANSITION_SUBMIT_TO_ADMIN);
                    $timeInProgress += $order->calculateTimeInProgress($operation['date']->getTimestamp());
                    $order->setTimeInProgress($timeInProgress);
                    break;

                case 'submitToWebmaster':
                    $this->setUser($admin);
                    $copywritingOrderService->applyTransition($order, CopywritingOrder::TRANSITION_SUBMIT_TO_WEBMASTER);
                    $copywritingOrderService->applyTransition($order, CopywritingOrder::TRANSITION_COMPLETE_TRANSITION);
                    break;

                case 'decline':
                    $copywritingOrderService->applyTransition($order, CopywritingOrder::TRANSITION_DECLINE_TRANSITION);
                    $order->setDeclinedAt($operation['date']);
                    break;
            }
        }

        self::assertEquals($lateDays, $order->getLateDays());

        /** @var Transaction $writerTransaction */
        $writerTransaction = $order->findTransactions(['user' => $writer, 'tag' => CopywritingOrder::TRANSACTION_TAG_REWARD])->last();
        $adminTransaction = $order->findTransactions(['user' => $admin, 'tag' => CopywritingOrder::TRANSACTION_TAG_REWARD])->last();

        self::assertEquals($writerRewards['total'], $writerTransaction->getDebit());
        if (isset($writerRewards['details'])) {
            self::assertArraySubset($writerRewards['details'], $writerTransaction->getDetails());
        }

        self::assertEquals($adminRewards['total'], $adminTransaction->getDebit());
        if (isset($adminRewards['details'])) {
            self::assertArraySubset($adminRewards['details'], $adminTransaction->getDetails());
        }
    }

    public function calculateBonusDataProvider()
    {
        return [
            [   # 0 - 1 days in progress
                'order' => 'P#4-O#2: new 100 words',
                'writer' => 'writer-1@test.com',
                'admin' => 'admin-1@test.com',
                'rules' => [
                    'words' => 100,
                    'userRating' => 90,
                    'userMonthlyEarnings' => 0
                ],
                'operations' => [
                    ['name' => 'create', 'date' => new \DateTime('-5 days')],
                    ['name' => 'take', 'date' => new \DateTime('-4 days')],
                    ['name' => 'submitToAdmin', 'date' => new \DateTime('-3 days')],
                    ['name' => 'submitToWebmaster', 'date' => new \DateTime('-3 days')],
                    ['name' => 'decline', 'date' => new \DateTime('-1 days')],
                    ['name' => 'submitToAdmin', 'date' => new \DateTime('-1 days')],
                    ['name' => 'submitToWebmaster', 'date' => new \DateTime('-1 days')],
                ],
                'lateDays' => 1,
                'writerRewards' => [
                    'total' => 0.8,
                    'details' => [
                        'earningForWords' => 0.8,
                        CopywritingProject::TRANSACTION_DETAIL_WRITER_BONUS => 0.05
                    ]
                ],
                'adminRewards' => [
                    'total' => 0.25,
                ],
            ],
            [   # 1 - 4 days in progress
                'order' => 'P#4-O#2: new 100 words',
                'writer' => 'writer-1@test.com',
                'admin' => 'admin-1@test.com',
                'rules' => [
                    'words' => 101,
                    'userRating' => 100,
                    'userMonthlyEarnings' => 0
                ],
                'operations' => [
                    ['name' => 'create', 'date' => new \DateTime('-5 days')],
                    ['name' => 'take', 'date' => new \DateTime('-5 days')],
                    ['name' => 'submitToAdmin', 'date' => new \DateTime('-1 days')],
                    ['name' => 'submitToWebmaster', 'date' => new \DateTime('-1 days')],
                ],
                'lateDays' => 4,
                'writerRewards' => [
                    'total' => 0.91,
                    'details' => [
                        'earningForWords' => 0.91,
                        CopywritingProject::TRANSACTION_DETAIL_WRITER_BONUS => 0.15,
                    ]
                ],
                'adminRewards' => [
                    'total' => 0.22,
                    'details' => [
                        'earningForWords' => 0.25,
                        'earningMalus' => 0.03,
                    ]
                ],
            ],
            [   # 2 - 5 days in progress ( 5+ days - without rating bonus, earning bonus)
                'order' => 'P#4-O#2: new 100 words',
                'writer' => 'writer-1@test.com',
                'admin' => 'admin-1@test.com',
                'rules' => [
                    'metaDescription' => true,
                    'words' => 200,
                    'userRating' => 100,
                    'userMonthlyEarnings' => 1000
                ],
                'operations' => [
                    ['name' => 'create', 'date' => new \DateTime('-6 days')],
                    ['name' => 'take', 'date' => new \DateTime('-6 days')],
                    ['name' => 'submitToAdmin', 'date' => new \DateTime('-1 days')],
                    ['name' => 'submitToWebmaster', 'date' => new \DateTime('-1 days')],
                ],
                'lateDays' => 5,
                'writerRewards' => [
                    'total' => 2.1,
                    'details' => [
                        'earningForWords' => 1.9,
                        CopywritingProject::TRANSACTION_DETAIL_REWARD_FOR_META_DESCRIPTION => 0.2,
                        CopywritingProject::TRANSACTION_DETAIL_WRITER_BONUS => 0.4,
                    ]
                ],
                'adminRewards' => [
                    'total' => 0.42,
                    'details' => [
                        'earningForWords' => 0.5,
                        'earningMalus' => 0.08,
                    ]
                ],
            ],
            [   # 3 - 2 days in progress (bad rating, no earning)
                'order' => 'P#4-O#2: new 100 words',
                'writer' => 'writer-1@test.com',
                'admin' => 'admin-1@test.com',
                'rules' => [
                    'words' => 350,
                    'userRating' => 65,
                    'userMonthlyEarnings' => 0
                ],
                'operations' => [
                    ['name' => 'create', 'date' => new \DateTime('-3 days')],
                    ['name' => 'take', 'date' => new \DateTime('-2 days')],
                    ['name' => 'submitToAdmin', 'date' => new \DateTime()],
                    ['name' => 'submitToWebmaster', 'date' => new \DateTime()],
                ],
                'lateDays' => 2,
                'writerRewards' => [
                    'total' => 2.1,
                    'details' => [
                        'earningForWords' => 2.1,
                        CopywritingProject::TRANSACTION_DETAIL_WRITER_MALUS => 0.53,
                    ]
                ],
                'adminRewards' => [
                    'total' => 0.84,
                    'details' => [
                        'earningForWords' => 0.88,
                        'earningMalus' => 0.04,
                    ]
                ],
            ],
            [   # 4 - 6 days in progress (malus for rating)
                'order' => 'P#4-O#2: new 100 words',
                'writer' => 'writer-1@test.com',
                'admin' => 'admin-1@test.com',
                'rules' => [
                    'metaDescription' => true,
                    'words' => 200,
                    'userRating' => 70,
                    'userMonthlyEarnings' => 1000
                ],
                'operations' => [
                    ['name' => 'create', 'date' => new \DateTime('-10 days')],
                    ['name' => 'take', 'date' => new \DateTime('-8 days')],
                    ['name' => 'submitToAdmin', 'date' => new \DateTime('-4 days')],
                    ['name' => 'submitToWebmaster', 'date' => new \DateTime('-2 days')],
                    ['name' => 'decline', 'date' => new \DateTime('-2 days')],
                    ['name' => 'submitToAdmin', 'date' => new \DateTime()],
                    ['name' => 'submitToWebmaster', 'date' => new \DateTime()],
                ],
                'lateDays' => 6,
                'writerRewards' => [
                    'total' => 1.5,
                    'details' => [
                        'earningForWords' => 1.3,
                        CopywritingProject::TRANSACTION_DETAIL_REWARD_FOR_META_DESCRIPTION => 0.2,
                        CopywritingProject::TRANSACTION_DETAIL_WRITER_MALUS => 0.2,
                    ]
                ],
                'adminRewards' => [
                    'total' => 0.4,
                    'details' => [
                        'earningForWords' => 0.5,
                        'earningMalus' => 0.1,
                    ]
                ],
            ],
            [   # 5 - 7 days in progress (minimal rate - 0.5)
                'order' => 'P#4-O#2: new 100 words',
                'writer' => 'writer-1@test.com',
                'admin' => 'admin-1@test.com',
                'rules' => [
                    'words' => 200,
                    'userRating' => 100,
                    'userMonthlyEarnings' => 1000
                ],
                'operations' => [
                    ['name' => 'create', 'date' => new \DateTime('-10 days')],
                    ['name' => 'take', 'date' => new \DateTime('-8 days')],
                    ['name' => 'submitToAdmin', 'date' => new \DateTime('-1 days')],
                    ['name' => 'submitToWebmaster', 'date' => new \DateTime('-1 days')],
                ],
                'lateDays' => 7,
                'writerRewards' => [
                    'total' => 1,
                    'details' => [
                        'earningForWords' => 1,
                        CopywritingProject::TRANSACTION_DETAIL_WRITER_MALUS => 0.5,
                    ]
                ],
                'adminRewards' => [
                    'total' => 0.38,
                    'details' => [
                        'earningForWords' => 0.5,
                        'earningMalus' => 0.12,
                    ]
                ],
            ],
            [   # 6 - 0 days in progress (base rate - 0)
                'order' => 'P#4-O#2: new 100 words',
                'writer' => 'writer-1@test.com',
                'admin' => 'admin-1@test.com',
                'rules' => [
                    'words' => 100,
                ],
                'operations' => [
                    ['name' => 'create', 'date' => new \DateTime('-1 days')],
                    ['name' => 'take', 'date' => new \DateTime('-1 days')],
                    ['name' => 'submitToAdmin', 'date' => new \DateTime('-1 days +1 sec')],
                    ['name' => 'submitToWebmaster', 'date' => new \DateTime()],
                ],
                'lateDays' => 0,
                'writerRewards' => [
                    'total' => 0.75,
                    'details' => [
                        'earningForWords' => 0.75,
                    ]
                ],
                'adminRewards' => [
                    'total' => 0.25,
                    'details' => [
                        'earningForWords' => 0.25,
                    ]
                ],
            ],
        ];
    }
}
