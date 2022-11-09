<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\StaticPage;

class StaticPageRepository extends BaseRepository implements FilterableRepositoryInterface
{

    protected $filters = ['identificator', 'language'];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('sp');

        $this->prepare($filters, $qb);

        return $qb;
    }

    /**
     * @param $id
     * @param $language
     *
     * @return StaticPage|null
     */
    public function findByIdentificator($id, $language = Language::EN)
    {
        if ($language !== Language::EN) {
            $languages = [$language, Language::EN];
        } else {
            $languages = [$language];
        }

        $pages = [];

        /** @var StaticPage $value */
        foreach ($this->filter(['identificator' => $id, 'language' => $languages])->getQuery()->getResult() as $value) {
            $pages[$value->getLanguage()] = $value;
        }

        if (empty($pages)) {
            return null;
        }

        return isset($pages[$language]) ? $pages[$language] : $pages[Language::EN];
    }
}
