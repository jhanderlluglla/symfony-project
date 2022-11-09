<?php

namespace CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

class EmailTemplatesRepository extends EntityRepository implements FilterableRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('et');

        return $qb;
    }

    /**
     * @param string $identificator
     * @param string $locale
     *
     * @return mixed
     */
    public function getEmailTemplate($identificator, $locale)
    {
        $qb = $this->createQueryBuilder('et');

        $qb
            ->addSelect('(CASE et.language WHEN :locale THEN 1 ELSE 0 END) as _order')
            ->andWhere($qb->expr()->eq('et.identificator', $qb->expr()->literal($identificator)))
            ->orderBy('_order', 'DESC')
            ->setParameter('locale', $locale)
            ->setMaxResults(1)
        ;

        try {
            $result = $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }

        return $result ? $result[0] : null;
    }
}
