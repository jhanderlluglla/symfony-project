<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\Candidate;
use CoreBundle\Entity\WaitingOrder;
use CoreBundle\Factory\PagerfantaAdapterFactory;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class WaitingOrderRepository extends EntityRepository implements FilterableRepositoryInterface
{
    /**
     * @param array $filters
     * @param bool $count
     * @return array|QueryBuilder
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('wo');

        $qb->innerJoin('wo.candidates', 'c');
        if(isset($filters['user'])){
            $qb
                ->andWhere('c.user = :user')
                ->setParameter('user', $filters['user'], Type::OBJECT)
            ;
        }
        if(isset($filters['with_orders']) && $filters['with_orders']){
            $qb
                ->innerJoin('wo.copywritingOrder', 'co')
            ;
        }
        if(isset($filters['status'])){
            $qb
                ->andWhere('wo.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }

        if(isset($filters['not_reject'])){
            $qb
                ->andWhere('c.action != :rejectAction OR c.action IS NULL')
                ->setParameter('rejectAction', Candidate::ACTION_REJECT)
            ;
        }
        $qb->getQuery();
        return $qb;
    }

    /**
     * @param $buider
     * @param int $page
     * @param int $perPage
     * @return Pagerfanta
     */
    public function createPagerfanta($buider, $page = 1, $perPage = 20)
    {
        $pagerfanta = new Pagerfanta(PagerfantaAdapterFactory::getAdapterInstance($buider));

        $pagerfanta
            ->setMaxPerPage($perPage)
            ->setCurrentPage($page)
        ;

        return $pagerfanta;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getExpiredOrders()
    {
        $sql = "SELECT waiting_order.id as waitingOrderId, candidate.id as candidateId FROM waiting_order
                  INNER JOIN candidate ON waiting_order.id = candidate.waiting_order_id
                  INNER JOIN (
                        SELECT waiting_order.id as waitingOrderId, MAX(candidate.deadline) AS maxDeadline FROM `waiting_order`
                            INNER JOIN candidate ON waiting_order.id = candidate.waiting_order_id
                            GROUP BY waiting_order.id
                  ) maxDeadlines on waiting_order.id = maxDeadlines.waitingOrderId
                  WHERE waiting_order.status = 'waiting' AND candidate.deadline = maxDeadlines.maxDeadline AND candidate.deadline < NOW()";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}