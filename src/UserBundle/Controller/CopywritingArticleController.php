<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingArticleNonconform;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\User;
use CoreBundle\Validator\TextCorrectness;
use Doctrine\ORM\EntityNotFoundException;
use Gedmo\Exception\UnexpectedValueException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Validator\ConstraintViolation;
use UserBundle\Form\CopywritingArticleDeclineType;
use UserBundle\Form\CopywritingArticleType;
use CoreBundle\Entity\ExchangeSite;
use UserBundle\Monolog\ImageHandler;
use UserBundle\Security\MainVoter;

/**
 * Class CopywritingArticleController
 *
 * @package UserBundle\Controller
 */
class CopywritingArticleController extends AbstractCRUDController
{

    /**
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws EntityNotFoundException
     */
    public function showAction($id)
    {
        $article = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if (is_null($article)) {
            throw new EntityNotFoundException();
        }

        return $this->redirect($this->get('router')->generate('copywriting_order_show', [
            'id' => $article->getOrder()->getId()
        ]));
    }

    /**
     * @param Request $request
     * @param string $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws EntityNotFoundException
     * @throws \Exception
     */
    public function editAction(Request $request, $id)
    {
        /** @var CopywritingArticle $article */
        $article = $this->getDoctrine()->getRepository($this->getEntity())->find($id);
        $translator = $this->get('translator');

        if (is_null($article)) {
            throw new EntityNotFoundException();
        }

        $this->denyAccessUnlessGranted("copywritingArticle.edit", $article);

        $options = [
            'method' => Request::METHOD_PATCH,
        ];

        $oldArticle = clone $article;
        $oldText = $article->getText();
        $articleForm = $this->getForm($article, $options);
        $declineForm = $this->createForm(CopywritingArticleDeclineType::class, null, ['action' => $this->generateUrl('copywriting_order_decline', ['id' => $article->getOrder()->getId()]),]);


        if ($request->isMethod(Request::METHOD_PATCH)) {
            $articleForm->handleRequest($request);

            switch (true) {
                case $articleForm->get('validateAndSave')->isClicked():
                    if (!$articleForm->isValid()) {
                        break;
                    }
                    $order = $article->getOrder();
                    $orderWorkflow = $this->get('workflow.registry')->get($order);

                    $this->submitArticle($request, $article, $articleForm);
                    $this->afterUpdate($request, $oldArticle, $article);

                    /** @var User $user */
                    $user = $this->getUser();

                    if ($orderWorkflow->can($order, CopywritingOrder::TRANSITION_SUBMIT_TO_ADMIN)) {
                        $orderWorkflow->apply($order, CopywritingOrder::TRANSITION_SUBMIT_TO_ADMIN);
                    } elseif ($user->isAdmin() && $orderWorkflow->can($order, CopywritingOrder::TRANSITION_SUBMIT_TO_WEBMASTER)) {
                        $orderWorkflow->apply($order, CopywritingOrder::TRANSITION_SUBMIT_TO_WEBMASTER);
                        if ($orderWorkflow->can($order, CopywritingOrder::TRANSITION_COMPLETE_TRANSITION)) {
                            $orderWorkflow->apply($order, CopywritingOrder::TRANSITION_COMPLETE_TRANSITION);
                        }
                    }

                    $this->getDoctrine()->getManager()->flush();

                    return $this->getRedirectToRoute($article, 'edit', ($user->isAdmin() ? [CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN] : [CopywritingOrder::STATUS_PROGRESS, CopywritingOrder::STATUS_DECLINED]));

                case $articleForm->get('validate')->isClicked():
                    $valid = $articleForm->isValid();
                    break;

                case $articleForm->get('save')->isClicked():
                    $order = $article->getOrder();

                    $newText = $article->getText();

                    $copywritingOrderStatus = $article->getOrder()->getStatus();

                    if(!$this->getUser()->isWebmaster() &&  $copywritingOrderStatus != CopywritingOrder::STATUS_COMPLETED){
                        $this->processSubmit($request, $article, $articleForm);
                        if ($request->isXmlHttpRequest()) {
                            return $this->json([
                                'status' => 'success',
                                'message' => $translator->trans('ajax.saved.success', [], 'copywriting'),
                            ]);
                        }
                        break;
                    }

                    $copywritingArticleProcessor = $this->get('user.copywriting.article_processor');

                    /** @var ExchangeProposition $exchangeProposition */
                    $exchangeProposition = $order->getExchangeProposition();
                    if (!($exchangeProposition && $order->isCompleted())) {
                        return $this->getRedirectToRoute($article, 'edit');
                    }

                    $isOwn = $exchangeProposition->getType() === ExchangeProposition::OWN_TYPE;
                    if (($exchangeProposition && $isOwn) || $copywritingArticleProcessor->compareLinks($oldText, $newText)) {
                        $this->submitArticle($request, $article, $articleForm);

                        $exchangeProposition->setPagePublish(null);
                        $publishedStatus = $copywritingArticleProcessor->publish($article, $isOwn);

                        if ($publishedStatus === ExchangeSite::RESPONSE_CODE_PUBLISH_PENDING) {
                            $exchangeProposition
                                ->setModificationStatus(ExchangeProposition::MODIFICATION_STATUS_4)
                                ->setComments($translator->trans('comment_article_pending', [], 'copywriting'))
                            ;
                            $this->getDoctrine()->getManager()->flush();
                        } elseif ($publishedStatus !== ExchangeSite::RESPONSE_CODE_PUBLISH_SUCCESS) {
                            $exchangeProposition
                                ->setModificationStatus(ExchangeProposition::MODIFICATION_STATUS_1)
                                ->setComments($translator->trans('comment_article_edited', [], 'copywriting'))
                            ;
                            $this->getDoctrine()->getManager()->flush();
                        }
                        if ($copywritingOrderStatus == CopywritingOrder::STATUS_PROGRESS || $copywritingOrderStatus == CopywritingOrder::STATUS_DECLINED) {

                            return $this->getRedirectToRoute($article, 'edit');
                        }

                        return $this->getRedirectToRoute($article, 'edit', $copywritingOrderStatus);
                    } else {
                        $articleForm->addError(new FormError($translator->trans('requirements.links.not_same', [], 'copywriting')));
                    }
                    break;
            }
        }

        /** @var User $user */
        $user = $this->getUser();

        $isStartReviewTracker = false;
        $isShowReviewedModal = false;

        if ($user->isAdmin() || $user->isWriterAdmin()) {
            $isShowReviewedModal = $article->isNowReview() && $article->getAdminReview() !== $user;

            if (!$article->getAdminReview() || !$article->isNowReview()) {
                $article->setAdminReview($user);
                $this->getDoctrine()->getManager()->flush();
            }

            if ($article->getAdminReview() === $user) {
                $isStartReviewTracker = true;
            }
        }

        return $this->render($this->prepareEditTemplate(), [
            'form' => $articleForm->createView(),
            'decline_form' => $declineForm->createView(),
            'entity' => $article,
            'id' => $id,
            'valid' => isset($valid) ? $valid : null,
            'status' => $article->getOrder()->getStatus(),
            'pixabayKey' => $this->getParameter('pixabay_api_key'),
            'isShowReviewedModal' => $isShowReviewedModal,
            'isStartReviewTracker' => $isStartReviewTracker,
        ]);
    }

    /**
     * @param $request
     * @param CopywritingArticle $article
     * @param $articleForm
     * @throws \Exception
     */
    protected function submitArticle($request, $article, $articleForm)
    {
        $articleProcessor = $this->get('user.copywriting.article_processor');

        $articleProcessor->prepareArticle($article);

        $this->processSubmit($request, $article, $articleForm);

        if ($article->getOrder()->getExchangeProposition()) {
            $this->get('user.exchange.proposition_processor')->updateArticleImageFromCopywritingArticle($article->getOrder()->getExchangeProposition(), $article);
        }
    }

    /**
     * @param int $id
     * @return Response
     * @throws EntityNotFoundException
     * @throws UnexpectedValueException
     */
    public function downloadAction($id)
    {
        /** @var CopywritingArticle $article */
        $article = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if (is_null($article)) {
            throw new EntityNotFoundException();
        } elseif (!$article->getText()) {
            throw new UnexpectedValueException();
        }

        $filename = $article->getOrder()->getProject()->getTitle().'-'.$article->getOrder()->getTitle().'.html';
        $fileContent = $this->render('copywriting_article/download.html.twig', ['article' => $article])->getContent();

        $response = new Response($fileContent);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            "download.html"
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @param Request $request
     * @param object $oldEntity
     * @param CopywritingArticle $article
     */
    protected function afterUpdate(Request $request, $oldEntity, $article)
    {
        $constraint = new TextCorrectness();
        $validator = $this->get('core.validator.text_correctness');
        $errors = $validator->validateArticle($article, $constraint, true);

        $errorsNames = [];
        /** @var ConstraintViolation $violation */
        foreach ($errors as $violation) {
            $errorsNames[] = $violation->getParameters()['name'];
        }

        /** @var CopywritingArticleNonconform $nonconform */
        foreach ($article->getNonconforms() as $nonconform) {
            if (!in_array($nonconform->getRule(), $errorsNames)) {
                $article->removeNonconform($nonconform);
            }
        }
    }

    /**
     * @param ExchangeSite $entity
     * @param array        $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getForm($entity, $options = [])
    {
        return $this->createForm(CopywritingArticleType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return CopywritingArticle::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new CopywritingArticle();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return 'copywriting_article';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action, $status = [CopywritingOrder::STATUS_PROGRESS, CopywritingOrder::STATUS_DECLINED])
    {
        return $this->redirectToRoute('copywriting_order_list', ['status' => $status]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function uploadImageAction(Request $request)
    {
        $type = $request->get('type');

        /** @var LoggerInterface $monolog */
        $monolog = $this->get('logger');

        $tmpPath = $this->getParameter('upload_article_images_dir') . '_tmp';
        $tempUrl = $this->getParameter('article_images_local_path') . '_tmp/';

        $asset = $this->get('assets.packages');

        try {
            switch ($type){
                case "url":
                    $imageUrl = $request->request->get('url');
                    $ch = curl_init($imageUrl);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $image = curl_exec($ch);
                    $imageType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                    curl_close($ch);
                    if(!$image){
                        throw new \Exception("Curl failed, url: $imageUrl");
                    }
                    list($resourceType, $extension) = explode('/', $imageType);
                    if($resourceType !== "image"){
                        throw new \Exception("Unknown type of resource: $resourceType");
                    }

                    $fileName = md5(uniqid()) . "." . $extension;

                    $fullFilePath = $tmpPath . DIRECTORY_SEPARATOR . $fileName;

                    $fileSystem = $this->get('filesystem');
                    $fileSystem->dumpFile($fullFilePath, $image);

                    $monolog->info('Download file: ' . $imageUrl . ' --> ' .  $fullFilePath);

                    break;
                case "file":
                    $image = $request->files->get('image');
                    $fileName = md5(uniqid()) . '.' . $image->guessExtension();

                    $image->move(
                        $tmpPath,
                        $fileName
                    );

                    $monolog->info("Image $fileName saved");

                    break;
                default:
                    throw new \LogicException("Unknown type: $type");
            }

            $url = $asset->getUrl($tempUrl . $fileName);
            $fullUrl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . $url;

            return new JsonResponse([
                'status' => 'success',
                'url' => $fullUrl,
                'message' => '',
            ]);

        }catch (\Exception $e){
            $monolog->error("Error with saving file", [
                'type', $type,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'status' => 'fail',
                'message' => $this->get('translator')->trans('error_upload_image', [], 'copywriting'),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param  int $id
     * @return Response
     *
     * @throws EntityNotFoundException
     * @throws \Exception
     */
    public function toggleReviewAction(Request $request, $id)
    {
        /** @var CopywritingArticle $article */
        $article = $this->getDoctrine()->getRepository($this->getEntity())->find($id);
        if (is_null($article)) {
            throw new EntityNotFoundException();
        }

        $article->setAdminReview($this->getUser());
        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return new JsonResponse(['status' => true]);
    }

    /**
     * @return null|string
     */
    protected function getVoterNamespace()
    {
        return MainVoter::COPYWRITING_ARTICLE;
    }
}
