<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Settings;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Collections\Criteria;

use CoreBundle\Entity\User;

class ExchangePropositionRepository extends BaseRepository implements FilterableRepositoryInterface
{
    protected $filters = [
        'id',
        'exchangeSite',
        ['name' => 'user', 'filter' => 'buyer'],
        ['name' => 'url', 'alias' => 'es']
    ];
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false, $orderBy = [])
    {
        $qb = $this->createQueryBuilder('ep');

        $this->prepare($filters, $qb);

        $qb->innerJoin('ep.exchangeSite', 'es');

        if (!empty($filters['user'])) {
            $qb->andWhere(
                'es.user = :user'
            );

            $qb->setParameter('user', $filters['user'], Type::OBJECT);
        }

        if (!empty($filters['status']) && is_array($filters['status'])) {
            $qb->andWhere(
                $qb->expr()->in('ep.status', $filters['status'])
            );
        }

        if (!empty($filters['type'])) {
            $qb->andWhere(
                $qb->expr()->eq('ep.type', $qb->expr()->literal($filters['type']))
            );
        }

        if (!empty($filters['modification_status']) && is_array($filters['modification_status'])) {
            $qb->andWhere(
                $qb->expr()->in('ep.modificationStatus', $filters['modification_status'])
            );
        }

        if (!empty($filters['expired'])) {
            $date = new \DateTime();
            $date->modify("-{$filters['expired']} day");

            $qb->andWhere('ep.createdAt >= :date');
            $qb->setParameter('date', $date);
        }

        if (!empty($filters['expiredLt'])) {
            $date = new \DateTime();
            $date->modify("-{$filters['expiredLt']} day");

            $qb->andWhere('ep.createdAt <= :date');
            $qb->setParameter('date', $date);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy("ep.$field", $direction);
            }
        } else {
            $qb
                ->orderBy('ep.status', Criteria::DESC)
                ->addOrderBy('ep.createdAt', Criteria::ASC)
            ;
        }

        return $qb;
    }

    /**
     * @param User     $user
     * @param null|int $viewed
     * @param null|int $id
     *
     * @return QueryBuilder
     */
    public function getWebmasterProposition($user, $viewed = null, $id = null)
    {
        $qb = $this->createQueryBuilder('ep');

        $qb
            ->join('ep.exchangeSite', 'es')
            ->andWhere(
                $qb->expr()->neq('ep.type', $qb->expr()->literal(ExchangeProposition::OWN_TYPE))
            )
        ;

        if ($user->hasRole(User::ROLE_WEBMASTER)) {
            $qb
                ->andWhere('ep.user = :user')
                ->setParameter('user', $user, Type::OBJECT)
                ->andWhere('ep.job is NULL')
            ;
        }

        if ($viewed) {
            $qb->andWhere(
                $qb->expr()->eq('ep.viewed', $viewed)
            );
        }

        if ($id) {
            $qb->andWhere(
                $qb->expr()->eq('ep.id', ':id')
            );
            $qb->setParameter('id', $id);
        }

        $qb
            ->orderBy('ep.createdAt', Criteria::DESC)
        ;

        return $qb;
    }

    /**
     * @param array $filter
     *
     * @return QueryBuilder
     */
    public function getExpiredPropositions($filter = [])
    {
        $tpsReactWebmaster = $this->getEntityManager()->getRepository(Settings::class)->getSettingValue('tps_reac_webmaster');

        $filter = $filter + [
            'status' => [ExchangeProposition::STATUS_AWAITING_WEBMASTER, ExchangeProposition::STATUS_ACCEPTED],
            'expiredLt' => $tpsReactWebmaster
        ];

        return $this->filter($filter);
    }

    public function getPropositionsForRemind()
    {
        $qb = $this->createQueryBuilder('ep');
        $qb->andWhere('(ep.status = :status_awaiting_webmaster AND ep.articleAuthorType != :author_writer) OR (ep.status = :status_accepted AND ep.articleAuthorType = :author_writer)');
        $qb->setParameter('status_awaiting_webmaster', ExchangeProposition::STATUS_AWAITING_WEBMASTER);
        $qb->setParameter('status_accepted', ExchangeProposition::STATUS_ACCEPTED);
        $qb->setParameter('author_writer', ExchangeProposition::ARTICLE_AUTHOR_WRITER);

        return $qb;
    }
}
