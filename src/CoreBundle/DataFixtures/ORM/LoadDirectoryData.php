<?php

namespace CoreBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Doctrine\Common\Persistence\ObjectManager;

use CoreBundle\Entity\Directory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadDirectoryData
 *
 * @package CoreBundle\DataFixtures\ORM
 */
class LoadDirectoryData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * The dependency injection container.
     *
     * @var ContainerInterface
     */
    protected $container;

    public function getDependencies()
    {
        return [
            LoadUserData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $env = $this->container->getParameter('kernel.environment');
        if($env == "dev") {
            $entity = $this->isNotExists('http://ereferer-dir.loc', $manager);
            if (is_null($entity)) {
                $entity = new Directory();
                $entity
                    ->setName('http://ereferer-dir.loc')
                    ->setWebmasterPartner($this->getReference('user-seller-1'))
                    ->setTariffWebmasterPartner(1)
                    ->setWebmasterOrder('http://ereferer-dir.loc')
                    ->setPageRank(1)
                    ->setMajesticTrustFlow(100)
                    ->setAge(new \DateTime())
                    ->setTotalReferringDomain(1);

                $manager->persist($entity);
                $manager->flush();
            }

            $this->setReference('directory-1', $entity);
        }
    }

    /**
     * @param string $url
     * @param ObjectManager $manager
     *
     * @return Directory
     */
    protected function isNotExists($url, ObjectManager $manager)
    {
        return $manager->getRepository(Directory::class)->findOneBy(['name' => $url]);
    }

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}