<?php

namespace CoreBundle\DataFixtures\Test;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\NetlinkingProject;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use DoctrineExtensions\Query\Mysql\Date;

class LoadNetlinkingProjectData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $netlinkingProject = new NetlinkingProject();
        $netlinkingProject
            ->setUser($this->getReference('user-test-webmaster-1'))
            ->setUrl('https://google.com/page1.html')
            ->setLanguage(Language::EN)
            ->setWordsCount(100)
            ->setComment('Project 1')
        ;
        $manager->persist($netlinkingProject);

        $job = new Job();
        $job
            ->setNetlinkingProject($netlinkingProject)
            ->setStatus(Job::STATUS_COMPLETED)
            ->setCostWebmaster(250)
            ->setComment('np1 - Job 250')
        ;
        $manager->persist($job);

        $netlinkingProject = new NetlinkingProject();
        $netlinkingProject
            ->setUser($this->getReference('user-test-webmaster-1'))
            ->setUrl('https://site.com/')
            ->setLanguage(Language::EN)
            ->setWordsCount(100)
            ->setComment('Project 2')
        ;
        $manager->persist($netlinkingProject);

        $job = new Job();
        $job
            ->setNetlinkingProject($netlinkingProject)
            ->setStatus(Job::STATUS_COMPLETED)
            ->setCostWebmaster(100)
            ->setComment('np2 - Job 100')
        ;
        $manager->persist($job);

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
