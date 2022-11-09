<?php

namespace UserBundle\Controller;

use UserBundle\Form\EmailTemplateType;
use CoreBundle\Entity\EmailTemplates;

/**
 * Class EmailTemplateController
 *
 * @package UserBundle\Controller
 */
class EmailTemplateController extends AbstractCRUDController
{

    /**
     * @param EmailTemplates $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm($entity, $options = [])
    {
        $locales = $this->container->getParameter('locales');

        $options = [
                'locales' => array_combine($locales, $locales)
            ] + $options;

        return $this->createForm(EmailTemplateType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return EmailTemplates::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new EmailTemplates();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'email_templates';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('admin_email_template');
    }
}