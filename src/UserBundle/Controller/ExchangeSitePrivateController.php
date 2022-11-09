<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\ExchangeSite;
use Doctrine\ORM\EntityNotFoundException;
use Pagerfanta\Adapter\ArrayAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ExchangeSitePrivateController extends Controller
{
    /**
     * @return Response
     */
    public function privateAction()
    {
        $this->getDoctrine()->getEntityManager()->getFilters()->enable('softdeleteable');
        $exchangeSiteRepository = $this->getDoctrine()->getRepository(ExchangeSite::class);
        $filters = [
            'isPrivateSite' => 1,
        ];
        if($this->getUser()->isWebmaster()){
            $filters['user'] = $this->getUser();
        }
        $privateSites = $exchangeSiteRepository->findBy($filters);
        $filters['isPrivateSite'] = 0;
        $notPrivateSites = $exchangeSiteRepository->findBy($filters);
        $adapter = new ArrayAdapter($privateSites);
        $pagerfanta = new Pagerfanta($adapter);

        return $this->render('exchange_site/private.html.twig', [
            'collection' => $pagerfanta,
            'notPrivateSites' => $notPrivateSites
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function massPrivateAction(Request $request)
    {
        /** @var Translator $translator */
        $translator = $this->get('translator');
        try {
            $ids = $request->get('ids');

            if (!empty($ids) && is_array($ids)) {
                $this->getDoctrine()->getRepository(ExchangeSite::class)->massPrivate($ids);
            }
        }
        catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $translator->trans('private_error', [], 'exchange_site')
            ]);
        }
        return $this->json([
            'status' => 'success',
            'message' => $translator->trans('private_success', [], 'exchange_site')
        ]);
    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws EntityNotFoundException
     */
    public function makePublicAction($id)
    {
        /** @var ExchangeSite $exchangeSite */
        $exchangeSite = $this->getDoctrine()->getRepository(ExchangeSite::class)->find($id);

        if(is_null($exchangeSite)){
            throw new EntityNotFoundException("Exchange site with $id not found");
        }

        $exchangeSite->setIsPrivateSite(false);
        $em = $this->getDoctrine()->getManager();
        $em->persist($exchangeSite);
        $em->flush();

        return $this->redirectToRoute('admin_exchange_site_private');
    }
}