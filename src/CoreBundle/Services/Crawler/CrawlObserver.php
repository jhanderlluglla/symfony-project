<?php

namespace CoreBundle\Services\Crawler;

use CoreBundle\Entity\DirectoryBacklinks;
use CoreBundle\Entity\User;
use CoreBundle\Services\Mailer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Psr\Http\Message\ResponseInterface;
use Spatie\Crawler\CrawlObserver as CrawlObserverInterface;
use Spatie\Crawler\Url;

class CrawlObserver implements CrawlObserverInterface
{

    /** @var EntityManager */
    private $entityManager;

    /**
     * @var array
     */
    protected $backLinks;

    /**
     * @var array
     */
    protected $desiredUrls;

    /**
     * @var array
     */
    protected $crawledUrls = [];

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * CrawlObserver constructor.
     * @param array $backLinks
     * @param EntityManager $entityManager
     * @param Mailer $mailer
     */
    public function __construct($backLinks, $entityManager, $mailer)
    {
        $this->backLinks = $backLinks;
        $this->desiredUrls = array_keys($backLinks);
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    /**
     * Called when the crawler will crawl the url.
     *
     * @param Url $url
     *
     * @return void
     */
    public function willCrawl(Url $url)
    {
    }

    /**
     * Called when the crawler has crawled the given url.
     *
     * @param Url $url
     * @param ResponseInterface|null $response
     * @param Url $foundOnUrl
     *
     * @return void
     */
    public function hasBeenCrawled(Url $url, $response, Url $foundOnUrl = null)
    {
        $trimmedUrl = trim(strval($url), '/');
        if (in_array($trimmedUrl, $this->desiredUrls)) {
            $this->crawledUrls[$trimmedUrl] = strval($foundOnUrl);
        }
    }

    /**
     * Called when the crawl has ended.
     *
     * @return void
     * @throws OptimisticLockException
     */
    public function finishedCrawling()
    {
        $today = new \DateTime();

        /** @var DirectoryBacklinks $backLink */
        foreach ($this->backLinks as $desiredUrl => $backLink) {
            $backLink->setDateChecked($today);
            if ($backLink->getDateCheckedFirst() === null) {
                $backLink->setDateCheckedFirst($today);
            }

            if (array_key_exists($desiredUrl, $this->crawledUrls)) {
                $backLink->setBacklink($this->crawledUrls[$desiredUrl]);
                $backLink->setDateFound($today);
                $backLink->setStatus(DirectoryBacklinks::STATUS_FOUND);

                $parsedUrl = parse_url($backLink->getBacklink());
                $replace = [
                    '%url%' => $parsedUrl['scheme'] . "://" . $parsedUrl['host'],
                    '%backlink%' => $backLink->getBacklink(),
                ];

                $user = $backLink->getJob()->getNetlinkingProject()->getUser();
                $this->mailer->sendToUser(User::NOTIFICATION_BACKLINK_FOUND, $user, $replace);

            } else {
                if ($backLink->isExpired()) {
                    $backLink->setStatus(DirectoryBacklinks::STATUS_NOT_FOUND);
                }
            }
            $this->entityManager->persist($backLink);
        }

        $this->entityManager->flush();
    }
}
