<?php

namespace UserBundle\Controller;

use CoreBundle\Entity\AdminImage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use UserBundle\Form\AdminImageType;
use Pagerfanta\Pagerfanta;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use CoreBundle\Repository\AdminImageRepository;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Doctrine\Common\Annotations\AnnotationReader;
use UserBundle\Security\MainVoter;

/**
 * Class AdminImageController
 *
 * @package UserBundle\Controller
 */
class AdminImageController extends AbstractCRUDController
{

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function addAction(Request $request){

       if ($request->isMethod(Request::METHOD_POST)){
           $entity = $this->getEntityObject();
           $form = $this->getForm($entity, ['method' => Request::METHOD_POST]);
           $form->handleRequest($request);
           $fileName =  $form->get('filename')->getData();
           $this->moveImage($fileName);
       }

       return parent::addAction($request);
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     *
     * @throws EntityNotFoundException
     */
    public function editAction(Request $request,$id){
        /** @var AdminImage $adminImage */
        $adminImage = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if (is_null($adminImage)) {
            throw new EntityNotFoundException();
        }

        $currentFileName = $adminImage->getFilename();
        $form = $this->getForm($adminImage, ['method' => Request::METHOD_PUT]);

        if ($request->isMethod(Request::METHOD_PUT)){
            $form->handleRequest($request);
            $formFileName =  $form->get('filename')->getData();

            if ( $currentFileName != $formFileName )
                $this->moveImage($formFileName,true);
        }

        return parent::editAction($request,$id);
    }

    /*
     * @param string fileName
     * @param bool edition
     */
    private function moveImage($fileName, $editionChanged = false){
        $uploadAdminImagesDir = $this->getParameter('upload_admin_images_dir');

        /** @var LoggerInterface $monolog */
        $monolog = $this->get('logger');

        $tempPath = $uploadAdminImagesDir . '_tmp' . DIRECTORY_SEPARATOR . $fileName;
        $newPath = $uploadAdminImagesDir . DIRECTORY_SEPARATOR . $fileName;

        /** @var Filesystem $fileSystem */
        $fileSystem = new Filesystem();
        $fileSystem->copy($tempPath,$newPath);
        $fileSystem->remove($tempPath);

        if ($editionChanged){}

        $monolog->info('Move file: ' . $tempPath . ' --> ' .  $newPath);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function searchAction(Request $request)
    {
        $searchQuery = $request->query->get('search_query','');

        if (!$searchQuery && !$request->isXmlHttpRequest()){
            return $this->redirectToRoute('admin_images');
        }

        /** @var AdminImageRepository $repository */
        $repository = $this->getDoctrine()->getRepository(AdminImage::class);
        $queryBuilder = $repository->getSearchQueryBuilder($searchQuery);

        $adapter = PagerfantaAdapterFactory::getAdapterInstance($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);

        $page = $request->query->get('page', 1);
        $perPage = $request->query->get('per_page', 21);
        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        if ($request->isXmlHttpRequest()){
            // JMS?
            $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
            $normalizer = new ObjectNormalizer($classMetadataFactory);
            $encoder = new JsonEncoder();
            $serializer = new Serializer(array($normalizer), array($encoder));
            $responseData = [
                'collection' => $pagerfanta,
                'totalCount' => $pagerfanta->getNbResults(),
                'isPaginated' => $pagerfanta->haveToPaginate()
            ];
            $jsonResponse = $serializer->serialize($responseData,'json', ['groups' => ['search']]);
            return $this->json($jsonResponse);
        }else {
            return $this->render('admin_images/index.html.twig', array(
                'collection' => $pagerfanta,
                'search_query' => $request->query->get('query', '')
            ));
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function uploadAction(Request $request)
    {
        /** @var LoggerInterface $monolog */
        $monolog = $this->get('logger');

        $tmpPath = $this->getParameter('upload_admin_images_dir') . '_tmp';
        $baseLocalUrl = $this->getParameter('admin_images_local_path');
        $tempUrl = $baseLocalUrl . '_tmp/';

        $asset = $this->get('assets.packages');

        try {
            $image = $request->files->get('image');

            if (!$image)
                throw new \Exception();

            $fileName = md5(uniqid()) . '.' . $image->guessExtension();

            $image->move(
                $tmpPath,
                $fileName
            );

            $urlTemp = $asset->getUrl($tempUrl . $fileName);
            $fullUrlTemp = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . $urlTemp;

            $url = $asset->getUrl($baseLocalUrl . $fileName);
            $fullUrl = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . $url;

            return new JsonResponse([
                'status' => 'success',
                'filename' => $fileName,
                'urlTemp' => $fullUrlTemp,
                'url' => $fullUrl,
                'message' => '',
            ]);

        }catch (\Exception $e){

            $monolog->error("Error uploading admin image", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'status' => 'fail',
                'message' => $this->get('translator')->trans('error_upload_image', [], 'admin_images'),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param string  $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws EntityNotFoundException
     */
    public function deleteAction(Request $request, $id)
    {
        /** @var AdminImage $adminImage */
        $adminImage = $this->getDoctrine()->getRepository($this->getEntity())->find($id);

        if (is_null($adminImage)) {
            throw new EntityNotFoundException();
        }

        /** @var LoggerInterface $monolog */
        $monolog = $this->get('logger');

        $uploadAdminImagesDir = $this->getParameter('upload_admin_images_dir');
        $imagePath = $uploadAdminImagesDir . DIRECTORY_SEPARATOR . $adminImage->getFilename();

        /** @var Filesystem $fileSystem */
        $fileSystem = new Filesystem();
        $fileSystem->remove($imagePath);
        $monolog->info('Admin image: ' . $imagePath.' is deleted' );

        return parent::deleteAction($request,$id);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateNamespace()
    {
        return "admin_images";
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityObject()
    {
        return new AdminImage();
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return AdminImage::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function getForm($entity, $options = [])
    {
        return $this->createForm(AdminImageType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRedirectToRoute($entity, $action)
    {
        return $this->redirectToRoute('admin_images');
    }

}
