<?php

namespace CoreBundle\DataFixtures\ORM;

use CoreBundle\Entity\TransactionTag;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use CoreBundle\Entity\DirectoriesList;

class LoadTransactionTagData extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (TransactionTag::getAvailableTags() as $tagName) {
            $entity = $this->isNotExists($tagName, $manager);

            if (is_null($entity)) {
                $entity = new TransactionTag();
                $entity->setName($tagName);

                $manager->persist($entity);
            }

            $this->setReference('transaction_tag_' . $tagName, $entity);
        }

        $manager->flush();
    }

    /**
     * @param string $name
     * @param ObjectManager $manager
     *
     * @return DirectoriesList
     */
    protected function isNotExists($name, ObjectManager $manager)
    {
        return $manager->getRepository(TransactionTag::class)->findOneBy(['name' => $name]);
    }
}
