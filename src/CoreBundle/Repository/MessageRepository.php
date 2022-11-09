<?php

namespace CoreBundle\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\Common\Collections\Criteria;
use CoreBundle\Entity\Message;
use CoreBundle\Entity\User;

class MessageRepository extends BaseRepository implements FilterableRepositoryInterface
{

    /** @var array  */
    protected $filters = ['isRead', 'receiveUser'];

    /**
     * {@inheritdoc}
     */
    public function filter(array $filters, $count = false)
    {
        $qb = $this->createQueryBuilder('m');

        /** @var User $user */
        $user = $filters['user'];

        if (isset($filters['mode'])) {
            $qb
                ->orderBy('m.createdAt', Criteria::DESC)
                ->setParameter('user', $user, Type::OBJECT)
            ;
            if (isset($filters['adminMessageWebmaster'])) {
                $inverse = $filters['mode'] === 'sendUser' ? 'receiveUser' : 'sendUser';
                $qb
                    ->leftJoin('m.'.$inverse, 'iUser', 'WITH', 'iUser.id = m.'.$inverse)
                    ->andWhere(
                        $qb->expr()->like('iUser.roles', $qb->expr()->literal('%"' .User::ROLE_WEBMASTER. '"%'))
                    )
                    ->leftJoin('m.' . $filters['mode'], 'u_'.$filters['mode'], 'WITH', 'u_'.$filters['mode'].'.id = m.' . $filters['mode'])
                    ->andWhere(
                        $qb->expr()->orX(
                            'm.'.$filters['mode'].' = :user',
                            $qb->expr()->like('u_'.$filters['mode'].'.roles', $qb->expr()->literal('%' .User::ROLE_SUPER_ADMIN. '%'))
                        )
                    )
                    ->orWhere('m.'.$filters['mode'].' = :user');
                ;
            } else {
                $qb->where('m.'.$filters['mode'].' = :user');
            }
        } else {
            $qb
                ->orderBy('m.createdAt', Criteria::DESC)
                ->setParameter('user', $user, Type::OBJECT)
                ->where('m.sendUser = :user')
                ->orWhere('m.receiveUser = :user');
            if (isset($filters['adminMessageWebmaster'])) {
                $qb
                    ->leftJoin('m.sendUser', 'sUser', 'WITH', 'sUser.id = m.sendUser')
                    ->leftJoin('m.receiveUser', 'rUser', 'WITH', 'rUser.id = m.receiveUser')
                    ->orWhere(
                        $qb->expr()->andX(
                            $qb->expr()->like('sUser.roles', $qb->expr()->literal('%'.User::ROLE_SUPER_ADMIN.'%')),
                            $qb->expr()->like('rUser.roles', $qb->expr()->literal('%'.User::ROLE_WEBMASTER.'%'))
                        )
                    )
                    ->orWhere(
                        $qb->expr()->andX(
                            $qb->expr()->like('rUser.roles', $qb->expr()->literal('%'.User::ROLE_SUPER_ADMIN.'%')),
                            $qb->expr()->like('sUser.roles', $qb->expr()->literal('%'.User::ROLE_WEBMASTER.'%'))
                        )
                    );
            }
        }

        return $qb;
    }


    /**
     * @param User $user
     * @param bool $adminMessage
     *
     * @return int
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountUnreadMessages($user, $adminMessage = false)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select($qb->expr()->count('m') . ' as cnt');

        if ($adminMessage === false) {
            $this->prepare(['receiveUser' => $user, 'isRead' => Message::READ_NO], $qb);
        } else {
            $qb
                ->leftJoin('m.sendUser', 'sUser', 'WITH', 'sUser.id = m.sendUser')
                ->leftJoin('m.receiveUser', 'rUser', 'WITH', 'rUser.id = m.receiveUser')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->eq('m.receiveUser', ':user'),
                        $qb->expr()->andX(
                            $qb->expr()->like('rUser.roles', $qb->expr()->literal('%' . User::ROLE_SUPER_ADMIN . '%')),
                            $qb->expr()->like('sUser.roles', $qb->expr()->literal('%' . User::ROLE_WEBMASTER.'%'))
                        )
                    )
                )
                ->andWhere('m.isRead = :readNo')
                ->setParameter('readNo', Message::READ_NO)
                ->setParameter('user', $user, Type::OBJECT);
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return !empty($result['cnt']) ? $result['cnt'] : 0;
    }
}
