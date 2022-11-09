<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\NetlinkingProject;
use CoreBundle\Entity\Site;
use CoreBundle\Helpers\SiteHelper;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\QueryBuilder;
use PDO;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * SiteRepository
 */
class SiteRepository extends BaseRepository implements FilterableRepositoryInterface
{
    protected $filters = [
        'id',
        'host',
    ];

    /**
     * @param array $filters
     * @param boolean $count
     *
     * @return QueryBuilder|array
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('s');
        $this->prepare($filters, $qb);

        return $qb;
    }

    /**
     * @param $url
     * @param $language
     *
     * @return Site
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findOrCreateByUrl($url, $language)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!$host) {
            throw new \LogicException('Error parsing host from url: '.$url);
        }
        if (!$scheme) {
            throw new \LogicException('Error parsing scheme from url: '.$url);
        }
        $host = SiteHelper::prepareHost($host);

        $site = $this->findByHost($host, $language);

        if (!$site) {
            if (!SiteHelper::validationHost($host)) {
                throw new ValidatorException('Host invalid: ' . $host);
            }

            $site = new Site();
            $site->setHost($host);
            $site->setScheme($scheme);
            $site->setLanguage($language);
            $this->getEntityManager()->persist($site);
            $this->getEntityManager()->flush();
        }


        return $site;
    }

    /**
     * @param $host
     * @param $language
     *
     * @return Site|null|object
     */
    public function findByHost($host, $language)
    {
        if (strpos($host, 'www') === 0) {
            $hostWWW = $host;
            $host = preg_replace('~^www.~', '', $host);
        } else {
            $hostWWW = 'www.'.$host;
        }
        try {
            $sql = 'SELECT * FROM `site` WHERE (BINARY `host` = :host OR BINARY `host` = :wwwHost) AND `language` = :language LIMIT 1';
            $query = $this->getEntityManager()->getConnection()->prepare($sql);
            $query->execute([
                'host' => $host,
                'wwwHost' => $hostWWW,
                'language' => $language,
            ]);
        } catch (DBALException $e) {
            return null;
        }

        $result = $query->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return $this->find($result['id']);
    }

    /**
     * @param int $limit
     *
     * @return array
     *
     * @throws DBALException
     */
    public function getUpdateFrequency($limit = 500)
    {
        $npSql = <<<SQL
SELECT 
    s.id,
    s.host,
    s.update_metrics_at,
    (CASE 
        WHEN np.id IS NOT NULL THEN 7    -- NetlinkingProject must be updated once a week.
        WHEN es.id IS NOT NULL THEN 14   -- ExchangeSite must be updated every two weeks.
        WHEN d.id IS NOT NULL THEN 14    -- Directories must be updated every two weeks.
        ELSE 0
    END) as 'frequency' 
FROM site as s 
LEFT JOIN exchange_site as es ON s.id = es.site_id
LEFT JOIN directory as d ON s.id = d.site_id
LEFT JOIN netlinking_project as np ON s.id = np.site_id AND 
(
   np.status IN (:npWaiting, :npInProgress) -- Update only active NetlinkingProject
   OR
   ( -- or finished, which were spent more than 250 euros
      np.status = :npFinished
      AND (SELECT SUM(cost_webmaster) FROM job LEFT JOIN netlinking_project as npp ON npp.id = job.netlinking_project_id WHERE npp.site_id = s.id)  >= 250 
      AND DATEDIFF(NOW(), np.finished_at) < 182
   )
)
/* WHERE DATEDIFF(NOW(), s.update_metrics_at) > 6 */
GROUP BY s.id
HAVING 
   frequency > 0 
   AND (TIMESTAMPDIFF(HOUR, s.update_metrics_at, NOW()) > frequency * 24 - 1 OR s.update_metrics_at IS NULL)
LIMIT {$limit}
SQL;

        $query = $this->getEntityManager()->getConnection()->prepare($npSql);
        $query->execute([
            'npFinished' => NetlinkingProject::STATUS_FINISHED,
            'npWaiting' => NetlinkingProject::STATUS_WAITING,
            'npInProgress' => NetlinkingProject::STATUS_IN_PROGRESS,
        ]);

        return $query->fetchAll();
    }

    /**
     * @param int $limit
     *
     * @return Site[]
     *
     * @throws DBALException
     */
    public function getSitesForMetricsUpdate($limit = 500)
    {
        $frequency = $this->getUpdateFrequency($limit);

        if (empty($frequency)) {
            return [];
        }

        $ids = array_column($frequency, 'id');

        $sites = $this->filter(['id' => $ids])->getQuery()->getResult();

        return $sites;
    }
}
