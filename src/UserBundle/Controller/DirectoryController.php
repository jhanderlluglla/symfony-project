<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\AbstractMetricsEntity;
use CoreBundle\Services\Metrics\MetricsManager;
use CoreBundle\Services\MozInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use UserBundle\Form\Directory\DirectoryAddType;
use CoreBundle\Entity\Directory;

class DirectoryController extends AbstractCRUDController
{
    /**
     * @param Directory $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm($entity, $options = [])
    {
        $options = [
            'user' => $this->getUser(),
        ] + $options;

        return $this->createForm(DirectoryAddType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return Directory::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new Directory();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'directory';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('admin_directory');
    }

    /**
     * @param Request $request
     * @param Directory $entity
     */
    protected function beforeDelete(Request $request, $entity)
    {
        $entity->removeFromAllDirectoryLists();
    }

    /**
     * @param Request $request
     * @param array $filters
     * @return array|\Doctrine\ORM\QueryBuilder
     */
    protected function getCollectionData(Request $request, $filters = [])
    {
        $this->getDoctrine()->getEntityManager()->getFilters()->enable('softdeleteable');
        return parent::getCollectionData($request, $filters);
    }

    /**
     * @param AbstractMetricsEntity $entity
     */
    private function updateMetrics(AbstractMetricsEntity $entity)
    {
        /** @var MetricsManager $metricsManager */
        $metricsManager = $this->get('core.service.metrics_manager');
        $metrics = $metricsManager->updateMetrics($entity->getSite());
        $metricsManager->updateMetricsEntitiesByMetrics($entity, $metrics);

        $this->getDoctrine()->getManager()->flush();
    }

    /**
     * @param Request $request
     * @param Directory $oldEntity
     * @param Directory $entity
     */
    protected function afterUpdate(Request $request, $oldEntity, $entity)
    {
        if ($oldEntity->getSite() !== $entity->getSite()) {
            $this->updateMetrics($entity);
        }
    }

    /**
     * @param Request $request
     * @param Directory $entity
     */
    protected function afterInsert(Request $request, $entity)
    {
        $this->updateMetrics($entity);
        $this->getDoctrine()->getManager()->flush();
    }
}
