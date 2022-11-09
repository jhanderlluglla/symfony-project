<?php

namespace AppBundle\Controller;

use AppBundle\Services\PluginService;
use CoreBundle\Entity\ArticleBlog;
use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\Directory;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Page\Homepage;
use CoreBundle\Entity\Settings;
use Doctrine\ORM\EntityNotFoundException;
use Psr\Log\LoggerAwareInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;

use CoreBundle\Entity\AffiliationClick;
use CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class IndexController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $locale = $request->getLocale();

        $homepageRepository = $this->getDoctrine()->getRepository(Homepage::class);
        $languageFilter = ['language' => $locale];
        $defaultLanguage = ['language' => Language::EN];

        $homepage = $homepageRepository->findOneBy($languageFilter);

        $articleBlogs = $this->getDoctrine()->getRepository(ArticleBlog::class)->findBy([
            'isEnable' => true
        ] + $languageFilter);

        if (is_null($homepage)) {
            $homepage = $homepageRepository->findOneBy($defaultLanguage);
        }

        $csrfToken = $this->has('security.csrf.token_manager')
            ? $this->get('security.csrf.token_manager')->getToken('authenticate')->getValue()
            : null;

        return $this->render('index/index.html.twig', [
            'csrf_token' => $csrfToken,
            'homepage' => $homepage,
            'exchange_sites_total' => $this->getDoctrine()->getRepository(ExchangeSite::class)->getCountAll(),
            'directories_total' => $this->getDoctrine()->getRepository(Directory::class)->getCountAll(),
            'price_on_homepage' => $this->getDoctrine()->getRepository(Settings::class)->getSettingValue(Settings::PRICE_ON_HOMEPAGE),
            'article_blogs' => $articleBlogs
        ]);
    }

    /**
     * @param Request $request
     * @param string  $hash
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function affiliationAction(Request $request, $hash)
    {
        $session = $request->getSession();

        if (!$session->has('affiliation')) {
            $em = $this->getDoctrine()->getManager();

            $user = $this->getDoctrine()->getRepository(User::class )->getAffilationUser($hash);

            if (is_null($user)) {
                throw new EntityNotFoundException($user);
            }

            $affiliationClick = new AffiliationClick();

            $affiliationClick
                ->setUser($user);
            ;

            $em->persist($affiliationClick);
            $em->flush();

            $session->set('affiliation', $hash);
        }

        return $this->redirectToRoute('fos_user_registration_register');
    }

    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function downloadPluginAction(Request $request)
    {
        $customPluginName = $request->get('fileName',PluginService::DEFAULT_NAME);

        /** @var PluginService $pluginService */
        $pluginService = $this->get('app.service.plugin');

        $zipPath = $pluginService->createZip($customPluginName);

        $response = new BinaryFileResponse($zipPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "$customPluginName.zip");
        return $response;
    }
}
