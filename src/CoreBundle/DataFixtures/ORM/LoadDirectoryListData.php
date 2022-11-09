<?php

namespace CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use CoreBundle\Entity\DirectoriesList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadDirectoryListData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * The dependency injection container.
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadUserData::class,
            LoadDirectoryData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $env = $this->container->getParameter('kernel.environment');
        if($env == "dev") {
            $this->container->getParameter('kernel.environment');
            $entity = $this->isNotExists('Test list', $manager);
            if (is_null($entity)) {
                $entity = new DirectoriesList();
                $entity
                    ->setName('Test list')
                    ->setUser($this->getReference('user-buyer-1'))
                    ->setWordsCount(100)
                    ->addDirectories($this->getReference('directory-1'));

                $manager->persist($entity);
                $manager->flush();
            }

            $this->setReference('directory-list-1', $entity);
        }
    }

    /**
     * @param string $name
     * @param ObjectManager $manager
     *
     * @return DirectoriesList
     */
    protected function isNotExists($name, ObjectManager $manager)
    {
        return $manager->getRepository(DirectoriesList::class)->findOneBy(['name' => $name]);
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}