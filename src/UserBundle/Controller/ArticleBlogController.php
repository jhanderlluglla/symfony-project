<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\ArticleBlog;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Form\ArticleBlogType;

class ArticleBlogController extends AbstractCRUDController
{
    protected function getTemplateNamespace()
    {
        return "article_blog";
    }

    protected function getEntityObject()
    {
        return new ArticleBlog();
    }

    protected function getEntity()
    {
        return ArticleBlog::class;
    }

    protected function getForm($entity, $options = [])
    {
        return $this->createForm(ArticleBlogType::class, $entity, $options);
    }

    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('article_blog');
    }

    /**
     * @param Request $request
     * @param string $urlPath
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function viewAction(Request $request, $urlPath)
    {
        $articleBlog = $this->getDoctrine()->getRepository(ArticleBlog::class)->findOneBy([
            'urlPath' => $urlPath,
            'language' => $request->getLocale()
        ]);

        if (is_null($articleBlog)) {
            throw $this->createNotFoundException();
        }

        $csrfToken = $this->has('security.csrf.token_manager')
            ? $this->get('security.csrf.token_manager')->getToken('authenticate')->getValue()
            : null;

        return $this->render('article_blog/view.html.twig', [
            'csrf_token' => $csrfToken,
            'articleBlog' => $articleBlog,
        ]);
    }
}
