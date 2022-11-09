<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\Settings;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class SettingsRepository extends BaseRepository implements FilterableRepositoryInterface
{

    protected $filters = ['identificator'];
    /**
     * @param string $identificator
     *
     * @return mixed
     */
    public function getSettingValue($identificator)
    {
        $qb = $this->createQueryBuilder('s');

        $qb
            ->where(
                $qb->expr()->eq('s.identificator', $qb->expr()->literal($identificator))
            )
        ;

        /** @var Settings $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return !is_null($result) ? $result->getValue():null;
    }

    /**
     * @param string $groupName
     * @return array
     */
    public function getGroupOfSettings($groupName)
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where('s.identificator LIKE :groupName')
            ->setParameter(':groupName', $groupName.'%');

        return $this->transformSettings($qb->getQuery()->getArrayResult());
    }


    /**
     * @param array $settings
     * @return array
     */
    private function transformSettings(array $settings)
    {
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting['identificator']] = $setting['value'];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getSettingsArrayKeyValue()
    {
        $qb = $this->createQueryBuilder('s');

        $result = [];
        $settings = $qb->getQuery()->getResult();
        foreach ($settings as $setting) {
            $result[$setting->getIdentificator()] = $setting->getValue();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('s');

        $this->prepare($filters, $qb);

        if (isset($filters['settings_filter']['search_query'])) {
            $qb
                ->orWhere('s.name LIKE :search_query')
                ->orWhere('s.identificator LIKE :search_query')
                ->setParameter('search_query', '%' . addcslashes($filters['settings_filter']['search_query'], '%_') . '%');
        }

        return $qb;
    }

    /**
     * @param array $identificators
     * @return array
     */
    public function getSettingsByIdentificators(array $identificators)
    {
        $qb = $this->createQueryBuilder('s');

        $qb
            ->where('s.identificator IN (:identificators)')
            ->setParameter('identificators', array_values($identificators))
        ;

        return $this->transformSettings($qb->getQuery()->getArrayResult());
    }
}