<?php

namespace CoreBundle\Repository;

use Gedmo\Tree\Entity\Repository\NestedTreeRepository;

class CategoryRepository extends NestedTreeRepository implements FilterableRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        return $this->getNodesHierarchy();
    }

    /**
     * @param $sites
     * @return array
     */
    public function getCategoriesBySites($sites)
    {
        $qb = $this->createQueryBuilder('c');

        if(count($sites) > 0){
            $ids = [];
            foreach ($sites as $site){
                $ids[] = $site->getId();
            }
            $qb
                ->select('c.name')
                ->innerJoin('c.exchangeSites', 'es')
                ->where($qb->expr()->in('es.id', $ids))
            ;

            return $qb->getQuery()->getResult();
        }
        return [];
    }

    /**
     * @param null $language
     *
     * @return array
     */
    public function getCategoriesByLanguage($language = null)
    {
        $qb = $this->createQueryBuilder('c');

        $qb
            ->andWhere('c.parent IS NOT NULL')
            ->addOrderBy('c.lft', 'ASC')
        ;

        if ($language) {
            $qb
                ->andWhere('c.language = :language')
                ->setParameter('language', $language)
            ;
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function reorderAll($sortByField = null, $direction = 'ASC', $verify = true)
    {
        $rootNode = $this->findOneBy(['name' => 'root']);

        $this->reorder($rootNode, $sortByField, $direction, $verify);
    }
}