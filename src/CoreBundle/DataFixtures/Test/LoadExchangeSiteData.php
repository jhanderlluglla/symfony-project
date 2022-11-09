<?php

namespace CoreBundle\DataFixtures\Test;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\ExchangeSite;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadExchangeSiteData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $exchangeSite = new ExchangeSite();
        $exchangeSite
            ->setUser($this->getReference('user-test-webmaster-1'))
            ->setUrl('https://google.com/')
            ->setSiteType(ExchangeSite::EXCHANGE_TYPE)
            ->setMinWordsNumber(120)
            ->setMaxLinksNumber(1)
            ->setMaxImagesNumber(1)
            ->setMinImagesNumber(0)
            ->setCredits(11)
            ->setPluginStatus(false)
            ->setLanguage(Language::EN)
        ;
        $manager->persist($exchangeSite);

        $exchangeSite = new ExchangeSite();
        $exchangeSite
            ->setUser($this->getReference('user-test-webmaster-1'))
            ->setUrl('http://site.fr/')
            ->setSiteType(ExchangeSite::EXCHANGE_TYPE)
            ->setMinWordsNumber(120)
            ->setMaxLinksNumber(1)
            ->setMaxImagesNumber(1)
            ->setMinImagesNumber(0)
            ->setCredits(11)
            ->setPluginStatus(false)
            ->setLanguage(Language::FR)
        ;
        $manager->persist($exchangeSite);

        $manager->flush();
    }

    /**
     * This method must return an array of fixtures classes
     * on which the implementing class depends on
     *
     * @return array
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
