<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\Candidate;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\Transaction;
use CoreBundle\Entity\User;
use CoreBundle\Entity\WaitingOrder;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Repository\SettingsRepository;
use CoreBundle\Services\CalculatorPriceService;
use CoreBundle\Services\TransactionService;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use UserBundle\Form\CopywritingProjectType;
use CoreBundle\Entity\ExchangeSite;
use UserBundle\Security\MainVoter;

/**
 * Class CopywriterProjectController
 *
 * @package UserBundle\Controller
 */
class CopywritingProjectController extends AbstractCRUDController
{
    /**
     * @param $id
     * @return JsonResponse
     * @internal param Request $request
     *
     */
    public function templateAction($id)
    {
        $userId = $this->getUser()->getId();

        $template = $this
            ->getDoctrine()
            ->getRepository(CopywritingProject::class)
            ->findOneBy(
                [
                    'id' => $id,
                    'customer' => $userId,
                    'template' => true,
                ]);

        if (!$template) {
            throw new NotFoundHttpException("Not found");
        }

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $encoder = new JsonEncoder();
        $serializer = new Serializer(array($normalizer), array($encoder));
        $jsonTemplate = $serializer->serialize($template,'json', ['groups' => ['template']]);
        // addition "Language" here without changing the LanguageTrait
        $templateSerialized = $encoder->decode($jsonTemplate,'json');
        $templateSerialized['language'] = $template->getLanguage();
        $jsonTemplate = $encoder->encode($templateSerialized,'json');
        return $this->json($jsonTemplate);
    }

    /**
     * @param ExchangeSite $entity
     * @param array        $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getForm($entity, $options = [])
    {
        $this->getDoctrine()->getEntityManager()->getFilters()->enable('softdeleteable');

        /** @var SettingsRepository $settingrepository */
        $settingrepository = $this->getDoctrine()->getRepository(Settings::class);

        $identtificators = array_map(function ($elem){ return "price_" . $elem;},CopywritingProject::WRITER_CATEGORIES);
        $prices = $settingrepository->getSettingsByIdentificators($identtificators);

        $categoryPrices = [];
        foreach (CopywritingProject::WRITER_CATEGORIES as $category){
            if(array_key_exists('price_' . $category, $prices)){
                $categoryPrices[$category] = $prices['price_' . $category];
            }else{
                $categoryPrices[$category] = 0;
            }
        }

        $options['customer'] = $this->getUser()->getId();
        $options['calculator_price_service'] = $this->get('core.service.calculator_price_service');
        $options['category_price'] = $categoryPrices;

        return $this->createForm(CopywritingProjectType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return CopywritingProject::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        $copywritingProject = new CopywritingProject();
        $copywritingProject->setCustomer($this->getUser());

        return $copywritingProject;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'copywriting_project';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('copywriting_order_list',['status' => ['waiting']]);
    }

    /**
     * @param Request $request
     * @param CopywritingProject $project
     * @throws EntityNotFoundException
     */
    protected function beforeInsert(Request $request, $project)
    {
        $ordersCount = $project->getOrders()->count();

        $user = $this->getUser();
        $project->setCustomer($user);

        if (isset($request->get('copywriting_project')['exchange_site']) && $request->get('copywriting_project')['exchange_site']) {
            $exchangeSiteId = $request->get('copywriting_project')['exchange_site'];

            /** @var ExchangeSite $exchangeSite */
            $exchangeSite = $this->getDoctrine()->getRepository(ExchangeSite::class)->find($exchangeSiteId);

            if (!$exchangeSite) {
                throw new EntityNotFoundException();
            }

            $project->setLanguage($exchangeSite->getLanguage());

            /** @var CopywritingOrder $order */
            foreach ($project->getOrders() as $order) {
                $exchangeProposition = new ExchangeProposition();
                $exchangeProposition
                    ->setUser($user)
                    ->setExchangeSite($exchangeSite)
                    ->setType(ExchangeProposition::OWN_TYPE)
                    ->setCredits(0)
                    ->setArticleAuthorType(ExchangeProposition::ARTICLE_AUTHOR_WRITER)
                ;
                $order->setExchangeProposition($exchangeProposition);
            }
        }

        if ($project->isRecurrent()) {

            $period = $project->getRecurrentPeriod();
            $periodInterval = new \DateInterval('P' . $period . 'D');
            $recurrentTotal = $project->getRecurrentTotal() - 2;

            $orders = clone $project->getOrders();

            for($count = $ordersCount, $createdAt = (new \DateTime())->add($periodInterval);
                $count < $project->getRecurrentTotal();
                $count += $ordersCount, $createdAt->add($periodInterval)) {

                foreach($orders as $order) {
                    if($recurrentTotal--) {
                        $order = clone $order;
                        $order->setCreatedAt(clone $createdAt);
                        $project->addOrder($order);
                    }
                }
            }
        }

        $chosenWriters = $request->get('chosen_writers');
        if(!empty($chosenWriters)){
            $writersIds = explode(',', $request->get('chosen_writers'));

            $writers = $this->getDoctrine()->getRepository(User::class)->findById($writersIds);

            if(count($writers) != count($writersIds)) {
                throw new EntityNotFoundException("Writers with ids: " . implode(',', $writersIds));
            }

            $chooseWriterService = $this->get('core.service.choose_writer');
            $em = $this->getDoctrine()->getManager();
            $orders = $project->getOrders();

            $wordsOfOrders = $wordsOfOrders = $this->getDoctrine()->getEntityManager()->getRepository(CopywritingOrder::class)->getWordsOfOrders($writersIds);
            foreach ($orders as $order){
                $waitingOrder = new WaitingOrder();
                $waitingOrder->setCopywritingOrder($order);

                foreach ($writers as $writer) {
                    $candidate = new Candidate();
                    $candidate->setUser($writer);

                    if(key_exists($writer->getId(), $wordsOfOrders)){
                        $deadline = $chooseWriterService->getDeadlineForWriter($writer->getWordsPerDay(), $wordsOfOrders[$writer->getId()]);
                    }else{
                        $deadline = $chooseWriterService->getDeadlineForWriter($writer->getWordsPerDay(), null);
                    }
                    $candidate->setDeadline($deadline);
                    $waitingOrder->addCandidate($candidate);
                }

                $em->persist($waitingOrder);
            }

            $em->flush();
        }
    }

    /**
     * @param Request $request
     * @param CopywritingProject $project
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function afterInsert(Request $request, $project)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var CalculatorPriceService $calculatorPriceService */
        $calculatorPriceService = $this->get('core.service.calculator_price_service');

        /** @var CopywritingOrder $order */
        foreach ($project->getOrders() as $order) {
            $additionalCost = $calculatorPriceService->getChooseWriterPrice($order->getWordsNumber(), $project->getWriterCategory(), CalculatorPriceService::TOTAL_KEY);
            $order->setAmount($order->getAmount() + $additionalCost);

            $em->persist($order);
        }

        /** @var TransactionService $transactionService */
        $transactionService = $this->get('core.service.transaction');

        $ordersCount = null;
        if ($project->getOrders()->count() > 1) {
            $details = new TransactionDescriptionModel(
                'copywriting_order.multi_project_payment',
                [
                    '%articles_count%' => $project->getOrders()->count(),
                    '%project_title%' => $project->getTitle()
                ]
            );
        } else {
            $details = new TransactionDescriptionModel(
                'copywriting_order.project_payment',
                [
                    '%project_title%' => $project->getTitle()
                ]
            );
        }

        $buyerTransaction = $transactionService->handling(
            $this->getUser(),
            $details,
            0,
            $project->getAmount(),
            $transactionService->getCopywritingProjectTransactionData($project),
            [CopywritingProject::TRANSACTION_TAG_PROJECT]
        );

        foreach ($project->getOrders() as $order) {
            $order->addTransaction($buyerTransaction);
        }

        $em->flush();
    }

    /**
     * @return null|string
     */
    protected function getVoterNamespace()
    {
        return MainVoter::COPYWRITING_PROJECT;
    }

    protected function getAdditionalData(Request $request)
    {
        /** @var SettingsRepository $settingRepository */
        $settingRepository = $this->getDoctrine()->getRepository(Settings::class);

        $identificators = array_map(function ($elem){ return "price_" . $elem;},CopywritingProject::WRITER_CATEGORIES);
        $identificators[] = Settings::PRICE_PER_100_WORDS;
        $identificators[] = Settings::PRICE_PER_IMAGE;
        $identificators[] = Settings::EXPRESS_RATE;
        $identificators[] = Settings::PRICE_FOR_META_DESCRIPTION;

        $prices = $settingRepository->getSettingsByIdentificators($identificators);

        return [
            'prices' => $prices,
        ];
    }
}