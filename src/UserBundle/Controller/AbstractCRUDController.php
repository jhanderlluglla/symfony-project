<?php

namespace UserBundle\Controller;

use CoreBundle\Factory\PagerfantaAdapterFactory;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Pagerfanta\Pagerfanta;
use CoreBundle\Entity\StateInterface;

/**
 * Class AbstractCRUDController
 *
 * @package UserBundle\Controller
 */
abstract class AbstractCRUDController extends Controller
{

    /**
     * @var string
     */
    protected $indexTemplate = 'index.html.twig';

    /**
     * @var string
     */
    protected $addTemplate = 'add.html.twig';

    /**
     * @var string
     */
    protected $editTemplate = 'edit.html.twig';

    /**
     * @var string
     */
    protected $showTemplate = 'show.html.twig';

    /**
     * @param int $id
     * @return Response
     * @throws EntityNotFoundException
     */
    public function showAction($id)
    {
        $entity = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        $this->checkAccess('show', $entity);

        if (is_null($entity)) {
            throw new EntityNotFoundException();
        }

        return $this->render($this->prepareShowTemplate(), [
            'entity' => $entity,
            'id' => $id,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $filters = $request->query->all();
        $queryBuilder = $this->getCollectionData($request, $filters);

        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per-page', 20);

        $pagerfanta = new Pagerfanta(PagerfantaAdapterFactory::getAdapterInstance($queryBuilder));

        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        return $this->render($this->prepareIndexTemplate(), [
            'collection' => $pagerfanta,
            'additionalData' => $this->getAdditionalData($request),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function addAction(Request $request)
    {
        $entity = $this->getEntityObject();

        $options = [
            'method' => Request::METHOD_POST,
        ];

        $form = $this->getForm($entity, $options);

        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->beforeInsert($request, $entity);
                $this->processSubmit($request, $entity, $form);
                $this->afterInsert($request, $entity);

                return $this->getRedirectToRoute($entity, 'add');
            }
        }

        return $this->render($this->prepareAddTemplate(), [
            'form' => $form->createView(),
            'entity' => $entity,
            'additionalData' => $this->getAdditionalData($request),
        ]);
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws EntityNotFoundException
     */
    public function editAction(Request $request, $id)
    {
        $entity = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if (is_null($entity)) {
            throw new EntityNotFoundException();
        }

        $this->checkAccess('edit', $entity);

        $oldEntity = clone $entity;

        $options = [
            'method' => Request::METHOD_PUT,
        ];

        $form = $this->getForm($entity, $options);

        if ($request->isMethod(Request::METHOD_PUT)) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->beforeUpdate($request, $oldEntity, $entity);
                $this->processSubmit($request, $entity, $form);
                $this->afterUpdate($request, $oldEntity, $entity);

                return $this->getRedirectToRoute($entity, 'edit');
            }
        }

        return $this->render($this->prepareEditTemplate(), [
            'form' => $form->createView(),
            'entity' => $entity,
            'id' => $id,
            'additionalData' => $this->getAdditionalData($request),
        ]);
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws EntityNotFoundException
     */
    public function deleteAction(Request $request, $id)
    {
        $entity = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        $this->checkAccess('delete', $entity);

        if (is_null($entity)) {
            throw new EntityNotFoundException();
        }

        $this->beforeDelete($request, $entity);

        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        $this->afterDelete($request, $entity);

        return $this->getRedirectToRoute($entity, 'delete', $request);
    }

    /**
     * @param Request $request
     * @param object  $entity
     * @param Form    $form
     *
     * @return void|mixed
     *
     * @throws \Exception
     */
    protected function processSubmit(Request $request, $entity, Form $form)
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @param Request $request
     * @param integer $id
     *
     * @return JsonResponse
     *
     * @throws EntityNotFoundException
     * @throws \Exception
     */
    public function endisAction(Request $request, $id)
    {
        $entity = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        $this->checkAccess('endis', $entity);

        if (is_null($entity)) {
            throw new EntityNotFoundException();
        }

        if (!($entity instanceof StateInterface)) {
            throw new \Exception('Expected entity instance of StateInterface');
        }

        $entity->setActive($entity->getActive() ? StateInterface::ACTIVE_NO:StateInterface::ACTIVE_YES);
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->flush();

        $translator = $this->get('translator');

        return $this->json([
            'action' => $entity->getActive() ? 'deactivate': 'activate',
            'text' => $entity->getActive() ? $translator->trans('deactivate', [], 'list'):$translator->trans('activate', [], 'list')
        ]);
    }

    /**
     * @return string
     */
    protected function prepareIndexTemplate()
    {
        return implode('/', [rtrim($this->getTemplateNamespace(),'/'), $this->indexTemplate]);
    }

    /**
     * @return string
     */
    protected function prepareAddTemplate()
    {
        return implode('/', [rtrim($this->getTemplateNamespace(),'/'), $this->addTemplate]);
    }

    /**
     * @return string
     */
    protected function prepareEditTemplate()
    {
        return implode('/', [rtrim($this->getTemplateNamespace(),'/'), $this->editTemplate]);
    }

    /**
     * @return string
     */
    protected function prepareShowTemplate()
    {
        return implode('/', [rtrim($this->getTemplateNamespace(),'/'), $this->showTemplate]);
    }

    /**
     * @param Request $request
     * @param array   $filters
     *
     * @return QueryBuilder|array
     */
    protected function getCollectionData(Request $request, $filters = [])
    {
        return $this->getDoctrine()->getRepository($this->getEntity())->filter($filters);
    }

    /**
     * @param Request $request
     * @param object  $entity
     */
    protected function beforeInsert(Request $request, $entity) {}

    /**
     * @param Request $request
     * @param object  $entity
     */
    protected function afterInsert(Request $request, $entity) {}

    /**
     * @param Request $request
     * @param object  $oldEntity
     * @param object  $entity
     */
    protected function beforeUpdate(Request $request, $oldEntity, $entity) {}

    /**
     * @param Request $request
     * @param object  $oldEntity
     * @param object  $entity
     */
    protected function afterUpdate(Request $request, $oldEntity, $entity) {}

    /**
     * @param Request $request
     * @param object  $entity
     */
    protected function beforeDelete(Request $request, $entity) {}

    /**
     * @param Request $request
     * @param object  $entity
     */
    protected function afterDelete(Request $request, $entity) {}

    /**
     * @param Request $request
     * @return array|null
     */
    protected function getAdditionalData(Request $request){
       return null;
    }

    /**
     * @return null
     */
    protected function getVoterNamespace()
    {
        return null;
    }

    /**
     * @param string $action
     * @param object $entity
     * @return bool
     */
    protected function checkAccess($action, $entity)
    {
        if($this->getVoterNamespace() !== null){
            $this->denyAccessUnlessGranted($this->getVoterNamespace() . '.' . $action, $entity);
        }
        return true;
    }

    /**
     * @return string
     */
    protected abstract function getTemplateNamespace();

    /**
     * @param null|integer $id
     *
     * @return object
     */
    protected abstract function getEntityObject();

    /**
     * @return string
     */
    protected abstract function getEntity();

    /**
     * @param object $entity
     * @param array  $options
     *
     * @return \Symfony\Component\Form\Form
     */
    protected abstract function getForm($entity, $options = []);

    /**
     * @param object $entity
     * @param string $action
     *
     * @return RedirectResponse
     */
    protected abstract function getRedirectToRoute($entity, $action);
}
