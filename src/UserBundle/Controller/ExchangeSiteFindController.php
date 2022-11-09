<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Helpers\FormHelper;
use CoreBundle\Repository\ExchangeSiteRepository;
use CoreBundle\Repository\SettingsRepository;
use CoreBundle\Services\CalculatorPriceService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use UserBundle\Form\Filters\ExchangeSiteType;
use UserBundle\Form\Filters\ProposalsFilterType;

use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;

use UserBundle\Form\ExchangeSiteFind\WritingErefererType;
use UserBundle\Form\ExchangeSiteFind\WritingWebmasterType;
use UserBundle\Form\ExchangeSiteFind\SubmitYourArticleType;

use UserBundle\Form\Filters\LanguageType;
use UserBundle\Security\ExchangePropositionVoter;
use UserBundle\Services\ExchangeSite\ExchangePropositionInterface;

/**
 * Class ExchangeSiteFindController
 *
 * @package UserBundle\Controller
 */
class ExchangeSiteFindController extends Controller
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted(ExchangePropositionVoter::ACTION_SHOW_LIST, ExchangeProposition::class);
        $this->getDoctrine()->getEntityManager()->getFilters()->enable('softdeleteable');
        $formFilter = $this->createForm(ExchangeSiteType::class);
        $formFilter->add('language', LanguageType::class, [
            'label' => 'form.language',
            'placeholder' => 'form.choose_language',
            'required' => false,
        ]);

        $formFilter->handleRequest($request);
        $filterQuery = $request->query->get($formFilter->getName());
        if (!$formFilter->isSubmitted() && $filterQuery) {
            $formFilter->submit($filterQuery);
        }
        $user = $this->getUser();

        $page = $request->query->get('page', 1);
        $blogsAsJson = [];

        if ($request->isXmlHttpRequest()) {
            $filter = $formFilter->getData();
            $filter = !is_null($filter) ? $filter:[];
            $filter['user'] = $user;
            $filter['nonOwner'] = true;
            $filter['enabledFilterPriceByWriting'] = true;
            $filter['siteType'] = [ExchangeSite::EXCHANGE_TYPE, ExchangeSite::UNIVERSAL_TYPE];

            $this->getDoctrine()->getEntityManager()->getFilters()->enable('softdeleteable');
            /** @var ExchangeSiteRepository $repository **/
            $repository = $this->getDoctrine()->getRepository(ExchangeSite::class);
            $queryBuilder = $repository->filter($filter);
            $countResults = $repository->filter($filter, true)->getQuery()->getSingleScalarResult();

            $pagerfanta = PagerfantaAdapterFactory::getPagerfantaInstance($queryBuilder, $page);
            $pagerfanta->setAllowOutOfRangePages(true);

            $blogsModel = $this->get("core.blogs.model");
            foreach ($pagerfanta->getCurrentPageResults() as $item) {
                $blogsAsJson[] = $blogsModel->transformItem($item);
            }
        } else {
            $countResults = 0;
            $pagerfanta = null;
        }

        /** @var SettingsRepository $settingsRepository */
        $settingsRepository = $this->getDoctrine()->getRepository(Settings::class);
        $webmasterAdditionalPay = $settingsRepository->getSettingValue(Settings::WEBMASTER_ADDITIONAL_PAY);

        $exchangePropositionRepository = $this->getDoctrine()->getRepository(ExchangeProposition::class);
        $proposalFilter = $this->createForm(ProposalsFilterType::class);
        $proposalFilter->handleRequest($request);
        $id = null;
        $pageProposition = $request->query->get('page-proposition', 1);
        if ($proposalFilter->isSubmitted() && $proposalFilter->getData()['id']) {
            $pageProposition = 1;
            $id = $proposalFilter->getData()['id'];
        }
        $queryBuilder = $exchangePropositionRepository->getWebmasterProposition($user, null, $id);
        $pagerfantaProposals = PagerfantaAdapterFactory::getPagerfantaInstance($queryBuilder, $pageProposition);

        $response = [
            'filterWords' => isset($filter['wordsCount'], $filter['wordsCount']['min']) ? $filter['wordsCount']['min'] : null,
            'filterPriceMax' => isset($filter['price'], $filter['price']['max']) ? $filter['price']['max'] : 99999999,
            'jsonBlogs' => json_encode($blogsAsJson),
            'collection' => $pagerfanta,
            'form' => $formFilter->createView(),
            'copywritingCalculator' => $this->get('core.service.calculator_price_service'),
            'webmasterAdditionalPay' => $webmasterAdditionalPay,
            'proposals' => $pagerfantaProposals,
            'countResults' => $countResults,
            'proposalFilter' => $proposalFilter->createView()
        ];

        if($request->isXmlHttpRequest()){
            return $this->render('exchange_site_find/table.html.twig', $response);
        }

        return $this->render('exchange_site_find/index.html.twig', $response);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function modalAction(Request $request)
    {
        $id = $request->get('id');
        $propositionId = $request->get('proposition_id');
        $type = $request->get('type');
        $translator = $this->get('translator');

        /** @var ExchangeSite $exchangeSite */
        $exchangeSite = $this->getDoctrine()->getRepository(ExchangeSite::class)->find($id);
        if (is_null($exchangeSite)) {
            return $this->json([
                'title' => $translator->trans('modal.error', [], 'exchange_site_find'),
                'body' => $translator->trans('modal.site_error', [], 'exchange_site_find'),
                'result' => 'fail',
            ]);
        }

        $exchangeProposition = null;
        if (!is_null($propositionId)) {
            $exchangePropositionRepository = $this->getDoctrine()->getRepository(ExchangeProposition::class);
            $exchangeProposition = $exchangePropositionRepository->find($propositionId);
        }

        /** @var User $user */
        $user = $this->getUser();

        /** @var CalculatorPriceService $calculatorPriceService */
        $calculatorPriceService = $this->get('core.service.calculator_price_service');
        $wordsPrice = $calculatorPriceService->getBasePrice($exchangeSite->getMinWordsNumber(), CalculatorPriceService::TOTAL_KEY);
        $imagesPrice = $calculatorPriceService->getImagesPrice($exchangeSite->getMaxImagesNumber(), CalculatorPriceService::TOTAL_KEY);
        $redactionPrice = $wordsPrice + $imagesPrice;
        $totalPrice = $redactionPrice + $exchangeSite->getCredits();

        $lowMoneyResponse = [
            'title' => $translator->trans('modal.error', [], 'exchange_site_find'),
            'body' => $translator->trans('modal.credit_error', [], 'exchange_site_find'),
            'result' => 'fail',
        ];

        switch ($type) {
            case ExchangeSite::ACTION_WRITING_EREFERER:
                if ($totalPrice > $user->getBalance()) {
                    return $this->json($lowMoneyResponse);
                }
                $form = $this->createForm(WritingErefererType::class);
                break;

            case ExchangeSite::ACTION_SUBMIT_ARTICLE:
                if ($exchangeSite->getCredits() > $user->getBalance()) {
                    return $this->json($lowMoneyResponse);
                }
                $form = $this->createForm(SubmitYourArticleType::class);
                break;

            case ExchangeSite::ACTION_WRITING_WEBMASTER:
                if ($exchangeSite->getCredits() > $user->getBalance()) {
                    return $this->json($lowMoneyResponse);
                }
                $form = $this->createForm(WritingWebmasterType::class);
                break;
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                /** @var ExchangePropositionInterface $exchangeSiteServices */
                $exchangeSiteServices = $this->get('user.exchange_proposition.' . $type);

                $response = $exchangeSiteServices->handler($id, $data, $exchangeProposition);

                if ($response instanceof JsonResponse) {
                    return $response;
                }

                return $this->json($response, ($response['status'] === false) ? Response::HTTP_BAD_REQUEST: Response::HTTP_OK);
            }
        }

        $body = $this->renderView('exchange_site_find/' . $type . '.html.twig', [
            'countWords' => $request->get('count_words'),
            'maxLinksNumber' => $exchangeSite->getMaxLinksNumber(),
            'minWordsNumber' => $exchangeSite->getMinWordsNumber(),
            'minImagesNumber' => $exchangeSite->getMinImagesNumber(),
            'maxImagesNumber' => $exchangeSite->getMaxImagesNumber(),
            'redactionPrice' => $redactionPrice,
            'totalPrice' => $totalPrice,
            'credits' => $exchangeSite->getCredits(),
            'form' => $form->createView(),
            'id' => $id,
            'propositionId' => $propositionId,
            'type' => $type,
            'authorizedAnchor' => $exchangeSite->getAuthorizedAnchor(),
            'headerOne' => $exchangeSite->getHeaderOneSet(),
            'minHeaderTwo' => $exchangeSite->getHeaderTwoStart(),
            'maxHeaderTwo' => $exchangeSite->getHeaderTwoEnd(),
            'minHeaderThree' => $exchangeSite->getHeaderThreeStart(),
            'maxHeaderThree' => $exchangeSite->getHeaderThreeEnd(),
            'boldText' => $exchangeSite->getBoldText(),
            'italicText' => $exchangeSite->getItalicText(),
            'quotedText' => $exchangeSite->getQuotedText(),
            'ulText' => $exchangeSite->getUlTag(),
            'links' => $exchangeProposition ? $exchangeProposition->getCopywritingOrders()->getLinks() : [],
        ]);

        return $this->json([
            'title' => $translator->trans(implode('.', ['modal', $type, 'title']), [], 'exchange_site_find'),
            'body' => $body,
            'result' => 'success',
        ]);
    }

    //bo/exchange-propsals-update-db-script
    public function scriptAction(Request $request)
    {

        $exchangePropositionRepository = $this->getDoctrine()->getEntityManager()->getRepository(ExchangeProposition::class);

        $query = $exchangePropositionRepository->createQueryBuilder('esp')
            ->select('esp.id')
            ->addselect('IDENTITY(esp.exchangeSite) as exchangeSite')
            ->addselect('esp.articleAuthorType as articleAuthorType')
            ->getQuery();

        $proposalForUpdate = $query->getArrayResult();

        $count_updates = 0;
        $exchangePropositionService = $this->get('core.service.exchange_proposition');

        foreach ($proposalForUpdate as $proposal) {
            $exchangeSite = $this->getDoctrine()->getEntityManager()->getRepository(ExchangeSite::class)->find($proposal['exchangeSite']);
            $exchangePropositionService->getTransactionDetails($proposal['articleAuthorType'], $exchangeSite, $totalPrice);

            $q = $this->getDoctrine()->getEntityManager()->createQuery('update CoreBundle\Entity\ExchangeProposition ep set ep. price = ?1 where ep.id = ?2')
                ->setParameter(1, $totalPrice)
                ->setParameter(2, $proposal['id']);

            $count_updates += $q->execute();

        }
        echo '<pre>'; var_dump($count_updates); echo '</pre>'; exit;
    }
}
