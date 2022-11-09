<?php

namespace UserBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Form\CategoryType;
use CoreBundle\Entity\Category;

/**
 * Class CategoryController
 *
 * @package UserBundle\Controller
 */
class CategoryController extends AbstractCRUDController
{
    /**
     * @param Category $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    protected function getForm($entity, $options = [])
    {
        $locales = $this->container->getParameter('locales');

        $options = [
                'locales' => array_combine($locales, $locales)
            ] + $options;

        return $this->createForm(CategoryType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return Category::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new Category();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('admin_category');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getCategoriesAction(Request $request)
    {
        $categoryRepository = $this->get('doctrine.orm.entity_manager')->getRepository(Category::class);

        return new JsonResponse($categoryRepository->getCategoriesByLanguage($request->get('language')));
    }

    /**
     * @param Request $request
     * @param Category $category
     */
    public function afterInsert(Request $request, $category)
    {
        $categoryRepository = $this->get('doctrine.orm.entity_manager')->getRepository(Category::class);
        $categoryRepository->reorderAll('name');
    }

    /**
     * @param Request $request
     * @param Category $oldCategory
     * @param Category $category
     */
    public function afterUpdate(Request $request, $oldCategory, $category)
    {
        $categoryRepository = $this->get('doctrine.orm.entity_manager')->getRepository(Category::class);
        $categoryRepository->reorderAll('name');
    }
}