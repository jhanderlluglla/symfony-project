<?php

namespace Tests\CoreBundle\ExchangeProposition;

use CoreBundle\DataFixtures\ORM as ORM;
use CoreBundle\DataFixtures\Test as Test;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\User;

class WritingByWebmasterTest extends BaseExchangePropositionTest
{

    /**
     * @dataProvider fullDataProvider
     *
     * @param string|User $userBuyer
     * @param string|User $userAdmin
     * @param string|User $userWriter
     * @param array $formData
     * @param string|ExchangeSite $exchangeSite
     */
    public function testFull($userBuyer, $userAdmin, $userWriter, $formData, $exchangeSite)
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

        $exchangeProposal = $this->createExchangePropositionTest($userBuyer, $exchangeSite, ExchangeSite::ACTION_WRITING_WEBMASTER, $formData);

        $this->acceptProposalTest($exchangeProposal);

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

        $exchangeProposal = $this->createExchangePropositionTest($userBuyer, $exchangeSite, ExchangeSite::ACTION_WRITING_WEBMASTER, $formData);

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
}
