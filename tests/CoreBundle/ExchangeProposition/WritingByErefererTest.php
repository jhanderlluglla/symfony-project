<?php

namespace Tests\CoreBundle\ExchangeProposition;

use CoreBundle\DataFixtures\ORM as ORM;
use CoreBundle\DataFixtures\Test as Test;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\User;

class WritingByErefererTest extends BaseExchangePropositionTest
{

    /**
     * @dataProvider fullDataProvider
     *
     * @param string|User $userBuyer
     * @param string|User $userAdmin
     * @param string|User $userWriter
     * @param array $formData
     * @param string|ExchangeSite $exchangeSite
     * @param string $article
     */
    public function testFull($userBuyer, $userAdmin, $userWriter, $formData, $exchangeSite, $article)
    {
        $fixtures = [
            ORM\LoadSettings::class,
            ORM\LoadEmailTemplatesData::class,
            Test\LoadUserData::class,
            Test\LoadExchangeSiteData::class,
            ORM\LoadTransactionTagData::class,
        ];

        $this->loadFixtures($fixtures);

        $this->loadBaseData($exchangeSite, $userBuyer, $userAdmin, $userWriter);

        $exchangeProposal = $this->createExchangePropositionTest($userBuyer, $exchangeSite, ExchangeSite::ACTION_WRITING_EREFERER, $formData);

        $this->writerAssignmentTest($exchangeProposal, $userAdmin, $userWriter);

        $this->writingArticleTest($exchangeProposal, $article);

        $this->approveArticleTest($exchangeProposal, $userAdmin);

        $this->publishedArticleTest($exchangeProposal);
    }

    /**
     * @return array
     */
    public function fullDataProvider()
    {
        $formData = [
            'urls' => [
                0 => [
                    'url' => 'http://www.site.lc',
                    'anchor' => 'Test',
                ]
            ]
        ];

        return [
            [
                'userBuyer' => 'webmaster-2@test.com',
                'userAdmin' => 'admin-1@test.com',
                'userWriter' => 'writer-1@test.com',
                'form' => $formData,
                'exchangeSite' => ['url' => 'https://google.com/'],
                'article' => 'article_text.html'
            ],
        ];
    }

    /**
     * @dataProvider impossibleDataProvider
     *
     * @param string|User $userBuyer
     * @param string|User $userAdmin
     * @param string|User $userWriter
     * @param array $formData
     * @param string|ExchangeSite $exchangeSite
     * @param array $options
     */
    public function testImpossibleAction($userBuyer, $userAdmin, $userWriter, $formData, $exchangeSite, $options = [])
    {
        $fixtures = [
            ORM\LoadSettings::class,
            ORM\LoadEmailTemplatesData::class,
            Test\LoadUserData::class,
            Test\LoadExchangeSiteData::class,
            ORM\LoadTransactionTagData::class,
        ];

        $this->loadFixtures($fixtures);

        $this->loadBaseData($exchangeSite, $userBuyer, $userAdmin, $userWriter);

        $exchangeProposal = $this->createExchangePropositionTest($userBuyer, $exchangeSite, ExchangeSite::ACTION_WRITING_EREFERER, $formData);

        $this->writerAssignmentTest($exchangeProposal, $userAdmin, $userWriter);

        if (isset($options['statusExchangeProposalBeforeImpossible'])) {
            $exchangeProposal->setStatus($options['statusExchangeProposalBeforeImpossible']);
        }

        $this->impossibleArticleTest($exchangeProposal);
    }

    /**
     * @return array
     */
    public function impossibleDataProvider()
    {
        $formData = [
            'urls' => [
                0 => [
                    'url' => 'http://www.site.lc',
                    'anchor' => 'Test',
                ]
            ]
        ];

        $baseData = [
            'userBuyer' => 'webmaster-2@test.com',
            'userAdmin' => 'admin-1@test.com',
            'userWriter' => 'writer-1@test.com',
            'form' => $formData,
            'exchangeSite' => ['url' => 'https://google.com/'],
        ];

        return [
            $baseData,
            $baseData + [
                'options' => [
                        'statusExchangeProposalBeforeImpossible' => ExchangeProposition::STATUS_PUBLISHED
                ]
            ],
        ];
    }

    /**
     * @dataProvider refuseDataProvider
     *
     * @param string|User $userBuyer
     * @param string|User $userAdmin
     * @param string|User $userWriter
     * @param array $formData
     * @param string|ExchangeSite $exchangeSite
     * @param array $options
     * @param string $status
     *
     * @throws \Exception
     */
    public function testRefuseAction($userBuyer, $userAdmin, $userWriter, $formData, $exchangeSite, $options = [], $status = 'success')
    {
        $fixtures = [
            ORM\LoadSettings::class,
            ORM\LoadEmailTemplatesData::class,
            Test\LoadUserData::class,
            Test\LoadExchangeSiteData::class,
            ORM\LoadTransactionTagData::class,
        ];

        $this->loadFixtures($fixtures);

        $this->loadBaseData($exchangeSite, $userBuyer, $userAdmin, $userWriter);

        $exchangeProposal = $this->createExchangePropositionTest($userBuyer, $exchangeSite, ExchangeSite::ACTION_WRITING_EREFERER, $formData);

        $exchangeProposal->getCopywritingOrders()->setCopywriter($userWriter);
        $exchangeProposal->getCopywritingOrders()->setTakenAt(new \DateTime('+1 hour'));
        $exchangeProposal->getCopywritingOrders()->setApprovedBy($userAdmin);
        $exchangeProposal->getCopywritingOrders()->setApprovedAt(new \DateTime('+8 hour'));


        if (isset($options['statusExchangeProposalBeforeRefuse'])) {
            $exchangeProposal->setStatus($options['statusExchangeProposalBeforeRefuse']);
        } else {
            $exchangeProposal->setStatus(ExchangeProposition::STATUS_ACCEPTED);
        }

        $this->refuseArticleTest($exchangeProposal, $status);
    }

    /**
     * @return array
     */
    public function refuseDataProvider()
    {
        $formData = [
            'urls' => [
                0 => [
                    'url' => 'http://www.site.lc',
                    'anchor' => 'Test',
                ]
            ]
        ];

        $baseData = [
            'userBuyer' => 'webmaster-2@test.com',
            'userAdmin' => 'admin-1@test.com',
            'userWriter' => 'writer-1@test.com',
            'form' => $formData,
            'exchangeSite' => ['url' => 'https://google.com/'],
        ];

        return [
            $baseData,
            $baseData + [
                'options' => [
                    'statusExchangeProposalBeforeRefuse' => ExchangeProposition::STATUS_PUBLISHED
                ],
                'status' => 'fail',
            ],
        ];
    }

    /**
     * @dataProvider expiredDataProvider
     *
     * @param string|User $userBuyer
     * @param string|User $userAdmin
     * @param string|User $userWriter
     * @param array $formData
     * @param string|ExchangeSite $exchangeSite
     * @param array $options
     * @param bool $isExpired
     *
     * @throws \Exception
     */
    public function testExpiredAction($userBuyer, $userAdmin, $userWriter, $formData, $exchangeSite, $options = [], $isExpired = false)
    {
        $fixtures = [
            ORM\LoadSettings::class,
            ORM\LoadEmailTemplatesData::class,
            Test\LoadUserData::class,
            Test\LoadExchangeSiteData::class,
            ORM\LoadTransactionTagData::class,
        ];

        $this->loadFixtures($fixtures);

        $this->loadBaseData($exchangeSite, $userBuyer, $userAdmin, $userWriter);

        $exchangeProposal = $this->createExchangePropositionTest($userBuyer, $exchangeSite, ExchangeSite::ACTION_WRITING_EREFERER, $formData);

        $exchangeProposal->getCopywritingOrders()->setCopywriter($userWriter);
        $exchangeProposal->getCopywritingOrders()->setTakenAt(new \DateTime('+1 hour'));
        $exchangeProposal->getCopywritingOrders()->setApprovedBy($userAdmin);
        $exchangeProposal->getCopywritingOrders()->setApprovedAt(new \DateTime('+8 hour'));

        if (isset($options['createdAt'])) {
            $exchangeProposal->setCreatedAt($options['createdAt']);
        }
        if (isset($options['status'])) {
            $exchangeProposal->setStatus($options['status']);
        }
        $this->em()->flush();

        $this->expiredArticleTest($exchangeProposal, $isExpired);
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function expiredDataProvider()
    {
        $formData = [
            'urls' => [
                0 => [
                    'url' => 'http://www.site.lc',
                    'anchor' => 'Test',
                ]
            ]
        ];

        $baseData = [
            'userBuyer' => 'webmaster-2@test.com',
            'userAdmin' => 'admin-1@test.com',
            'userWriter' => 'writer-1@test.com',
            'form' => $formData,
            'exchangeSite' => ['url' => 'https://google.com/'],
        ];

        return [
            $baseData + [
                'isExpired' => false
            ],
            $baseData + [
                'options' => [
                    'createdAt' => new \DateTime('-10 days'),
                    'status' => ExchangeProposition::STATUS_PUBLISHED,
                ],
                'isExpired' => false
            ],
            $baseData + [
                'options' => [
                    'createdAt' => new \DateTime('-20 days'),
                    'status' => ExchangeProposition::STATUS_PUBLISHED,
                ],
                'isExpired' => false
            ],
            $baseData + [
                'options' => [
                    'createdAt' => new \DateTime('-20 days'),
                    'status' => ExchangeProposition::STATUS_ACCEPTED,
                ],
                'isExpired' => true
            ],
            $baseData + [
                'options' => [
                    'createdAt' => new \DateTime('-16 days'),
                    'status' => ExchangeProposition::STATUS_AWAITING_WEBMASTER,
                ],
                'isExpired' => true
            ],
            $baseData + [
                'options' => [
                    'createdAt' => new \DateTime('-20 days'),
                    'status' => ExchangeProposition::STATUS_IN_PROGRESS,
                ],
                'isExpired' => false
            ],
        ];
    }
}
