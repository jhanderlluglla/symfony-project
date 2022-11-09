<?php

namespace UserBundle\Services;

use CoreBundle\Entity\ScheduleTask;
use CoreBundle\Entity\User;
use CoreBundle\Services\CalculatorPriceService;
use CoreBundle\Services\TransactionService;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Translation\TranslatorInterface;

use CoreBundle\Entity\Directory;
use CoreBundle\Entity\Job;
use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\BotUrls;
use CoreBundle\Entity\Comission;
use CoreBundle\Entity\DirectoriesList;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\Settings;

use CoreBundle\Services\Mailer;
use UserBundle\Services\ExchangeSite\CalculatorPrice;

/**
 * Class BacklinksChekerService
 *
 * @package UserBundle\Services
 */
class BacklinksChekerService
{

    const MAX_ATTEMPTS = 3;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var array
     */
    private $multipleUrls = [];

    /**
     * @var int
     */
    private $curlTimeoutLimit = 0;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var CalculatorPriceService
     */
    private $copywritingCalculatorPrice;

    /**
     * @var CalculatorPrice
     */
    private $exchangeCalculatorPrice;

    /**
     * @var NetlinkingService
     */
    private $netlinkingService;

    /**
     * BacklinksChekerService constructor.
     *
     * @param EntityManager $entityManager
     * @param Mailer $mailer
     * @param TranslatorInterface $translator
     * @param TransactionService $transactionService
     * @param CalculatorPriceService $copywritingCalculatorPrice
     * @param CalculatorPrice $exchangeCalculatorPrice
     * @param NetlinkingService $netlinkingService
     */
    public function __construct($entityManager, Mailer $mailer, $translator, $transactionService, $copywritingCalculatorPrice, $exchangeCalculatorPrice, $netlinkingService)
    {
        $this->entityManager = $entityManager;
        $this->connection    = $entityManager->getConnection();

        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->transactionService = $transactionService;
        $this->copywritingCalculatorPrice = $copywritingCalculatorPrice;
        $this->exchangeCalculatorPrice = $exchangeCalculatorPrice;
        $this->netlinkingService = $netlinkingService;
    }
    
    public function check()
    {
        $this->connection->beginTransaction();
        try {

            $directories = $this->entityManager->getRepository(Directory::class)->findBy(['active' => Directory::ACTIVE_YES]);
            if (!empty($directories)) {
                /** @var Directory $directory */
                foreach ($directories as $directory) {
                    $this->multipleUrls = [];

                    $directoryUrl = !empty($directory->getNddTarget()) ? $directory->getNddTarget():$directory->getName();
                    $directoryUrl = str_replace('http://', '', $directoryUrl);
                    $directoryUrl = str_replace('www.', '', $directoryUrl);

                    $this->multipleUrls = $this->entityManager->getRepository(NetlinkingProject::class)->getBacklinks($directory);
                    if (empty($this->multipleUrls)) {
                        continue;
                    }

                    $iterator = 0;
                    $maxIteration = $directory->getPageCount();

                    $this->curlTimeoutLimit = 0;
                    if ($this->prepareCheckDomain($directoryUrl)) {
                        if ($this->checkSiteOnlineStatus($directoryUrl, 5)) {
                            while (null !== ($analyzedUrl = $this->findAnalyzedLink()) && ($iterator < $maxIteration)) {
                                $iterator++;
                                $this->checkDomain($analyzedUrl, $directoryUrl, $directory, $maxIteration);

                                if (empty($this->multipleUrls)) {
                                    break;
                                }

                                if ($iterator > $maxIteration) {
                                    break;
                                }

                                if ($this->curlTimeoutLimit > 100) {
                                    break;
                                }
                            }
                        }

                        if (!empty($this->multipleUrls)) {
                            /** @var NetlinkingProject $netlinkingProject */
                            foreach ($this->multipleUrls as $netlinkingProject) {
                                $this->setBacklinkStatus($netlinkingProject, $directory, '');
                            }
                        }
                    }
                }
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
        }
    }

    /**
     * @param string    $url
     * @param string    $directoryUrl
     * @param Directory $directory
     * @param int       $pageCount
     *
     * @return bool
     */
    private function checkDomain($url, $directoryUrl, $directory, $pageCount)
    {
        $curlInit = $this->curlInit($url, 10, $directoryUrl);

        $requestError = true;
        $attemptsCount = 0;

        while ($requestError) {
            $curlResult = curl_exec($curlInit);

            $requestError = false;
            if (curl_errno($curlInit)) {
                $attemptsCount++;
                if ($attemptsCount == self::MAX_ATTEMPTS) {// check max request attempts
                    break;
                }

                $errorNumber = curl_errno($curlInit);
                if ($errorNumber == 28) {
                    $this->curlTimeoutLimit++;
                }
                $requestError = true;
            }
        }

        $mimetype = curl_getinfo($curlInit, CURLINFO_CONTENT_TYPE); // get mitetype
        curl_close($curlInit);

        $links = $this->getLinksFromContent($curlResult, $mimetype);

        if (!empty($links)) {
            foreach ($links as $link) {
                $urlpropre = '';

                if (
                    ('http://www.' . $directoryUrl == substr($link, 0, (11 + strlen($directoryUrl)))) ||
                    ('http://' . $directoryUrl == substr($link, 0, (7 + strlen($directoryUrl))))
                ) {
                    $urlpropre = $link;
                } else {
                    if ('tel:' != substr($link, 0, 4) && 'https://' != substr($link, 0, 8) && 'http://' != substr($link, 0, 7) && 'javascript' != substr($link, 0, 10) && substr($link, 0, 1) != "'") {
                        $urlpropre = $link;
                        if (strlen($link) < 7 || 'http://' != substr($link, 0, 7)) {
                            if ($link != '') {
                                if (substr($link, 0, 1) != '/') {
                                    $urlpropre = 'http://www.' . str_replace('//', '/', $directoryUrl . '/' . $link);
                                } else {
                                    $urlpropre = 'http://www.' . str_replace('//', '/', $directoryUrl . $link);
                                }
                            } else {
                                $urlpropre = 'http://www.' . $directoryUrl;
                            }
                        }
                    }
                }

                // checking strange urls for some annuaires
                if (empty($urlpropre)) {
                    $info = parse_url($directoryUrl);
                    $path = $info['path'];

                    $path = explode('/', $path);
                    $path = $path[0];

                    if (strpos($link, $path) !== false) {
                        $urlpropre = $link;
                    }
                }

                if (!empty($urlpropre)) {
                    $botUrlsCount = $this->entityManager->getRepository(BotUrls::class)->getCount();

                    if ($botUrlsCount <= $pageCount) {
                        // fix: catching relative path
                        $path_pieces = explode('/', $directoryUrl);
                        if ((count($path_pieces) > 1)) {
                            if (substr($link, 0, 1) == '/') {
                                $host = array_shift($path_pieces);
                                $urlpropre = 'http://www.' . str_replace('//', '/', $host . $link);
                            }
                        }

                        // check subdomain - removing `www.`
                        if (!empty($urlpropre)) {
                            $exploded = explode('.', $this->getHost($urlpropre));
                            if ((count($exploded) > 2)) {
                                $urlpropre = str_replace('www.', '', $urlpropre);
                            }
                        }

                        $nb = $this->entityManager->getRepository(BotUrls::class)->getCount($urlpropre);

                        // check if url is unique
                        if ($nb < 1) {
                            if (substr_count($urlpropre, '.html') > 1) {
                                $urlpropre = str_replace('.html', '', $urlpropre);
                                $urlpropre .= '.html';
                            }

                            // check domain name
                            $urlpropre_host = $this->getHost($urlpropre);
                            $ndd_host = $this->getHost($directoryUrl);

                            if (strpos($urlpropre_host, $ndd_host) !== false) {
                                $botUrls = new BotUrls();
                                $botUrls->setAnalyzedUrl($this->urlBeginning($urlpropre));

                                $this->entityManager->persist($botUrls);
                                $this->entityManager->flush();
                            }
                        }
                    }
                }

                $linkHost = $this->prepareCheckingUrl($link);

                if (empty($linkHost)) {
                    continue;
                }

                /** @var NetlinkingProject $netlinkingProject */
                foreach ($this->multipleUrls as $netlinkingProject) {
                    $backlinkHost = $this->prepareCheckingUrl($netlinkingProject->getUrl());

                    if (strpos($linkHost, $backlinkHost) !== false) {
                        $this->find = $netlinkingProject->getUrl();
                        // remove found backlink from checking
                        $this->setBacklinkStatus($netlinkingProject, $directory, $url);

                        if (($key = array_search($netlinkingProject, $this->multipleUrls)) !== false) {
                            unset($this->multipleUrls[$key]);
                        }
                    }
                }
            }
        }

        try {
            $botUrls = $this->entityManager->getRepository(BotUrls::class)->findOneBy(['analyzedUrl' => $url]);

            if (!is_null($botUrls)) {
                $botUrls->setIsTreated(BotUrls::TREATHED_YES);
                $this->entityManager->persist($botUrls);
                $this->entityManager->flush();
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function prepareCheckingUrl($url)
    {
        $url = rtrim($url,'/');
        $clear_parts = array('http://','https://','www.');

        return str_replace($clear_parts,'',$url);
    }

    /**
     * @param NetlinkingProject $netlinkingProject
     * @param Directory         $directory
     * @param string            $backlinkUrl
     */
    private function setBacklinkStatus($netlinkingProject, $directory, $backlinkUrl = '')
    {
        /** @var DirectoryBacklinks $directoryBacklinks */
        $directoryBacklinks = $this->entityManager->getRepository(DirectoryBacklinks::class)->findByNetlinkingProject($netlinkingProject, $directory);

        $nowDate = new \DateTime();
        $status = !empty($backlinkUrl) ? DirectoryBacklinks::STATUS_FOUND:DirectoryBacklinks::STATUS_NOT_FOUND_YET;

        if (!is_null($directoryBacklinks)) {
            $interval = $directoryBacklinks->getDateCheckedFirst()->diff(new \DateTime());
            if($interval->days > 15){
                $status = DirectoryBacklinks::STATUS_NOT_FOUND;
            }

            $directoryBacklinks
                ->setDateChecked($nowDate)
                ->setBacklink($backlinkUrl)
                ->setStatus($status)
                ->setStatusType(DirectoryBacklinks::STATUS_TYPE_CRON)
            ;

            if ($status == DirectoryBacklinks::STATUS_FOUND) {
                $directoryBacklinks->setDateFound($nowDate);
            }
        } else {
            $directoryBacklinks = new DirectoryBacklinks();
            $directoryBacklinks
                ->setDateChecked($nowDate)
                ->setDateCheckedFirst($nowDate)
                ->setStatus($status)
                ->setBacklink($backlinkUrl)
                ->setStatusType(DirectoryBacklinks::STATUS_TYPE_CRON)
            ;
        }

        $this->entityManager->flush();

        if ($status == DirectoryBacklinks::STATUS_FOUND) {
            if ($directory->hasWebmasterPartner()) {
                /** @var Comission $comission */
                $comission = $this->entityManager->getRepository(Comission::class)->findOneBy(['netlinkingProject' => $netlinkingProject, 'directory' => $directory]);
                if (!is_null($comission)) {
                    $comission = new Comission();
                    $comission
                        ->setUser($directory->getWebmasterPartner())
                        ->setDirectory($directory)
                        ->setNetlinkingProject($netlinkingProject)
                        ->setAmount($directory->getTariffWebmasterPartner())
                    ;

                    $this->entityManager->persist($comission);
                    $this->entityManager->flush();
                }
            }

            $replace = [
                '%url%' => $directory->getName(),
                '%backlink%' => $backlinkUrl,
            ];

            $this->mailer->sendToUser(User::NOTIFICATION_BACKLINK_FOUND, $netlinkingProject->getUser(), $replace);

            if ($this->netlinkingService->checkCompleted($netlinkingProject)) {
                $this->netlinkingService->finishedProject($netlinkingProject);
            }
        }
    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function getLinksFromXml($content)
    {
        $links = [];

        $rss = simplexml_load_string($content);

        if ($rss) {
            foreach ($rss->channel->item as $item) {
                $links[] = (string) $item->link;
            }
        }

        return $links;
    }

    /**
     * @param string $directoryUrl
     *
     * @return bool
     */
    private function prepareCheckDomain($directoryUrl)
    {
        try {
            $cmd = $this->entityManager->getClassMetadata(BotUrls::class);
            $dbPlatform = $this->connection->getDatabasePlatform();
            $this->connection->query('SET FOREIGN_KEY_CHECKS=0');
            $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
            $this->connection->executeUpdate($q);
            $this->connection->query('SET FOREIGN_KEY_CHECKS=1');

            $botUrls = new BotUrls();
            $botUrls->setAnalyzedUrl($this->urlBeginning($directoryUrl));

            $this->entityManager->persist($botUrls);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return null|string
     */
    private function findAnalyzedLink()
    {
        $result = null;

        $botUrls = $this->entityManager->getRepository(BotUrls::class)->findOneBy(['isTreated' => BotUrls::TREATHED_NO]);
        if (!is_null($botUrls)) {
            $result = $botUrls->getAnalyzedUrl();
        }

        return $result;
    }

    /**
     * @param string $url
     *
     * @return mixed|string
     */
    private function getHost($url)
    {
        if (!empty($url)) {
            $parseUrl = parse_url(trim($url));
            $host = '';
            if (!empty($parseUrl['host'])) {
                $host = $parseUrl['host'];
            } else {
                if (!empty($parseUrl['path'])) {
                    $host = explode('/', $parseUrl['path'], 2);
                    $host = array_shift($host);
                }
            }

            return str_replace('www.', '', $host);
        }

        return '';
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function urlBeginning($url)
    {
        $exploded = explode('.', $this->getHost($url));
        if ((count($exploded) > 2)) {
            $url = str_replace('www.', '', $url);
        }

        return trim($url);
    }

    /**
     * @param string $directoryUrl
     * @param int    $timeout
     *
     * @return bool
     */
    private function checkSiteOnlineStatus($directoryUrl, $timeout)
    {
        $curlInit = $this->curlInit($directoryUrl, $timeout);
        $response = curl_exec($curlInit);

        if (curl_error($curlInit)) {
            $errorNumber = curl_errno($curlInit);
            if ($errorNumber == 28) {
                $this->curlTimeoutLimit+=10;
            }

            return false;
        }

        return true;
    }

    /**
     * @param string  $url
     * @param integer $timeout
     * @param string  $referer
     *
     * @return resource
     */
    private function curlInit($url, $timeout, $referer = null)
    {
        $curlInit = curl_init($url);

        $referer = !empty($referer) ? $url:$referer;

        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curlInit, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curlInit, CURLOPT_HEADER, false);
        curl_setopt($curlInit, CURLOPT_FAILONERROR, true);
        curl_setopt($curlInit, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlInit, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13');
        curl_setopt($curlInit, CURLOPT_REFERER, 'http://www.google.fr/search?hl=fr&source=hp&q=' . $referer . '&aq=f&aqi=g2&aql=&oq=');

        return $curlInit;
    }

    /**
     * @param string $content
     * @param string $mimetype
     *
     * @return array
     */
    private function getLinksFromContent($content, $mimetype)
    {
        $links = [];
        if (strpos($mimetype, 'rss') !== false) {
            $links = $this->getLinksFromXml($content);
        }

        if (empty($links)) {
            $dom = new \DOMDocument();

            if (strpos($mimetype, 'xml') !== false) {
                $dom->loadXML($content);
                $links = $dom->getElementsByTagName('a');
            }

            if (strpos($mimetype, 'html') !== false) {
                $tidy = new \tidy();
                $tidy->ParseString($content, array(
                    'quote-nbsp'          => false,
                    'output-xhtml'        => true,
                    'hide-comments'       => true,
                    'new-blocklevel-tags' => 'article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section source summary template track video nav figure',
                    'new-empty-tags'      => 'command embed keygen source track wbr',
                    'new-inline-tags'     => 'audio command datalist embed keygen mark menuitem meter output progress source time video wbr svg image nav figure figure',
                    'char-encoding'       => 'utf8',
                    'input-encoding'      => 'utf8',
                    'output-encoding'     => 'utf8'
                ), 'utf8');

                $tidy->cleanRepair();

                $content = (string) $tidy->html();
                libxml_use_internal_errors(true);
                $dom->loadHTML($content);

                $result = $dom->getElementsByTagName('a');

                foreach ($result as $linkData) {
                    $links[] = $linkData->getAttribute('href');
                }
            }
        }

        if (empty($links)) {
            if (( preg_match_all("/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU", $content, $matches))) {
                $links = $matches[2];
            }
        }

        return $links;
    }
}