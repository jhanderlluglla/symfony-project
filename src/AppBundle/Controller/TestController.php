<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use UserBundle\Services\OnlineConvertService;
use CoreBundle\Entity\User;

class TestController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        return $this->render('test/index.html.twig');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function testAction(Request $request)
    {
        $fileName = 'upload-test.docx';

        $articleStatisticService = $this->get('user.article_statistic_service');

        $resultDoc = $articleStatisticService->convertDoc($fileName);
        if ($resultDoc instanceof JsonResponse) {
            return $resultDoc;
        }

        $resultHtml = $articleStatisticService->convertToHtml($fileName);
        if ($resultHtml instanceof JsonResponse) {
            return $resultHtml;
        }

        return $this->json($resultDoc + $resultHtml);
    }
}