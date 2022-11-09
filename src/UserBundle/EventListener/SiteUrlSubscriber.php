<?php

namespace UserBundle\EventListener;

use CoreBundle\Entity\Interfaces\LanguageInterface;
use CoreBundle\Entity\Interfaces\SiteUrlInterface;
use CoreBundle\Entity\Site;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events as DoctrineEvent;
use Doctrine\ORM\OptimisticLockException;

/**
 * Class SiteUrlSubscriber
 *
 * @package UserBundle\EventListener
 */
class SiteUrlSubscriber implements EventSubscriber
{

    /**
     * Returns an array of events this subscriber wants to listen to.
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
     *
     * @throws OptimisticLockException
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        if (!$args->getEntity() instanceof SiteUrlInterface) {
            return;
        }

        $this->updateSite($args->getEntity(), $args);
    }

    /**
     * @param PreUpdateEventArgs $args
     *
     * @throws OptimisticLockException
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        if (!$args->getEntity() instanceof SiteUrlInterface  || !$args->getEntity() instanceof LanguageInterface || !$args->hasChangedField('url')) {
            return;
        }

        $this->updateSite($args->getEntity(), $args);
    }

    /**
     * @param object|SiteUrlInterface|LanguageInterface $entity
     * @param LifecycleEventArgs $args
     *
     * @throws OptimisticLockException
     */
    private function updateSite(SiteUrlInterface $entity, LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $siteRepository = $em->getRepository(Site::class);
        $site = $siteRepository->findOrCreateByUrl($entity->getUrl(), $entity->getLanguage());

        $entity->setSite($site);
    }
}
