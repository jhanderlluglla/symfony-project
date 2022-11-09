<?php

namespace UserBundle\EventListener;

use CoreBundle\Entity\Site;
use CoreBundle\Exceptions\NonUniqueFieldValueException;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events as DoctrineEvent;

/**
 * Class SiteSubscriber
 *
 * @package UserBundle\EventListener
 */
class SiteSubscriber implements EventSubscriber
{

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            DoctrineEvent::prePersist,
            DoctrineEvent::preUpdate,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        /** @var Site $site */
        $site = $args->getEntity();
        if (!$site instanceof Site) {
            return;
        }

        $this->checkExistHost($site->getHost(), $site->getLanguage(), $args->getEntityManager());
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        /** @var Site $site */
        $site = $args->getEntity();

        if (!$site instanceof Site || !$args->hasChangedField('host')) {
            return;
        }

        $this->checkExistHost($site->getHost(), $site->getLanguage(), $args->getEntityManager());
    }

    /**
     * @param $host
     * @param $language
     *
     * @param EntityManager $em
     */
    private function checkExistHost($host, $language, EntityManager $em)
    {
        if ($em->getRepository(Site::class)->findByHost($host, $language)) {
            throw new NonUniqueFieldValueException('Host "'.$host.'" already exist');
        }
    }
}
