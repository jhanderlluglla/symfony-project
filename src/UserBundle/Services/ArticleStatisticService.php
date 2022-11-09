<?php

namespace UserBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ArticleStatisticService
 *
 * @package UserBundle\Services
 */
class ArticleStatisticService
{

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var OnlineConvertService
     */
    private $onlineConvertService;

    /**
     * @var string
     */
    private $uploadDocsDir;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * ArticleStatisticService constructor.
     *
     * @param OnlineConvertService $onlineConvertService
     */
    public function __construct($translator, OnlineConvertService $onlineConvertService, $uploadDocsDir)
    {
        $this->translator = $translator;
        $this->onlineConvertService = $onlineConvertService;
        $this->uploadDocsDir = $uploadDocsDir;
        $this->fs = new Filesystem();
    }

    /**
     * @return string
     */
    public function getUploadDocsDir()
    {
        return $this->uploadDocsDir;
    }

    /**
     * @param string $uploadDocsDir
     *
     * @return ArticleStatisticService
     */
    public function setUploadDocsDir($uploadDocsDir)
    {
        $this->uploadDocsDir = $uploadDocsDir;

        return $this;
    }

    /**
     * @param string $fileName
     *
     * @return array|JsonResponse
     */
    public function convertDoc($fileName)
    {
        $count = 0;

        $syncJob = [
            'input' => [
                [
                    'type' => \OnlineConvert\Endpoint\InputEndpoint::INPUT_TYPE_REMOTE,
                    'source' => $this->onlineConvertService->getGlobalFilePath($fileName)
                ]
            ],
            'conversion' => [
                [
                    'target' => 'docx'
                ]
            ]
        ];

        $result = $this->onlineConvertService->postFullJob($syncJob);

        if ($result['status']['code'] == OnlineConvertService::STATUS_CODE_FAIL) {
            throw new BadRequestHttpException($this->translator->trans('modal.submit_your_article.errors.file_convert', [], 'exchange_site_find'));
        }
        $localOutputedFilePath = $this->onlineConvertService->getLocalOutputedFilePath($result);
        $localOutputedDirPath = $this->onlineConvertService->getLocalOutputedDirPath($result);
        $localFilePath = $this->uploadDocsDir . DIRECTORY_SEPARATOR . $result['input']['0']['filename'];
        $extractPath = $localOutputedDirPath . DIRECTORY_SEPARATOR . 'extract';

        if ($this->fs->exists($localOutputedFilePath)) {
            $this->fs->copy($localOutputedFilePath, $localFilePath);

            $zip = new \ZipArchive;
            if ($zip->open($localOutputedFilePath) === true) {
                $zip->extractTo($extractPath);
                $zip->close();
            } else {
                throw new BadRequestHttpException($this->translator->trans('modal.submit_your_article.errors.zip_extract', [], 'exchange_site_find'));
            }

            $finder = new Finder();
            $count+= $finder->files()->in($extractPath . DIRECTORY_SEPARATOR . 'word/media')->name('*.jpg')->count();

            $finder = new Finder();
            $count+= $finder->files()->in($extractPath . DIRECTORY_SEPARATOR . 'word/media')->name('*.jpeg')->count();

            $finder = new Finder();
            $count+= $finder->files()->in($extractPath . DIRECTORY_SEPARATOR . 'word/media')->name('*.png')->count();
        }

        return [
            'images' => $count,
        ];
    }

    /**
     * @param string $fileName
     *
     * @return array|JsonResponse
     */
    public function convertToHtml($fileName)
    {
        $syncJob = [
            'input' => [
                [
                    'type' => \OnlineConvert\Endpoint\InputEndpoint::INPUT_TYPE_REMOTE,
                    'source' => $this->onlineConvertService->getGlobalFilePath($fileName)
                ]
            ],
            'conversion' => [
                [
                    'target' => 'html'
                ]
            ]
        ];

        $result = $this->onlineConvertService->postFullJob($syncJob);

        if ($result['status']['code'] == OnlineConvertService::STATUS_CODE_FAIL) {
            throw new BadRequestHttpException($this->translator->trans('modal.submit_your_article.errors.file_convert', [], 'exchange_site_find'));
        }

        $htmlFilePath = null;
        $localOutputedDirPath = $this->onlineConvertService->getLocalOutputedDirPath($result);
        if ($this->onlineConvertService->isLocalOutputedFileZip($result)) {
            $extractPath = $localOutputedDirPath . DIRECTORY_SEPARATOR . 'extract';

            $zip = new \ZipArchive;
            if ($zip->open($this->onlineConvertService->getLocalOutputedZipFilePath($result)) === true) {
                $zip->extractTo($extractPath);
                $zip->close();

                $htmlFilePath = $extractPath . DIRECTORY_SEPARATOR . $this->onlineConvertService->getHtmlFileName($result);
                if (!$this->fs->exists($htmlFilePath)) {
                    $htmlFilePath = null;
                }

                $this->fs->mirror($extractPath, $this->uploadDocsDir);

            } else {
                throw new BadRequestHttpException($this->translator->trans('modal.submit_your_article.errors.zip_extract', [], 'exchange_site_find'));
            }
        } else {
            if ($this->onlineConvertService->isLocalOutputedFileHtml($result)) {
                $htmlFilePath = $this->onlineConvertService->getLocalOutputedHtmlFilePath($result);

                $this->fs->mirror($localOutputedDirPath, $this->uploadDocsDir);
            }
        }

        if (is_null($htmlFilePath)) {
            throw new BadRequestHttpException($this->translator->trans('modal.submit_your_article.errors.html_file', [], 'exchange_site_find'));
        }

        $links = 0;
        $words = 0;
        $plaintext = [];
        $linksHref = [];
        $headers = [];

        $html = file_get_contents($htmlFilePath);

        $crawler = new Crawler($html);
        $subLinkCrawler = $crawler->filter('a');
        foreach ($subLinkCrawler as $domElement) {
            if (!empty($domElement->nodeValue)) {
                $links++;
                $linksHref[$domElement->nodeValue] = html_entity_decode($domElement->getAttribute('href'));
            }
        }

        $subWordsCrawler = $crawler->filter('p, h1, h2, h3, h4, h5, h6');
        foreach ($subWordsCrawler as $domElement) {
            $el = trim($domElement->nodeValue);
            if (!empty($el)) {
                $ct = count(explode(" ", $el));
                $words+=$ct;
                $plaintext[] = $el;
            }
        }

        $crawler = new Crawler($html);
        $subH1Crawler = $crawler->filter('h1');
        $headers['h1'] = $subH1Crawler->count();

        $subH2Crawler = $crawler->filter('h2');
        $headers['h2'] = $subH2Crawler->count();

        $subH3Crawler = $crawler->filter('h3');
        $headers['h3'] = $subH3Crawler->count();

        $subImgCrawler = $crawler->filter('img');
        $images = $subImgCrawler->count();

        $subBoldCrawler = $crawler->filter('b');
        $bold = $subBoldCrawler->count();

        $subItalicCrawler = $crawler->filter('i');
        $italic = $subItalicCrawler->count();

        $subLiCrawler = $crawler->filter('li');
        $ulList = $subLiCrawler->count();

        $text = implode(" ", $plaintext);
        $quote = 0;
        $quote += mb_substr_count($text, '“');
        $quote += mb_substr_count($text, '«');

        return [
            'links' => $links,
            'plaintext' => $text,
            'words' => $words,
            'links_href' => $linksHref,
            'images' => $images,
            'bold' => $bold,
            'italic' => $italic,
            'ul_tag' => $ulList,
            'quote' => $quote,
            'headers' => $headers,
        ];
    }
}
