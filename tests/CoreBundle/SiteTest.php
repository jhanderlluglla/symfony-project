<?php

namespace Tests\CoreBundle;

use CoreBundle\DataFixtures\Test\LoadDirectoryData;
use CoreBundle\DataFixtures\Test\LoadExchangeSiteData;
use CoreBundle\DataFixtures\Test\LoadNetlinkingProjectData;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\Site;
use Tests\AbstractTest;

class SiteTest extends AbstractTest
{

    public function testCreateSiteEntity()
    {
        $this->loadFixtures(
            [
                LoadExchangeSiteData::class,
                LoadNetlinkingProjectData::class,
                LoadDirectoryData::class,
            ]
        );

        $siteRepository = $this->em()->getRepository(Site::class);
        $sites = $siteRepository->findAll();

        self::assertCount(3, $sites);

        $exchangeSiteGoogle = $this->getObjectOf(ExchangeSite::class, ['url' => 'https://google.com/']);
        $netlinkingProjectGoogle = $this->getObjectOf(NetlinkingProject::class, ['url' => 'https://google.com/page1.html']);

        self::assertEquals($exchangeSiteGoogle->getSite(), $netlinkingProjectGoogle->getSite());
    }
}
