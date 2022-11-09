<?php

namespace UserBundle\Controller\Pages;

use CoreBundle\Entity\Page\Homepage;
use Symfony\Component\HttpFoundation\RedirectResponse;
use UserBundle\Controller\AbstractCRUDController;
use UserBundle\Form\Page\HomepageType;

class HomepageController extends AbstractCRUDController
{
    protected function getTemplateNamespace()
    {
        return "pages/homepage";
    }

    protected function getEntityObject()
    {
        return new Homepage();
    }

    protected function getEntity()
    {
        return Homepage::class;
    }

    protected function getForm($entity, $options = [])
    {
        $locales = $this->getParameter('locales');
        $options['locales'] = array_combine($locales, $locales);
        return $this->createForm(HomepageType::class, $entity, $options);
    }

    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('pages_homepage');
    }
}