<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingArticleRating;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;
use CoreBundle\Entity\WaitingOrder;
use CoreBundle\Repository\Traits\FindByTransaction;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * Class CopywritingOrderRepository
 * @package CoreBundle\Repository
 */
class CopywritingOrderRepository extends BaseRepository implements FilterableRepositoryInterface
{
    use FindByTransaction;

    /** @var array  */
    protected $filters = [
        'status',
        'copywriter',
        'exchangeProposition',
        'customer',
        ['name' => 'language', 'alias' => 'p']
    ];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false, $orderBy = [])
    {
        $qb = $this->createQueryBuilder('ca');
        $this->prepare($filters, $qb);

        $qb->addSelect('p');
        $qb->addSelect('c');

        if(empty($orderBy)){
            $qb->orderBy('ca.createdAt', Criteria::DESC);
        }else{
            foreach ($orderBy as $field => $criteria){
                $qb->addOrderBy("ca.$field", $criteria);
            }
        }

        $qb->leftJoin('ca.project', 'p');
        $qb->leftJoin('ca.customer', 'c');


        if (array_key_exists('consulted', $filters)) {
            $qb->leftJoin(CopywritingArticle::class, 'caa', Join::WITH, 'caa.order = ca.id');
            $qb->andWhere('caa.consulted = '.($filters['consulted'] ? 1 : 0));
        }

        if(isset($filters['exclude_waiting']) && $filters['exclude_waiting']){
            $qb
                ->leftJoin('ca.waitingOrder', 'wo')
                ->andWhere('wo.status = :waitingStatus OR wo.status IS NULL ')
                ->setParameter('waitingStatus', WaitingOrder::STATUS_REJECTED)
            ;
        }
        if(isset($filters['exclude_status'])){
            if(is_array($filters['exclude_status'])){
                $qb
                    ->andWhere('ca.status NOT IN(:statuses)')
                    ->setParameter('statuses', $filters['exclude_status'])
                ;
            }else{
                $qb
                    ->andWhere('ca.status != :status' )
                    ->setParameter('status', $filters['exclude_status'])
                ;
            }
        }
        if(isset($filters['rating']) && !is_null($filters['rating'])) {
            $qb->innerJoin('ca.rating','r')
                ->andWhere('r.value = :rating')
                ->setParameter('rating', $filters['rating']);
        }

        if(isset($filters['keyword']) && $filters['keyword']){
            $qb->innerJoin(CopywritingArticle::class,'a', 'WITH', 'IDENTITY(a.order) = ca.id');
            $allOptionsEmpty = !$filters['keyword_title'] && !$filters['keyword_description'] && !$filters['keyword_content'];
            $orX = $qb->expr()->orX();
            if($filters['keyword_title'] || $allOptionsEmpty){
                $orX->add('ca.title LIKE :keyword');
            }
            if($filters['keyword_description'] || $allOptionsEmpty){
                $orX->add('p.description LIKE :keyword');
            }
            if($filters['keyword_content'] || $allOptionsEmpty){
                $orX->add('a.text LIKE :keyword');
            }
            $qb->setParameter('keyword', '%' . addcslashes($filters['keyword'], '%_') . '%');
            $qb->andWhere($orX);
        }

        $qb->leftJoin('ca.exchangeProposition', 'ep');
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull('ep.status'),
                $qb->expr()->neq('ep.status', ':status_impossible')
            )
        );
        $qb->setParameter('status_impossible', ExchangeProposition::STATUS_IMPOSSIBLE);

        return $qb;
    }

    /**
     * @param CopywritingOrder $order
     * @return \DateTime
     */
    public function countStartDate(CopywritingOrder $order)
    {
        $wordsPerDay = $this->getEntityManager()->getRepository(Settings::class)->getSettingValue(Settings::WORDS_PER_DAY);

        $wordsWaiting = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'waiting')
            ->select('SUM(a.wordsNumber) as wordsNumber')
            ->getQuery()
            ->getSingleScalarResult();

        $daysWaiting = ceil($wordsWaiting / $wordsPerDay);
        $startDate = (clone $order->getCreatedAt())->add(new \DateInterval("P" . $daysWaiting . "D"));

        return $startDate;
    }

    /**
     * @param array $filters
     * @param User $user
     * @param array $orderBy
     *
     * @return QueryBuilder|mixed
     *
     * @throws \Exception
     */
    public function getUserCollectionBuilder($filters, User $user, $orderBy = [])
    {
        if ($user->isWebmaster()) {
            $qb = $this->getWebmasterCollectionData($filters, $user);
        } elseif ($user->isWriter() || $user->hasRole(User::ROLE_WRITER_COPYWRITING)) {
            $qb = $this->getWriterCollectionData($filters + ['language' => $user->getWorkLanguage()], $user);
        } elseif ($user->isAdmin() || $user->isWriterAdmin()) {
            $qb = $this->filter($filters, false, $orderBy);
        }

        $qb->andWhere(sprintf('%s.createdAt <= :now', $qb->getRootAliases()[0]))
            ->setParameter(':now', new \DateTime());

        return $qb;
    }

    /**
     * @param $user
     * @param array $filters
     * @return QueryBuilder
     */
    public function getOrderedByCustomerRating($user, $filters = [])
    {

        $qb = $this->createQueryBuilder('a')
            ->addSelect('AVG(ar.value) AS HIDDEN rating')
            ->innerJoin(CopywritingOrder::class,'sa', 'WITH', 'sa.customer = a.customer')
            ->innerJoin('a.project', 'cp')
            ->leftJoin('a.waitingOrder', 'wo')
            ->leftJoin(CopywritingArticleRating::class,'ar', 'WITH', 'IDENTITY(ar.order) = sa.id AND sa.copywriter = :copywriter')
            ->where('a.status = :status')
            ->andWhere('wo.status = :waitingStatus OR wo.status IS NULL ')
            ->groupBy('a.id','sa.customer')
            ->orderBy('a.createdAt','ASC')
            ->setParameter('status', 'waiting')
            ->setParameter('copywriter', $user)
            ->setParameter('waitingStatus', WaitingOrder::STATUS_REJECTED)
        ;

        if(isset($filters['express'])){
            if($filters['express'] == true){
                $qb->andWhere('a.express = true');
            }else{
                $qb->andWhere('a.express = false');
            }
        }

        if (isset($filters['language'])) {
            $qb
                ->andWhere('cp.language = :language')
                ->setParameter('language', $filters['language'])
            ;
        }

        return $qb;
    }

    /**
     * @param $user
     * @param null $status
     * @param array $ordersBy
     * @return QueryBuilder
     */
    public function findByCustomer($user, $status = null, $ordersBy = [])
    {
        $qb = $this->createQueryBuilder('co');

        $qb
            ->where('co.customer = :user')
            ->setParameter('user', $user)
            ->leftJoin('co.exchangeProposition', 'ep')
            ->andWhere('ep.job is NULL')
        ;

        if($status) {
            if(is_array($status)) {
                $qb->andWhere("co.status IN (:status)");
            } else {
                $qb->andWhere("co.status = :status");
            }

            $qb->setParameter('status', $status);
        }

        foreach ($ordersBy as $field => $criteria){
            $qb->addOrderBy("co.$field", $criteria);
        }

        return $qb;
    }

    /**
     * @param User $user
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getExpressOrdersCount(User $user)
    {
        if($user->isWriter() || $user->isAdmin()) {

            $qb = $this->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->where('o.express = true')
                ->andWhere('o.deadline >= NOW()')
                ->andWhere('o.status = :status');

            if($user->isWriter()) {

                $qb->setParameter('status', CopywritingOrder::STATUS_WAITING);
            } elseif ($user->isAdmin()) {

                $qb->setParameter('status', CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN);
            }

            return $qb->getQuery()->getSingleScalarResult();
        }

    }

    /**
     * @param $year
     * @return array
     */
    public function getEarningsForMonthsAndWriters($year)
    {
        $sql = "SELECT 
                    u.full_name AS full_name,
                    u.created_at AS registered_at,
                    SUM(ca.writer_earn) AS earning,
                    COUNT(ca.id) AS order_count,
                    MONTH(co.approved_at) AS month
                FROM
                    copywriting_article AS ca
                        INNER JOIN
                    copywriting_order AS co ON (ca.order_id = co.id)
                        INNER JOIN
                    fos_user AS u ON (co.copywriter_id = u.id)
                WHERE YEAR(co.approved_at) = :year
                    AND co.status = :status
                GROUP BY co.copywriter_id, month";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute(['year' => $year, 'status' => CopywritingOrder::STATUS_COMPLETED]);

        return $stmt->fetchAll();
    }

    /**
     * @param array $filters
     * @param User $user
     * @return mixed
     */
    private function getWebmasterCollectionData($filters = [], User $user)
    {
        $status = isset($filters['status']) ? $filters['status'] : 'waiting';

        $orderBy = [];
        if($status === CopywritingOrder::STATUS_COMPLETED){
            $orderBy = ['approvedAt' => Criteria::DESC];
        }

        return $this->findByCustomer($user, $status, $orderBy);
    }

    /**
     * @param array $filters
     * @param User $user
     * @param array $orderBy
     * @return mixed
     */
    private function getWriterCollectionData($filters = [], User $user, $orderBy = ["takenAt" => "asc"])
    {
        if (isset($filters['status']) && $filters['status'] == 'waiting') {

            return $this->getOrderedByCustomerRating($user, $filters);
        } else {

            return $this->filter(array_merge($filters,['copywriter' => $user]), false, $orderBy);
        }
    }

    /**
     * @param User $user
     * @return array
     */
    public function getCountProjects($user)
    {
        $qb = $this->createQueryBuilder('o');
        $workLanguage = $user->getWorkLanguage();

        $qb
            ->select('COUNT(o) as allCount')
            ->leftJoin('o.waitingOrder', 'wo')
        ;

        if ($user->isAdmin() || $user->isWriterAdmin()) {
            $qb
                ->addSelect('SUM(CASE WHEN o.status = \'' . CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN . '\' and o.express = 0 then 1 else 0 end) toAdminCount')
                ->addSelect('SUM(CASE WHEN o.status = \'' . CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN . '\' and o.express = 1 then 1 else 0 end) toAdminExpressCount')
            ;
        }

        if ($user->hasRole(User::ROLE_WRITER) || $user->hasRole(User::ROLE_WRITER_COPYWRITING)) {
            $qb
                ->addSelect('SUM(CASE WHEN o.status = \'' . CopywritingOrder::STATUS_WAITING . '\' and o.express = 1 then 1 else 0 end) waitingExpressCount')
                ->addSelect('SUM(CASE WHEN wo.status = \'' . WaitingOrder::STATUS_WAITING . '\' and c.user = :user then 1 else 0 end) waitingOrdersCount')
                ->addSelect('SUM(CASE WHEN o.status = \'' . CopywritingOrder::STATUS_WAITING . '\' and o.express = 0 then 1 else 0 end) pendingForWriter')
                ->setParameter('user', $user, TYPE::OBJECT)
                ->leftJoin('wo.candidates', 'c')
                ->addSelect('SUM(CASE WHEN o.status IN (\'' . CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN . '\') and o.copywriter = :user and o.express = 0 then 1 else 0 end) underReviewCount')
                ->addSelect('SUM(CASE WHEN o.status IN (\'' . CopywritingOrder::STATUS_SUBMITTED_TO_ADMIN . '\') and o.copywriter = :user and o.express = 1 then 1 else 0 end) underReviewExpressCount')
                ->addSelect('SUM(CASE WHEN o.status IN (\'' . CopywritingOrder::STATUS_PROGRESS . '\',\'' . CopywritingOrder::STATUS_DECLINED . '\') and o.copywriter = :user and o.express = 0 then 1 else 0 end) progressCount')
                ->addSelect('SUM(CASE WHEN o.status IN (\'' . CopywritingOrder::STATUS_PROGRESS . '\',\'' . CopywritingOrder::STATUS_DECLINED . '\') and o.copywriter = :user and o.express = 1 then 1 else 0 end) progressExpressCount')
                ->leftJoin('o.exchangeProposition', 'ep')
                ->leftJoin('o.project', 'p')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('ep.status'),
                        $qb->expr()->neq('ep.status', ':ep_status_impossible')
                    )
                )
                ->andWhere('p.language = :workLanguage')
            ;
            $qb->setParameter('ep_status_impossible', ExchangeProposition::STATUS_IMPOSSIBLE);
            $qb->setParameter('workLanguage', $workLanguage);
        } else {
            $qb
                ->addSelect('SUM(CASE WHEN o.status = \'' . CopywritingOrder::STATUS_WAITING . '\' and o.express = 0 and (wo.status = :waitingStatus or wo.status IS NULL) then 1 else 0 end) waitingCount')
                ->addSelect('SUM(CASE WHEN o.status = \'' . CopywritingOrder::STATUS_WAITING . '\' and o.express = 1 then 1 else 0 end) pendingExpressCount')
                ->addSelect('SUM(CASE WHEN o.status IN (\'' . CopywritingOrder::STATUS_PROGRESS . '\',\'' . CopywritingOrder::STATUS_DECLINED . '\') and o.express = 1 then 1 else 0 end) progressExpressCount')
                ->addSelect('SUM(CASE WHEN o.status IN (\'' . CopywritingOrder::STATUS_PROGRESS . '\',\'' . CopywritingOrder::STATUS_DECLINED . '\') and o.express = 0 then 1 else 0 end) progressCount')
                ->setParameter('waitingStatus', WaitingOrder::STATUS_REJECTED)
            ;
            if ($user->hasRole(User::ROLE_WEBMASTER)) {
                $qb
                    ->addSelect('SUM(CASE WHEN o.status = \'' . CopywritingOrder::STATUS_COMPLETED . '\' and a.consulted = 0 then 1 else 0 end) completedCount')
                    ->leftJoin('o.article', 'a')
                    ->leftJoin('o.exchangeProposition', 'ep')
                    ->andWhere('ep.job is NULL')
                    ->andWhere('o.customer = :user')
                    ->setParameter('user', $user, TYPE::OBJECT)
                ;
            } else {
                $qb
                    ->addSelect('SUM(CASE WHEN o.status = \'' . CopywritingOrder::STATUS_COMPLETED . '\' then 1 else 0 end) completedCount')
                ;
            }
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @param array $writersIds
     * @return array
     */
    public function getWordsOfOrders($writersIds)
    {
        $qb = $this->createQueryBuilder('co');

        if(count($writersIds) === 0){
            return [];
        }
        $qb
            ->select('SUM(co.wordsNumber) as wordsSum, IDENTITY(co.copywriter) as writerId, IDENTITY(c.user) as userId')
            ->leftJoin('co.waitingOrder', 'wo')
            ->leftJoin('wo.candidates', 'c')
            ->where($qb->expr()->in('co.status', [CopywritingOrder::STATUS_PROGRESS, CopywritingOrder::STATUS_DECLINED]))
            ->orWhere('wo.status = :waitingOrderStatus')
            ->andWhere($qb->expr()->in('co.copywriter', $writersIds))
            ->orWhere($qb->expr()->in('c.user', $writersIds))
            ->groupBy('co.copywriter')
            ->addGroupBy('c.user')
            ->setParameter('waitingOrderStatus', WaitingOrder::STATUS_WAITING)
        ;

        $results = $qb->getQuery()->getResult();
        $wordsInProgress = [];
        $wordsInWaiting = [];

        foreach ($results as $result){
            if(isset($result['writerId']) && $result['userId'] === null){
                $wordsInProgress[$result['writerId']] = $result['wordsSum'];
            }elseif(isset($result['userId']) && $result['writerId'] === null){
                $wordsInWaiting[$result['userId']] = $result['wordsSum'];
            }
        }
        foreach ($wordsInWaiting as $key => $waitingWords){
            if(key_exists($key, $wordsInProgress)){
                $wordsInProgress[$key] += $waitingWords;
            }else{
                $wordsInProgress[$key] = $waitingWords;
            }
        }

        return $wordsInProgress;
    }

    /**
     * @param $user
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException]
     */
    public function getProgressOrdersCount($user)
    {
        $qb = $this->createQueryBuilder('co');

        $qb->select($qb->expr()->count('co.id'));
        $qb->leftJoin('co.project', 'p');

        $this->prepare([
            'language' => $user->getWorkLanguage(),
            'copywriter' => $user,
            'status' => CopywritingOrder::STATUS_PROGRESS
        ], $qb);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $ids
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException]
     */
    public function getOrdersCountByIds($ids)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('IDENTITY(o.copywriter) as writer_id ');
        $qb
            ->addSelect('SUM(CASE WHEN o.status = \'' . CopywritingOrder::STATUS_COMPLETED . '\' then 1 else 0 end) completedCount')
            ->addSelect('SUM(CASE WHEN o.status IN (\'' . CopywritingOrder::STATUS_PROGRESS . '\',\'' .
                CopywritingOrder::STATUS_DECLINED . '\') then 1 else 0 end) progressCount')
        ;
        $qb
            ->andWhere(
                $qb->expr()->in('o.copywriter', ':affected_user')
            )
            ->setParameter('affected_user', $ids);
        ;
        $qb->groupBy('o.copywriter');

        $opywritingProjects = $qb->getQuery()->getResult();
        $userCopywritingProjects = [];
        foreach ($opywritingProjects as $project) {
            $userCopywritingProjects[$project['writer_id']]['current'] = $project['progressCount'];
            $userCopywritingProjects[$project['writer_id']]['completed'] = $project['completedCount'];
        }

        return $userCopywritingProjects;
    }
}
