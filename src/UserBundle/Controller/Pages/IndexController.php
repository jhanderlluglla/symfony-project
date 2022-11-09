<?php
namespace UserBundle\Controller\Pages;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class IndexController extends Controller
{
    public function pageListAction()
    {
        return $this->render('pages/page_list.html.twig');
    }
}