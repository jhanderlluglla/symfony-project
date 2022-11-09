<?php

namespace UserBundle\Controller;

use UserBundle\Form\StaticPageType;
use CoreBundle\Entity\StaticPage;

/**
 * Class StaticPagesController
 *
 * @package UserBundle\Controller
 */
class StaticPagesController extends AbstractCRUDController
{

    /**
     * @param StaticPage $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm($entity, $options = [])
    {
        $locales = $this->container->getParameter('locales');

        $options = [
                'locales' => array_combine($locales, $locales)
            ] + $options;

        return $this->createForm(StaticPageType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return StaticPage::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new StaticPage();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'static_pages';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('admin_static_page');
    }
}