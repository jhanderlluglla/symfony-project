<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\Filter;
use CoreBundle\Repository\FilterRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Form\DirectoryList\DirectoriesListType;
use UserBundle\Form\DirectoryList\DirectoriesListRelationType;
use UserBundle\Form\Filters\DirectoriesListType as DirectoriesListFilter;
use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use UserBundle\Security\MainVoter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserBundle\Services\NetlinkingSchedule;

/**
 * Class DirectoriesListController
 *
 * @package UserBundle\Controller
 */
class DirectoriesListController extends AbstractCRUDController
{
    /**
     * @param Request $request
     * @param integer $id
     *
     * @return Response
     * @throws EntityNotFoundException
     */
    public function relationAction(Request $request, $id)
    {
        /** @var DirectoriesList $entity */
        $entity = $this->getDoctrine()->getRepository(DirectoriesList::class)->find($id);
        $em = $this->getDoctrine()->getManager();

        /** @var NetlinkingSchedule $netlinkingShedule */
        $netlinkingShedule = $this->get('user.netlinking_schedule');

        if (is_null($entity)) {
            throw new EntityNotFoundException();
        }

        $filters = $request->get('filters');
        $page = $request->get('page', 1);
        $perPage = $request->get('per-page', 20);
        $sortBy = $request->get('sortBy', "createdAt");
        $sortDirection = $request->get('sortDirection', "desc");

        /** @var FilterRepository $filterRepository */
        $filterRepository = $this->getDoctrine()->getRepository(Filter::class);

        if ($filters) {
            $filterRepository->save($this->getUser(), Filter::TYPE_DIRECTORY_LIST, $filters, $id);
        } else {
            $filter = $filterRepository->findByType($this->getUser(), Filter::TYPE_DIRECTORY_LIST, $id);
            if ($filter) {
                $filters = $filter->getData();
            } else {
                $filters = [];
                if (empty($filters['language'])) {
                    $filters['language'] = $this->getUser()->getLocale();
                }
            }
        }

        $filters['user'] = $this->getUser();
        $filters['sorting'] = isset($filters['sorting']) ? $filters['sorting']:'';
        $filters['nonOwner'] = true;

        $options = [
            'method' => Request::METHOD_PATCH,
            'filters' => $filters,
        ];
        $form = $this->createForm(DirectoriesListRelationType::class, $entity, $options);

        $filters['showPrice'] = true;

        if ($request->isMethod(Request::METHOD_PATCH)) {
            $data = $request->request->get($form->getName());
            $directories_and_blogs = $request->request->get('directories_and_blogs', []);
            $data['exchangeSite'] = $data['directories'] = [];
            foreach ($directories_and_blogs as $value) {
                $valueArray = explode('_', $value);
                if ($valueArray[0] == 'directory') {
                    $data['directories'][] = $valueArray[1];
                }
                if ($valueArray[0] == 'exchangeSite') {
                    $data['exchangeSite'][] = $valueArray[1];
                }
            }
            $request->request->set($form->getName(), $data);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $netlinkingShedule->updateSchedule($entity);
                $em->persist($entity);
                $em->flush();

                return $this->getRedirectToRoute($entity, 'relation');
            }
        }

        if (isset($filters['wordsCount']) && array_key_exists('min', $filters['wordsCount'])) {
            if (is_numeric($filters['wordsCount']['min'])) {
                $entity->setWordsCount($filters['wordsCount']['min']);
                $em->flush();
            }
        }

        if ($request->isXmlHttpRequest()) {
            $em->getFilters()->enable('softdeleteable');

            $filters['siteType'] = [ExchangeSite::EXCHANGE_TYPE, ExchangeSite::UNIVERSAL_TYPE];
            $filters['_type'] = $request->get('type');

            $unionService = $this->get('core.service.directory_exchange_site_union');
            $items = $unionService->getObjectsByFilters($filters, $page, $perPage, [$sortBy => $sortDirection]);

            $filters['showPrice'] = false;
            $totalCount = 0;
            if (empty($filters['_type']) || $filters['_type'] === 'exchange_site') {
                $totalCount += $this->getDoctrine()->getRepository(ExchangeSite::class)->countFilterResults($filters);
            }
            if (empty($filters['_type']) || $filters['_type'] === 'directory') {
                $totalCount += $this->getDoctrine()->getRepository(Directory::class)->countFilterResults($filters);
            }

            $response = [
                'items' => $this->getGridData($items, $entity),
                'form' => $this->renderView('directories_list/form.html.twig', ['form' => $form->createView()]),
                'totalEstimationPrice' => $this->getTotalEstimationPrice($entity),
                'countResults' => $totalCount
            ];

            return new JsonResponse($response);
        }

        $formFilter = $this->createForm(DirectoriesListFilter::class);

        $this->get('core.helper.form')->formSetValues($formFilter, $filters);

        $template = implode('/', [rtrim($this->getTemplateNamespace(), '/'), 'relation.html.twig']);
        return $this->render($template, [
            'formFilter' => $formFilter->createView(),
            'form' => $form->createView(),
            'id' => $id,
            'maxPerPage' => $perPage,
        ]);
    }

    public function duplicateAction(Request $request, $id)
    {
        $entity = $this->getDoctrine()->getRepository(DirectoriesList::class)->find($id);
        if (is_null($entity)) {
            throw new EntityNotFoundException();
        }

        $duplicate = $this->getEntityObject();
        $duplicate
            ->setName('Copy: ' . $entity->getName())
            ->setUser($entity->getUser())
            ->setWordsCount($entity->getWordsCount())
            ->setFilter($entity->getFilter())
            ->setDirectories($entity->getDirectories())
            ->setExchangeSite($entity->getExchangeSite())
        ;

        $em = $this->getDoctrine()->getManager();
        $em->persist($duplicate);
        $em->flush();

        return $this->getRedirectToRoute($entity, 'duplicate');
    }

    public function getPriceByIdAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            return false;
        }

        $translator = $this->get('translator');
        $id = $request->get('id');
        $directoryListId = $request->get('directory_list_id');
        $type = $request->get('type');

        if (!$id || !$type || !$directoryListId) {
            throw new BadRequestHttpException($translator->trans('errors.get_price_error', [], 'directories_list'));
        }
        $directoriesList = $this->getDoctrine()->getRepository(DirectoriesList::class)->find($directoryListId);
        if (is_null($directoriesList)) {
            throw new BadRequestHttpException($translator->trans('errors.get_price_error', [], 'directories_list'));
        }
        $response['price']  = 0;
        if ($type == 'directory') {
            $directory = $this->getDoctrine()->getRepository(Directory::class)->find($id);
            if (is_null($directory)) {
                throw new BadRequestHttpException($translator->trans('errors.get_price_error', [], 'directories_list'));
            }

            $response['price']  = $this->get('core.directory.model')->getDirectoryPrice($directory, $directoriesList);
        }
        if ($type == 'blog') {
            $blog = $this->getDoctrine()->getRepository(ExchangeSite::class)->find($id);
            if (is_null($blog)) {
                throw new BadRequestHttpException($translator->trans('errors.get_price_error', [], 'directories_list'));
            }

            $response['price']  = $this->get('core.blogs.model')->getBlogPrice($blog, $directoriesList);
        }

        return new JsonResponse($response);
    }

    /**
     * @param DirectoriesList $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm($entity, $options = [])
    {
        return $this->createForm(DirectoriesListType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return DirectoriesList::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new DirectoriesList();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'directories_list';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        if ($action == 'add') {
            return $this->redirectToRoute('admin_directories_list_relation', ['id' => $entity->getId()]);
        }

        return $this->redirectToRoute('admin_directories_list');
    }

    /**
     * {@inheritdoc}
     */
    protected function getCollectionData(Request $request, $filters = [])
    {
        $filters = $filters + [
                'user' => $this->getUser(),
            ];

        return $this->getDoctrine()->getRepository($this->getEntity())->filter($filters);
    }

    /**
     * @param Request         $request
     * @param DirectoriesList $entity
     */
    protected function beforeInsert(Request $request, $entity)
    {
        $entity->setUser($this->getUser());
    }

    /**
     * @param ExchangeSite[]|Directory[] $items
     * @param DirectoriesList $directoriesList
     *
     * @return mixed
     */
    private function getGridData($items, DirectoriesList $directoriesList)
    {
        $result = [];

        $exchangeSiteGridService = $this->get('core.blogs.model');
        $directoryGridService = $this->get('core.directory.model');

        foreach ($items as $item) {
            if ($item instanceof Directory) {
                $result['d' . $item->getId()] = $directoryGridService->transformItem($item, $directoriesList);
            } else {
                $result['es' . $item->getId()] = $exchangeSiteGridService->transformItem($item, $directoriesList);
            }
        }

        return $result;
    }

    /**
     * @return null|string
     */
    protected function getVoterNamespace()
    {
        return MainVoter::DIRECTORY_LIST;
    }

    /**
     * @param DirectoriesList $directoriesList
     *
     * @return array
     */
    private function getTotalEstimationPrice(DirectoriesList $directoriesList)
    {
        $query = $this->getDoctrine()
            ->getRepository(Directory::class)
            ->filter(['directoriesList' => $directoriesList])
            ->getQuery()
            ->getResult()
        ;

        $total_directory_price = $this->get('core.directory.model')->priceArray($query, $directoriesList);

        $query = $this->getDoctrine()
            ->getRepository(ExchangeSite::class)
            ->filter(['directoriesList' => $directoriesList])
            ->getQuery()
            ->getResult()
        ;
        $total_blog_price = $this->get('core.blogs.model')->priceArray($query, $directoriesList);

        $total_price = [];
        $total_price['total_selected_price'] = 0;
        $total_price['directories'] = [];
        $total_price['blogs'] = [];

        if ($total_directory_price) {
            foreach ($total_directory_price as $directory) {
                $total_price['directories'][$directory['id']] = (float)$directory['price'];
                if ($directory['selected']) {
                    $total_price['total_selected_price'] += $directory['price'];
                }
            }
        }
        if ($total_blog_price) {
            foreach ($total_blog_price as $blog) {
                $total_price['blogs'][$blog['id']] = (float)$blog['price'];
                if ($blog['selected']) {
                    $total_price['total_selected_price'] += $blog['price'];
                }
            }
        }
        if ($total_price['total_selected_price']) {
            $total_price['total_selected_price'] = round($total_price['total_selected_price'], 2);
        }

        return $total_price;
    }
}
