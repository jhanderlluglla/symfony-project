<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\Filter;
use CoreBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * Class FilterService
 *
 * @package UserBundle\Services
 */
class FilterService
{
    /** @var EntityManager */
    private $em;

    /**
     * FilterService constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param User $user
     * @param array $dealsType - examples: [ExchangeSite::class, Directory::class]
     * @param int $countDeal
     * @param int $countFilter - number of filters for which deals will be searched
     *
     * @return array
     */
    public function getDealsForUser($user, $dealsType = [ExchangeSite::class], $countDeal = 5, $countFilter = 1)
    {
        $deals = [];

        $userFilters = $this->em->getRepository(Filter::class)->findByFilter(
            [
                'type' => Filter::TYPE_DIRECTORY_LIST,
                'user' => $user,
            ],
            $countFilter,
            ['f.updatedAt' => 'DESC']
        );

        $dealEntityClasses = $dealsType;
        foreach ($dealsType as $dealEntityClass) {
            $deals[$dealEntityClass] = [];
        }

        /** @var Filter $userFilter */
        foreach ($userFilters as $userFilter) {
            $data = $userFilter->getData();
            $data['user'] = $user;
            $data['nonOwner'] = true;
            $data['siteType'] = ['exchange', 'universal'];

            foreach ($dealEntityClasses as $iEntityClass => $dealEntityClass) {
                $entityByFilter = $this->em->getRepository($dealEntityClass)->filter($data)->getQuery()->getResult();
                if (!$entityByFilter) {
                    continue;
                }

                /** @var ExchangeSite $entity */
                foreach ($entityByFilter as $entity) {
                    if (count($deals[$dealEntityClass]) === $countDeal) {
                        unset($dealEntityClasses[$iEntityClass]);
                        break;
                    }
                    $deals[$dealEntityClass][$entity->getId()] = $entity;
                }
            }

            if (count($dealEntityClasses) === 0) {
                break;
            }
        }

        $deals = $this->mergeDeals($deals);
        array_splice($deals, $countDeal);

        return $deals;
    }

    /**
     * @param array $deals
     *
     * @return array
     */
    private function mergeDeals($deals)
    {
        $result = [];
        foreach ($deals as $sitesByType) {
            $result += array_values($sitesByType);
        }

        usort($result, function ($a, $b) {
            if ($a->getCreatedAt() == $b->getCreatedAt()) {
                return 0;
            }
            return ($a->getCreatedAt() > $b->getCreatedAt()) ? -1 : 1;
        });

        return $result;
    }
}
