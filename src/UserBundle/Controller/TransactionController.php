<?php

namespace UserBundle\Controller;

use CoreBundle\Factory\PagerfantaAdapterFactory;
use Symfony\Component\HttpFoundation\Request;
use CoreBundle\Entity\Transaction;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Form\Filters\TransactionsFilterType;

/**
 * Class TransactionController
 *
 * @package UserBundle\Controller
 */
class TransactionController extends AbstractCRUDController
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $filterForm = $this->createForm(TransactionsFilterType::class);

        $filterForm->handleRequest($request);
        $filterFormData = $filterForm->getData();

        $queryBuilder = $this->getCollectionData($request, $filterFormData);
        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 20);

        return $this->render($this->prepareIndexTemplate(), [
            'collection' => PagerfantaAdapterFactory::getPagerfantaInstance($queryBuilder, $page, $perPage),
            'filter_form' => $filterForm->createView()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCollectionData(Request $request, $filters = [])
    {
        $filters['user'] = $this->getUser();

        return parent::getCollectionData($request, $filters);
    }

    /**
     * {@inheritdoc}
     */
    protected function getForm($entity, $options = [])
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return Transaction::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new Transaction();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'transaction';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('admin_transaction');
    }
}
