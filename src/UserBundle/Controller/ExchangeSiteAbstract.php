<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use CoreBundle\Entity\ExchangeProposition;

/**
 * Class ExchangeSiteAbstract
 *
 * @package UserBundle\Controller
 */
abstract class ExchangeSiteAbstract extends Controller
{
    /**
     * @param integer $id
     *
     * @return array|ExchangeProposition
     */
    protected function existsProposal($id)
    {
        $translator = $this->get('translator');

        /** @var ExchangeProposition $entity */
        $entity = $this->getDoctrine()->getRepository(ExchangeProposition::class)->find($id);
        if (is_null($entity)) {
            return [
                'result' => 'fail',
                'title' => $translator->trans('modal.error', [], 'exchange_site_proposals'),
                'body' => $translator->trans('modal.site_error', [], 'exchange_site_proposals'),
            ];
        }

        return $entity;
    }

    /**
     * @param ExchangeProposition $entity
     *
     * @return array
     */
    abstract protected function canRead($entity);
}