<?php

namespace Tests;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;

trait ORMTestCaseTrait
{
    /** @var  EntityManager */
    private $em;

    public function setupORMTestCaseTrait()
    {
        $this->em = $this->container->get('doctrine.orm.entity_manager');

        $connection = $this->em->getConnection();
        $query = $connection->prepare('SELECT CONCAT(\'TRUNCATE TABLE \', TABLE_NAME) as \'query\' FROM INFORMATION_SCHEMA.TABLES WHERE  table_schema = :table');
        $query->execute(['table' => $connection->getDatabase()]);

        $connection->exec('SET foreign_key_checks = 0');

        foreach ($query->fetchAll() as $sql) {
            $connection->exec($sql['query']);
        }
    }

    /**
     * @param array $classNames
     * @param bool $append
     */
    public function loadFixtures(array $classNames)
    {
        $loader = new ContainerAwareLoader($this->container);

        foreach ($classNames as $className) {
            $loader->addFixture(new $className);
        }

        $executor = new ORMExecutor($this->em);
        $executor->execute($loader->getFixtures(), true);
    }

    public function flushORM()
    {
        $this->container->get('doctrine.orm.default_entity_manager')->flush();
    }
}
