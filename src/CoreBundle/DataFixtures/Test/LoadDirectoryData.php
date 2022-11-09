<?php

namespace CoreBundle\DataFixtures\Test;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\NetlinkingProject;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadDirectoryData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $directory = new Directory();
        $directory
            ->setName('https://abc.com')
            ->setLanguage(Language::EN)
        ;
        $manager->persist($directory);

        $directory = new Directory();
        $directory
            ->setName('https://www.abc.com/test.page')
            ->setLanguage(Language::EN)
        ;
        $manager->persist($directory);

        $manager->flush();
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
