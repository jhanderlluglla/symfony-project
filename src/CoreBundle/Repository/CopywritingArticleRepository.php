<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\CopywritingArticle;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class CopywritingArticleRepository extends EntityRepository implements FilterableRepositoryInterface
{

    private $filters = ['status'];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('ca');

        if($filters) {
            foreach($filters as $key => $filter)
            {
                if (in_array($key, $this->filters)) {
                    $qb->where("ca.$key = :status");
                    $qb->setParameter('status',$filter);
                }

            }

        }

        return $qb;
    }

    public function countStartDate(CopywritingArticle $article)
    {
        $wordsWaiting = $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', 'waiting')
            ->select('SUM(a.wordsNumber) as wordsNumber')
            ->getQuery()
            ->getSingleScalarResult();

        $daysWaiting = ceil($wordsWaiting / CopywritingArticle::WORDS_PER_DAY);
        $startDate = new \DateTime(" + $daysWaiting days");

        return $startDate;
    }

}