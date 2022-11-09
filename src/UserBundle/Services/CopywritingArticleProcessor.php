<?php

namespace UserBundle\Services;

use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Settings;
use CoreBundle\Model\ArticleEarning;
use CoreBundle\Services\CalculatorPriceService;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Translation\TranslatorInterface;
use UserBundle\Services\BonusCalculator\CopywritingAdminBonusCalculator;
use UserBundle\Services\BonusCalculator\CopywritingWriterBonusCalculator;

/**
 * Class CopywritingReportBuilder
 *
 * @package UserBundle\Services
 */
class CopywritingArticleProcessor
{
    const TAGS = [
        'h1' => [
            'value' => 'h1',
            'name' => 'H1_set',
            'keyword' => 'keyword_H1_set',
            'maxCount' => 1
        ],
        'h2' => [
            'value' => 'h2',
            'name' => 'H2_range',
            'keyword' => 'keyword_H2_set',
        ],
        'h3' => [
            'value' => 'h3',
            'name' => 'H3_range',
            'keyword' => 'keyword_H3_set',
        ],
        'ul' => [
            'value' => 'ul',
            'name' => 'UL_set',
        ],
        'b' => [
            'value' => 'strong',
            'name' => 'bold_text',
        ],
        'q' => [
            'value' => 'blockquote',
            'name' => 'quoted_text',
        ],
        'i' => [
            'value' => 'em',
            'name' => 'italic_text',
        ],
        'img' => [
            'value' => 'img',
            'name' => 'images_range',
        ]
    ];

    const H1_PATTERN = '/<h1(?:.*)?>(.*)<\/h1>/Uis';

    public const RESPONSE_KEY_PUBLISH_STATUS = 'publish_status';

    public const RESPONSE_PUBLISH_STATUS_PUBLISH = 'publish';
    public const RESPONSE_PUBLISH_STATUS_PENDING = 'pending';

    /** @var EntityManager $em */
    private $em;

    /** @var CopywritingWriterBonusCalculator $writerBonusCalculator */
    private $writerBonusCalculator;

    /** @var CopywritingAdminBonusCalculator $adminBonusCalculator */
    private $adminBonusCalculator;

    /** @var CalculatorPriceService $calculatorPriceService */
    private $calculatorPriceService;

    /** @var LoggerInterface $monolog */
    private $monolog;

    /** @var TranslatorInterface $translator */
    private $translator;

    /** @var string $articleImagesLocalPath */
    private $articleImagesLocalPath;

    /** @var string $uploadArticleImagesDir */
    private $uploadArticleImagesDir;

    /** @var FileSystem $fileSystem */
    private $fileSystem;

    /** @var string $siteUrl */
    private $siteUrl;

    /**
     * CopywritingArticleProcessor constructor.
     * @param EntityManager $entityManager
     * @param CopywritingWriterBonusCalculator $writerBonusCalculator
     * @param CopywritingAdminBonusCalculator $adminBonusCalculator
     * @param CalculatorPriceService $calculatorPriceService
     * @param LoggerInterface $monolog
     * @param TranslatorInterface $translator
     * @param $articleImagesLocalPath
     * @param $uploadArticleImagesDir
     * @param Filesystem $fileSystem
     * @param $siteUrl
     */
    public function __construct(
        EntityManager $entityManager,
        CopywritingWriterBonusCalculator $writerBonusCalculator,
        CopywritingAdminBonusCalculator $adminBonusCalculator,
        CalculatorPriceService $calculatorPriceService,
        LoggerInterface $monolog,
        TranslatorInterface $translator,
        $articleImagesLocalPath,
        $uploadArticleImagesDir,
        FileSystem $fileSystem,
        $siteUrl
    ) {
        $this->em = $entityManager;
        $this->writerBonusCalculator = $writerBonusCalculator;
        $this->adminBonusCalculator = $adminBonusCalculator;
        $this->calculatorPriceService = $calculatorPriceService;
        $this->monolog = $monolog;
        $this->translator = $translator;
        $this->articleImagesLocalPath = $articleImagesLocalPath;
        $this->uploadArticleImagesDir = $uploadArticleImagesDir;
        $this->fileSystem = $fileSystem;
        $this->siteUrl = $siteUrl;
    }

    /**
     * @param CopywritingArticle $article
     * @param bool $force
     *
     * @return bool
     */
    public function sendArticle(CopywritingArticle $article, $force = false)
    {
        $order = $article->getOrder();
        $proposition = $order->getExchangeProposition();
        $exchangeSite = $proposition->getExchangeSite();

        if (!$proposition || !$exchangeSite || !$exchangeSite->getPluginUrl() || ($proposition->getType() === ExchangeProposition::EXTERNAL_TYPE && !$exchangeSite->isAutoPublish())) {
            return false;
        }

        $title = $this->parsePostTitle($article->getText());
        if ($title === "") {
            $title = $article->getMetaTitle() ?: $this->translator->trans('default_article_title', [], 'copywriting');
        }
        $data = [
            'id' => $article->getId(),
            'post_title' => $title,
            'post_content' => $this->removeFirstH1FromText($article->getText()),
            'categories' => $article->getRubricsExtIds(),
            'front_image' => $article->getFrontImage(),
            'force' => $force
        ];

        if ($article->getMetaTitle()) {
            $data['meta_title'] = $article->getMetaTitle();
        }

        if ($article->getMetaDesc()) {
            $data['meta_description'] = $article->getMetaDesc();
        }

        if ($article->getImageSources()) {
            $data['custom_field'] = $article->getImageSources();
        }

        $this->monolog->info("Send article to the plugin with data", $data);
        $response = $this->send($data, $exchangeSite->getPluginUrl(), $exchangeSite->getApiKey());

        if ($response['status'] === false) {
            $context = [
                'orderId' => $order->getId(),
                'exchangeSitePluginUrl' => $exchangeSite->getPluginUrl(),
                'exchangeSiteApiKey' => $exchangeSite->getApiKey(),
                'response' => $response
            ];

            $this->monolog->error("Error publish article, response:", $context);
        } else {
            $this->monolog->info("Success publish article, response", $response);
        }

        return $response;
    }

    /**
     * @param CopywritingArticle $article
     * @param bool $force
     *
     * @return string
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function publish(CopywritingArticle $article, $force = false)
    {
        $proposition = $article->getOrder()->getExchangeProposition();
        if (!$proposition) {
            throw new \LogicException('Publishing an article without Exchange Proposition is not possible.');
        }

        $response = $this->sendArticle($article, $force);

        if ($response === false) {
            $proposition->setPublicationResponseCode(ExchangeSite::RESPONSE_CODE_IMPOSSIBLE);

            return ExchangeSite::RESPONSE_CODE_IMPOSSIBLE;
        } else {
            if (!isset($response['code']) || !in_array($response['code'], ExchangeSite::getAvailableResponseCode())) {
                $proposition->setPublicationResponseCode(ExchangeSite::RESPONSE_CODE_UNKNOWN_ERROR);
                $this->monolog->error('Publish article: invalid response', ['response' => $response]);

                return ExchangeSite::RESPONSE_CODE_UNKNOWN_ERROR;
            }

            if (isset($response['article_url'])) {
                $proposition->setPagePublish($response['article_url']);
            }

            $proposition->setPublicationResponseCode($response['code']);

            $this->em->flush();

            return $response['code'];
        }
    }

    /**
     * @param $response - result self::publish()
     *
     * @return bool
     */
    public static function isPublished($response)
    {
        return $response !== false
            && isset($response[CopywritingArticleProcessor::RESPONSE_KEY_PUBLISH_STATUS])
            && $response[CopywritingArticleProcessor::RESPONSE_KEY_PUBLISH_STATUS] === CopywritingArticleProcessor::RESPONSE_PUBLISH_STATUS_PUBLISH;
    }

    /**
     * @param $response - result self::publish()
     * @return bool
     */
    public static function isPending($response)
    {
        return $response !== false
            && isset($response[CopywritingArticleProcessor::RESPONSE_KEY_PUBLISH_STATUS])
            && $response[CopywritingArticleProcessor::RESPONSE_KEY_PUBLISH_STATUS] === CopywritingArticleProcessor::RESPONSE_PUBLISH_STATUS_PENDING;
    }

    /**
     * @param ExchangeSite $exchangeSite
     * @return array
     */
    public function testPluginConnection($exchangeSite)
    {
        $data['test_connection'] = true;

        return $this->send($data, $exchangeSite->getPluginUrl(), $exchangeSite->getApiKey());
    }

    /**
     * @param CopywritingArticle $article
     *
     */
    public function buildReport($article)
    {
        $this->countWords($article);
        $this->countTags($article);
        $this->countKeywords($article);
    }

    /**
     * @param CopywritingArticle $article
     */
    public function countTags(CopywritingArticle $article)
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($article->getText());

        $order = $article->getOrder();

        foreach (['h1','h2','h3','img'] as $tag) {
            if ($order->isTagRequired($tag)) {
                $tagOccurrences = $dom->getElementsByTagName($tag);

                $article->setTagCount($tag, $tagOccurrences->length);

                if ($order->isKeywordInTagRequired($tag)) {
                    list(,,$keywords) = $this->getMissedKeywords($tagOccurrences, $order->getKeywords(), true);
                    $article->setKeywordsInTag($tag, $keywords);
                }
            }
        }

        if ($order->isMetaTitle()) {
            $keywords = [];

            if ($article->getMetaTitle() !== null) {
                $titleTagOccurrences = [$article->getMetaTitle()];

                list(,,$keywords) = $this->getMissedKeywords($titleTagOccurrences, $order->getKeywords(), true);
            }

            $article->setMetaTitleKeywords($keywords);
        }
    }

    /**
     * @param CopywritingArticle $article
     */
    public function countKeywords(CopywritingArticle $article)
    {
        list($keywordsCount, $missedKeywords,) = $this->getMissedKeywords($this->getWords($article->getText()), $article->getOrder()->getKeywords());
        $article->setKeywordsNumber($keywordsCount);
        $article->setMissedKeywords($missedKeywords);
    }

    /**
     * @param CopywritingArticle $article
     *
     * @return ArticleEarning
     */
    public function calculateWriterEarn(CopywritingArticle $article)
    {
        $this->countWords($article);
        $order = $article->getOrder();
        $copywriter = $order->getCopywriter();

        $bonus = 0;

        if ($copywriter->getCopyWriterRate() !== null) {
            $rate = $this->writerBonusCalculator->calculate($article, $copywriter->getCopyWriterRate(), $bonus);
        } else {
            $defaultRate = $this->em->getRepository(Settings::class)->getSettingValue(Settings::WRITER_PRICE_PER_100_WORDS);
            $rate = $this->writerBonusCalculator->calculate($article, $defaultRate, $bonus);
        }

        $expressEarning = 0;
        if ($order->isExpress() && $order->getDeadline() > new \DateTime()) {
            $expressEarning = $this->calculatorPriceService->getExpressPrice($order->getWordsNumber(), CalculatorPriceService::WRITER_KEY);
        }

        $chooseWriterEarning = 0;
        $waitingOrder = $order->getWaitingOrder();
        if ($waitingOrder !== null && !$waitingOrder->hasAllRejected()) {
            $chooseWriterEarning = $this->calculatorPriceService->getChooseWriterPrice(
                $order->getWordsNumber(),
                $order->getProject()->getWriterCategory(),
                CalculatorPriceService::WRITER_KEY
            );
        }

        $baseEarning = $this->calculatorPriceService->getBasePrice($order->getWordsNumber(), CalculatorPriceService::WRITER_KEY, $rate);
        $imagesEarning = $this->calculatorPriceService->getImagesPrice(count($article->getImagesByWriter()), CalculatorPriceService::WRITER_KEY);
        $metaDescriptionEarning = $this->calculatorPriceService->getMetaDescriptionPrice($order->isMetaDescription(), CalculatorPriceService::WRITER_KEY);

        $bonus = round($baseEarning - $this->calculatorPriceService->getBasePrice($order->getWordsNumber(), CalculatorPriceService::WRITER_KEY, $rate - $bonus), 2);

        return new ArticleEarning($baseEarning, $imagesEarning, $expressEarning, $chooseWriterEarning, $metaDescriptionEarning, $bonus < 0 ? abs($bonus) : 0, $bonus > 0 ? $bonus : 0);
    }

    /**
     * @param CopywritingArticle $article
     *
     * @return ArticleEarning
     * @throws \Exception
     */
    public function countCorrectorEarn(CopywritingArticle $article)
    {
        $malusRate = $this->adminBonusCalculator->calculate($article);
        $order = $article->getOrder();
        $countWords = $order->getWordsNumber();

        $malus = round($malusRate * ($countWords / 100), 2);

        $diff = $order->getReadyForReviewAt()->diff(new \DateTime());
        if ($diff->days >= 2 && $diff->invert === 0) {
            $baseCost = $this->calculatorPriceService->getBasePrice($countWords, CalculatorPriceService::REDUCED_CORRECTOR_KEY);
        } else {
            $baseCost = $this->calculatorPriceService->getBasePrice($countWords, CalculatorPriceService::CORRECTOR_KEY);
        }

        $expressCost = 0;
        if ($article->getOrder()->isExpress() && $order->getDeadline() > new \DateTime()) {
            $expressCost = $this->calculatorPriceService->getExpressPrice($countWords, CalculatorPriceService::CORRECTOR_KEY);
        }

        $imagesCost = 0;
        $imagesByAdmin = count($article->getImagesByAdmin());
        if ($imagesByAdmin > 0) {
            $imagesCost = $this->calculatorPriceService->getImagesPrice($imagesByAdmin, CalculatorPriceService::WRITER_KEY);
        }

        return new ArticleEarning($baseCost, $imagesCost, $expressCost, 0, 0, $malus);
    }

    /**
     * @param $text
     * @return array|false|string[]
     */
    public function getWords($text)
    {
        $textStripped = $this->stripText($text);
        $textWords = $textStripped ? preg_split('/\s+/', $textStripped) : [];

        return $textWords;
    }

    /**
     * @param CopywritingArticle $article
     */
    public function countWords(CopywritingArticle $article)
    {
        $article->setWordsNumber(count($this->getWords($article->getText())));
    }

    /**
     * @param $words
     * @param $keywords
     * @param bool $tag
     * @return array
     */
    public function getMissedKeywords($words, $keywords, $tag = false)
    {
        $keywordsCount = 0;
        $keywordsOccured = [];

        if ($tag) {
            foreach ($words as $tag) {
                $tagWords = $this->getWords(is_string($tag) ? $tag : $tag->textContent);
                list($keywordCount,,$occured) = $this->getMissedKeywords($tagWords, $keywords);
                $keywordsCount += $keywordCount;
                $keywordsOccured = array_merge($keywordsOccured, $occured);
            }

            return [$keywordsCount, null, $keywordsOccured];
        }

        $wordsCounts = array_count_values($words);

        $missedKeywords = [];
        $keywordsOccured = [];

        foreach ($keywords as $keyword) {
            $keyword = $keyword->getWord();
            if (!isset($wordsCounts[$keyword])) {
                $missedKeywords[] = $keyword;
            } else {
                $keywordsCount += $wordsCounts[$keyword];
                $keywordsOccured[] = $keyword;
            }
        }

        return [$keywordsCount, $missedKeywords, $keywordsOccured];
    }

    /**
     * @param $text
     * @return mixed
     */
    private function stripText($text)
    {
        return trim(str_replace(['&nbsp;'], ' ', strip_tags($text)));
    }

    /**
     * @param string $text
     *
     * @return string
     */
    private function parsePostTitle($text)
    {
        preg_match(self::H1_PATTERN, $text, $out);

        return !empty($out[1]) ? strip_tags($out[1]): '';
    }

    /**
     * @param $text
     * @return null|string
     */
    private function removeFirstH1FromText($text)
    {
        return preg_replace(self::H1_PATTERN, "", $text, 1);
    }

    /**
     * @param $data
     * @param $url
     * @param $token
     * @return mixed
     */
    private function send($data, $url, $token)
    {
        $dataJson = json_encode($data, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP);

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ));
        curl_setopt($curl, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $dataJson);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_VERBOSE, 1);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            return ['status' => false, 'curlError' => curl_error($curl)];
        } else {
            if ($httpCode !== 200) {
                return ['status' => false, 'curlInfo' => curl_getinfo($curl)];
            }
        }
        curl_close($curl);

        $newResponse = preg_replace('/[[:^print:]]/', '', $response); //remove non-printable characters
        $decodedResponse = json_decode($newResponse, true);
        if ($decodedResponse === null) {
            return ['status' => false, 'curlResult' => $response, 'code' => ExchangeSite::RESPONSE_CODE_INVALID_JSON];
        }

        return $decodedResponse;
    }

    /**
     * @param string $oldText
     * @param string $newText
     * @return bool
     */
    public function compareLinks($oldText, $newText)
    {
        $dom = new \DOMDocument;
        $dom->loadHTML(mb_convert_encoding($oldText, 'HTML-ENTITIES', 'UTF-8'));
        $oldLinks = $dom->getElementsByTagName('a');

        $dom = new \DOMDocument;
        $dom->loadHTML($newText);
        $newLinks = $dom->getElementsByTagName('a');

        if ($oldLinks->length !== $newLinks->length) {
            return false;
        }

        foreach ($oldLinks as $key => $oldLink) {
            if ($newLinks->item($key)->getAttribute('href') !== $oldLink->getAttribute('href')
                ||
                $newLinks->item($key)->textContent !== $oldLink->textContent
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $text
     * @return array
     */
    public function getImagesFromText($text)
    {
        $dom = new \DOMDocument;

        try {
            $dom->loadHTML($text);
        } catch (\Exception $e) {
            return [];
        }

        $images = $dom->getElementsByTagName('img');

        $resultSrcs = [];
        foreach ($images as $key => $image) {
            $resultSrcs[] = $image->getAttribute('src');
        }

        return $resultSrcs;
    }

    /**
     * @param CopywritingArticle $article
     */
    public function prepareArticle($article)
    {
        $text = '<div>' . $article->getText() . '</div>';

        $text = self::replaceSpecialChars($text);

        $dom = new \DOMDocument;

        try {
            $dom->loadHTML(mb_convert_encoding($text , 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } catch (\Exception $e) {
            return;
        }

        $imagesLocalPath = $this->articleImagesLocalPath . DIRECTORY_SEPARATOR;

        /** @var \DOMElement $img */
        foreach ($dom->getElementsByTagName('img') as $img) {
            try {
                if (mb_strpos($img->getAttribute('src'), $imagesLocalPath) === false) {
                    $img->setAttribute('src', $this->moveImage($img->getAttribute('src')));
                }
            } catch (FileNotFoundException $exception) {
                $img->parentNode->parentNode->removeChild($img->parentNode);
            }
        }

        if ($article->getFrontImage() && mb_strpos($article->getFrontImage(), $imagesLocalPath) === false) {
            try {
                $article->setFrontImage($this->moveImage($article->getFrontImage()));
            } catch (FileNotFoundException $exception) {
                $article->setFrontImage(null);
            }
        }

        $text = mb_convert_encoding($dom->saveHTML(), 'UTF-8', 'HTML-ENTITIES');
        $text = self::replaceSpecialChars($text, false);

        $article->setText($text);
    }

    /**
     * @param $text
     * @param bool $encode -  true - encode; false - decode
     *
     * @return string
     */
    public static function replaceSpecialChars($text, $encode = true)
    {
        $array = [
            '&gt;' => '_---@gt---_',
            '&lt;' => '_---@lt---_',
        ];

        if ($encode) {
            $text = strtr($text, $array);
        } else {
            $text = strtr($text, array_flip($array));
        }

        return $text;
    }

    /**
     * @param $imageSrc
     * @return string
     */
    private function moveImage($imageSrc)
    {
        if (mb_strpos($imageSrc, $this->articleImagesLocalPath.'_tmp') === false) {
            $ch = curl_init($imageSrc);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $image = curl_exec($ch);
            $imageType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            list($type, $extension) = explode('/', $imageType);
            if ($type !== "image") {
                return "";
            }

            $fileName = md5(uniqid()) . "." . $extension;

            $fullFilePath = $this->uploadArticleImagesDir . DIRECTORY_SEPARATOR . $fileName;

            $this->monolog->info('Download file: ' . $imageSrc . ' --> ' .  $fullFilePath);

            $this->fileSystem->dumpFile($fullFilePath, $image);

        } else {
            $fileName = pathinfo($imageSrc, PATHINFO_BASENAME);

            $oldPath = $this->uploadArticleImagesDir . '_tmp' . DIRECTORY_SEPARATOR . $fileName;
            $newPath = $this->uploadArticleImagesDir . DIRECTORY_SEPARATOR . $fileName;

            $this->monolog->info('Move file: ' . $oldPath . ' --> ' .  $newPath);

            $file = new File($oldPath);
            $file->move($this->uploadArticleImagesDir);
        }

        return $this->siteUrl . $this->articleImagesLocalPath . '/' . $fileName;
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function prepareArticleText($text)
    {
        $removedEmptyTags = ['h1', 'h2', 'h3', 'strong', 'em', 'span', 'ul', 'li'];

        do {
            $repeat = false;
            foreach ($removedEmptyTags as $tag) {
                $text = preg_replace('~<'.$tag.'[^>]*?>(\s|&nbsp;)*?</'.$tag.'>~uim', '', $text, -1, $count);
                if ($count > 0) {
                    $repeat = true;
                }
            }
        } while ($repeat);

        return $text;
    }
}
