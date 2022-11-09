<?php

namespace CoreBundle\Repository;

use CoreBundle\Entity\Constant\Language;
use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingArticleRating;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\User;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Common\Collections\Criteria;

/**
 * Class UserRepository
 *
 * @package CoreBundle\Entity
 */
class UserRepository extends BaseRepository implements FilterableRepositoryInterface
{
    protected $filters = [
        'enabled',
        ['name' => 'id', 'filter' => 'notUser', 'compare' => BaseRepository::COMPARE_NOT_EQUIV],
    ];
    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('u');

        if (isset($filters['role'])) {
            $role = "";
            switch ($filters['role']) {
                case 'seo':
                    $role = User::ROLE_WRITER;
                    break;
                case 'webmaster':
                    $role = User::ROLE_WEBMASTER;
                    break;
                case 'administrator':
                    $role = User::ROLE_SUPER_ADMIN;
                    break;
                case 'writer_admin':
                    $role = User::ROLE_WRITER_ADMIN;
                    break;
                case 'writer_netlinking':
                    $role = User::ROLE_WRITER_NETLINKING;
                    break;
                case 'writer_copywriting':
                    $role = User::ROLE_WRITER_COPYWRITING;
                    break;
            }
            $qb->andWhere(
                $qb->expr()->like('u.roles', $qb->expr()->literal('%'.$role.'%'))
            );
        }

        if (isset($filters['roles'])) {
            if (!is_array($filters['roles'])) {
                $filters['roles'] = [$filters['roles']];
            }
            $cond = '(u.roles LIKE \'%"'.implode('"%\' OR u.roles LIKE \'%"', $filters['roles']).'"%\')';
            $qb->andWhere($cond);
        }

        $qb->orderBy('u.fullName', Criteria::ASC);

        if (isset($filters['balanceGt'])) {
            $qb->andWhere('u.balance > :balanceGt');
            $qb->setParameter('balanceGt', $filters['balanceGt']);
        }

        return $qb;
    }


    /**
     * @param string $hash
     *
     * @return object
     *
     * @throws NonUniqueResultException
     */
    public function getAffilationUser($hash)
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->where(
                $qb->expr()->eq('MD5(u.id)', $qb->expr()->literal($hash))
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param User $user
     *
     * @param null $isActive
     * @return array
     */
    public function getAllUsersExcludeCurrent($user, $isActive = null)
    {
        $filters = ['notUser' => $user];

        if (!is_null($isActive)) {
            $filters['enabled'] = (int) $isActive;
        }

        $qb = $this->filter($filters);


        return $qb->getQuery()->getResult();
    }

    /**
     * @param null|User $user
     * @param null $roles
     *
     * @return array
     */
    public function getAllUsersAsKeyAndValue($user = null, $roles = null)
    {
        $filters = [];

        if ($roles) {
            $filters['roles'] = $roles;
        }

        if (!is_null($user)) {
            $filters = ['notUser' => $user];
        }

        $qb = $this->filter($filters);

        $result = [];
        $users = $qb->getQuery()->getResult();
        if ($users) {
            /** @var User $user */
            foreach ($users as $user) {
                $result[$user->getId()] = $user;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getSuperUsersAsKeyAndValue()
    {
        $filters = ['roles' => User::ROLE_SUPER_ADMIN];

        $qb = $this->filter($filters);

        $result = [];
        $users = $qb->getQuery()->getResult();
        if ($users) {
            /** @var User $user */
            foreach ($users as $user) {
                $result[$user->getId()] = $user;
            }
        }

        return $result;
    }

    private function getAllUserByRoles($roles, $isActive = null)
    {
        $filters = ['roles' => $roles];

        if (!is_null($isActive)) {
            $filters['enabled'] = (int) $isActive;
        }

        $qb = $this->filter($filters);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param null|boolean $isActive
     *
     * @return array
     */
    public function getAllSeo($isActive = null)
    {
        return $this->getAllUserByRoles([User::ROLE_WRITER, User::ROLE_WRITER_COPYWRITING, User::ROLE_WRITER_NETLINKING], $isActive);
    }

    /**
     * @param null|boolean $isActive
     *
     * @return array
     */
    public function getAllWebmaster($isActive = null)
    {
        return $this->getAllUserByRoles([User::ROLE_WEBMASTER], $isActive);
    }

    /**
     * @param null|boolean $isActive
     *
     * @return array
     */
    public function getAllWriterAdmin($isActive = null)
    {
        return $this->getAllUserByRoles([User::ROLE_WRITER_ADMIN], $isActive);
    }

    /**
     * @param User $user
     * @param null $month
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCopywriterEarningsForMonth(User $user, $month = null)
    {
        if(is_null($month)){
            $month = date('m', strtotime("last month"));
        }
        $year = date('Y', strtotime("last month"));

        $sql = "SELECT 
                    SUM(ca.writer_earn)
                FROM
                    copywriting_article AS ca
                        INNER JOIN
                    copywriting_order AS co ON (ca.order_id = co.id)
                        INNER JOIN
                    fos_user AS u ON (co.copywriter_id = u.id)
                WHERE YEAR(co.approved_at) = :year
                    AND  MONTH(co.approved_at) = :month
                    AND co.status = :status
                    AND co.copywriter_id = :userId";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute(['year' => $year,'month' => $month,'userId' => $user->getId(), 'status' => CopywritingOrder::STATUS_COMPLETED]);

        return $stmt->fetchColumn();
    }

    /**
     * @param array $ids
     * @param null $month
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function usersCopywriterEarningsForMonth($ids, $month = null)
    {
        if(is_null($month)){
            $month = date('m', strtotime("last month"));
        }
        $year = date('Y', strtotime("last month"));

        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('SUM(ca.writerEarn) as earning_copywriting, IDENTITY(co.copywriter) as id')
            ->from(CopywritingArticle::class,'ca')
            ->innerJoin(CopywritingOrder::class,'co', 'WITH', 'IDENTITY(ca.order) = co.id')
            ->where('YEAR(co.approvedAt) = :year')
            ->andWhere('MONTH(co.approvedAt) = :month')
            ->andWhere('co.status = :status')
            ->andWhere('co.copywriter IN (:userIds)')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->setParameter('status', CopywritingOrder::STATUS_COMPLETED)
            ->setParameter('userIds', $ids)
            ->groupBy('co.copywriter')
        ;

        return $qb->getQuery()->getResult();
    }


    /**
     * @param User $user
     * @return bool|string
     */
    public function getCopywriterCountForLastMonth(User $user)
    {
        $month = date('m', strtotime("last month"));
        $year = date('Y', strtotime("last month"));

        $sql = "SELECT 
                    COUNT(ca.id)
                FROM
                    copywriting_article AS ca
                        INNER JOIN
                    copywriting_order AS co ON (ca.order_id = co.id)
                        INNER JOIN
                    fos_user AS u ON (co.copywriter_id = u.id)
                WHERE YEAR(co.approved_at) = :year
                    AND MONTH(co.approved_at) = :month
                    AND co.status = :status
                    AND co.copywriter_id = :userId";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute(['year' => $year,'month' => $month,'userId' => $user->getId(), 'status' => CopywritingOrder::STATUS_COMPLETED]);

        return $stmt->fetchColumn();
    }

    /**
     * @param User $user
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getAverageCopywriterRating(User $user)
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('SUM(CASE WHEN ar.value = true then 1 else 0 end) likes')
            ->addSelect('SUM(CASE WHEN ar.value = false then 1 else 0 end) dislikes')
            ->from(CopywritingOrder::class,'sa')
            ->innerJoin(CopywritingArticleRating::class,'ar', 'WITH', 'IDENTITY(ar.order) = sa.id AND sa.copywriter = :copywriter')
            ->setParameter('copywriter', $user)
        ;

        $result = $qb->getQuery()->getSingleResult();
        if(is_null($result['likes']) && is_null($result['dislikes'])){
            return null;
        }

        return (($result['likes'] - $result['dislikes']) / ($result['likes'] + $result['dislikes'])) * 100;
    }

    /**
     * @param array $ids
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function usersAverageCopywriterRating($ids)
    {
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('SUM(CASE WHEN ar.value = true then 1 else 0 end) likes')
            ->addSelect('SUM(CASE WHEN ar.value = false then 1 else 0 end) dislikes')
            ->addSelect('IDENTITY(sa.copywriter) as copywriter')
            ->from(CopywritingOrder::class,'sa')
            ->innerJoin(CopywritingArticleRating::class,'ar', 'WITH', 'IDENTITY(ar.order) = sa.id AND sa.copywriter IN( :copywriter )')
            ->groupBy('sa.copywriter')
            ->setParameter('copywriter', $ids)
        ;

        $result = $qb->getQuery()->getResult();

        $arRating = [];
        foreach ($result as $rating) {
            $arRating[$rating['copywriter']] = (($rating['likes'] - $rating['dislikes']) / ($rating['likes'] + $rating['dislikes'])) * 100;

        }

        return $arRating;
    }

    /**
     * @param $user
     * @param User|null $copywriter
     * @param string $language
     * @return array
     */
    public function getLikes($user, User $copywriter = null, $language = null)
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->addSelect('SUM(CASE WHEN r.value = true then 1 else 0 end) likes')
            ->addSelect('SUM(CASE WHEN r.value = false then 1 else 0 end) dislikes')
            ->addSelect('SUM(CASE WHEN r.value = true and o.customer = :user then 1 else 0 end) youLikeWriters')
            ->setParameter('user', $user, Type::OBJECT)
            ->where('o.copywriter IS NOT NULL')
            ->innerJoin('u.takenOrders', 'o')
            ->leftJoin('o.rating', 'r', Join::WITH, 'r.order = o.id')
            ->leftJoin('o.customer', 'customer', Join::WITH, 'o.customer = customer.id')
            ->leftJoin('o.copywriter', 'copywriter', Join::WITH, 'o.copywriter = copywriter.id')
            ->groupBy('o.copywriter')
        ;

        if (Language::validate($language)) {
            $qb
                ->andWhere('u.workLanguage = :workLanguage')
                ->setParameter('workLanguage', $language)
            ;
        }

        if (!is_null($copywriter)) {
            $qb
                ->andWhere('o.copywriter = :copywriter')
                ->setParameter('copywriter', $copywriter)
            ;

            return $qb->getQuery()->getArrayResult();
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getProjectLikes($ids)
    {
        $likes = $this->getLikesDislikes($ids, true);
        $dislikes = $this->getLikesDislikes($ids, false);
        $writerLikes = [];
        foreach ($likes as $like) {
            $writerLikes[$like['copywriter_id']]['text_likes'] = $like['likes'];
            $writerLikes[$like['copywriter_id']]['text_likes_dif'] = $like['customers'];
        }
        $writerDisLikes = [];
        foreach ($dislikes as $dislike) {
            $writerDisLikes[$dislike['copywriter_id']]['text_dislikes'] = $dislike['dislikes'];
            $writerDisLikes[$dislike['copywriter_id']]['text_dislikes_dif'] = $dislike['customers'];
        }

        $allWriters = [];
        foreach ($ids as $id) {
            $allWriters[$id] = [
                'text_likes' => 0,
                'text_dislikes' => 0,
                'text_likes_dif' => 0,
                'text_dislikes_dif' => 0
            ];

            if (array_key_exists($id, $writerLikes)) {
                $allWriters[$id]['text_likes'] = $writerLikes[$id]['text_likes'];
                $allWriters[$id]['text_likes_dif'] = $writerLikes[$id]['text_likes_dif'];
            }

            if (array_key_exists($id, $writerDisLikes)) {
                $allWriters[$id]['text_dislikes'] = $writerDisLikes[$id]['text_dislikes'];
                $allWriters[$id]['text_dislikes_dif'] = $writerDisLikes[$id]['text_dislikes_dif'];
            }
        }

        return $allWriters;
    }

    private function getLikesDislikes($ids, $like)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('IDENTITY(o.copywriter) as copywriter_id');
        if ($like) {
            $qb->addSelect('SUM(CASE WHEN r.value = true then 1 else 0 end) likes');
        } else {
            $qb->addSelect('SUM(CASE WHEN r.value = false then 1 else 0 end) dislikes');
        }
        $qb->addSelect('COUNT(DISTINCT o.customer) as customers')
            ->where('o.copywriter IS NOT NULL')
            ->andWhere('r.value = :value')
            ->innerJoin('u.takenOrders', 'o')
            ->leftJoin('o.rating', 'r', Join::WITH, 'r.order = o.id')
            ->andWhere(
                $qb->expr()->in('o.copywriter', ':copywriter')
            )
            ->setParameter('value', $like)
            ->setParameter('copywriter', $ids)
            ->groupBy('o.copywriter')
        ;

        return $qb->getQuery()->getArrayResult();
    }

    public function searchUsers($query)
    {
        $query = preg_replace('/[+\><\(\)~*\"@]+/', ' ', $query);
        $qb = $this->createQueryBuilder('u');

        $qb->where("MATCH_AGAINST (u.email, u.fullName) AGAINST(:matchAgainstQuery) > 1");
        $qb->orderBy("MATCH_AGAINST (u.email, u.fullName) AGAINST(:matchAgainstQuery)", "DESC");
        $qb->addOrderBy("LOCATE(:query, u.email)", "DESC");
        $qb->addOrderBy("LOCATE(:query, u.fullName)", "DESC");
        $qb->setParameter('query', $query);
        $qb->setParameter('matchAgainstQuery', "+*$query*");

        return $qb;
    }


    /**
     * @param string $username
     *
     * @return User|null
     */
    public function findByUsernameOrEmail($username)
    {
        $qb = $this->createQueryBuilder('u');
        $qb
            ->where('u.username = :username OR u.email = :username')
            ->setParameter(':username', $username)
        ;

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
