<?php

namespace CoreBundle\DataFixtures\Test;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\ExchangeSite;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;


class ExchangeSiteData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $exchangeSite = new ExchangeSite();
        $exchangeSite->setUser($this->getReference('user-test-webmaster-1'));
        $exchangeSite->setUrl('http://google.com/');
        $exchangeSite->setSiteType(ExchangeSite::EXCHANGE_TYPE);
        $exchangeSite->setMinWordsNumber(120);
        $exchangeSite->setMaxLinksNumber(1);
        $exchangeSite->setMaxImagesNumber(1);
        $exchangeSite->setMinImagesNumber(0);
        $exchangeSite->setCredits(11);
        $exchangeSite->setPluginStatus(false);
        $exchangeSite->setLanguage(Language::EN);
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
        return [UserData::class];
    }
}
