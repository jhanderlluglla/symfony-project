<?php

namespace UserBundle\Controller;

use UserBundle\Form\SettingsType;
use CoreBundle\Entity\Settings;
use UserBundle\Form\Filters\SettingsFilterType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SettingsController
 *
 * @package UserBundle\Controller
 */
class SettingsController extends AbstractCRUDController
{
    /**
     * @param Settings $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm($entity, $options = [])
    {
        return $this->createForm(SettingsType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return Settings::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new Settings();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('admin_settings');
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalData(Request $request)
    {
        $filterForm = $this->createForm(SettingsFilterType::class);
        $filterForm->handleRequest($request);

        return  ['filter_form' => $filterForm->createView()];
    }
}