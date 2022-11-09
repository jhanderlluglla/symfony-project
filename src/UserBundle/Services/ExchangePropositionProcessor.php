<?php

namespace UserBundle\Services;

use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Services\ExchangePropositionService;
use Doctrine\ORM\EntityManager;
use DOMDocument;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class ExchangePropositionProcessor
{
    /** @var EntityManager $em */
    private $em;

    /**
     * @var EngineInterface $templating
     */
    private $templating;


    /**
     * @var string $uploadsDocsDir
     */
    private $uploadsDocsDir;


    /** @var ExchangePropositionService $exchangePropositionService*/
    private $exchangePropositionService;

    /**
     * CopywritingArticleProcessor constructor.
     * @param EntityManager $entityManager
     * @param EngineInterface $templating
     * @param string $uploadsDocsDir
     * @param ExchangePropositionService $exchangePropositionService
     */
    public function __construct(EntityManager $entityManager, EngineInterface $templating, string $uploadsDocsDir, ExchangePropositionService $exchangePropositionService)
    {
        $this->em = $entityManager;
        $this->templating = $templating;
        $this->uploadsDocsDir = $uploadsDocsDir;
        $this->exchangePropositionService = $exchangePropositionService;
    }

    /**
     * @param ExchangeProposition $proposition
     * @param CopywritingArticle $article
     */
    public function buildReport(ExchangeProposition $proposition, CopywritingArticle $article)
    {
        $proposition->setImagesNumber($this->countElementsByTag($article, 'img'));
        $proposition->setLinksNumber($this->countElementsByTag($article, 'a'));
        $proposition->setWordsNumber($article->getWordsNumber());
        $proposition->setPlaintext($article->getText());
    }

    /**
     * @param CopywritingArticle $article
     * @param $tag
     * @return int
     */
    private function countElementsByTag(CopywritingArticle $article, $tag)
    {
        $doc = new DOMDocument();
        $doc->loadHTML($article->getText());

        $elements = $doc->getElementsByTagName($tag);

        return $elements->length;
    }

    /**
     * @param CopywritingArticle $article
     *
     * @return string
     */
    public function savePreview(CopywritingArticle $article)
    {
        $fileName = uniqid().".html";

        $fileContent = $this->templating->render('copywriting_article/download.html.twig', ['article' => $article]);
        $preview = new DOMDocument();
        $preview->loadHTML($fileContent);

        $fileSystem = new Filesystem();
        if (!$fileSystem->exists($this->uploadsDocsDir)) {
            $fileSystem->mkdir($this->uploadsDocsDir);
        }

        $result = $preview->saveHTMLFile($this->uploadsDocsDir . DIRECTORY_SEPARATOR . $fileName);

        if($result === false){
            throw new IOException("Can't save file");
        }

        return $fileName;
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     * @param CopywritingArticle $article
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateArticleImageFromCopywritingArticle(ExchangeProposition $exchangeProposition, CopywritingArticle $article)
    {
        $fileName = $this->savePreview($article);
        $this->updateArticleImage($exchangeProposition, $fileName);
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     * @param $htmlFileName
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateArticleImage(ExchangeProposition $exchangeProposition, $htmlFileName)
    {
        if ($exchangeProposition->getDocumentImage() && $htmlFileName !== $exchangeProposition->getDocumentImage()) {
            $path = $this->uploadsDocsDir . DIRECTORY_SEPARATOR;
            $fileSystem = new Filesystem();
            $fileSystem->remove($path . $exchangeProposition->getDocumentImage());
            $fileSystem->rename($path . $htmlFileName, $path . $exchangeProposition->getDocumentImage());
        } else {
            $exchangeProposition->setDocumentImage($htmlFileName);
            $this->em->flush();
        }
    }
}
