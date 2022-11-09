<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingOrder;
use CoreBundle\Entity\CopywritingProject;
use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Model\TransactionDescriptionModel;
use CoreBundle\Services\CalculatorPriceService;
use Doctrine\ORM\EntityManager;

use CoreBundle\Entity\Transaction;
use CoreBundle\Entity\User;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Ldap\Adapter\ExtLdap\EntryManager;
use Symfony\Component\Translation\TranslatorInterface;

class UserService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $transactionService;

    /**
     * TransactionService constructor.
     *
     * @param EntryManager $entityManager
     * @param TransactionService $transactionService
     */
    public function __construct($entityManager, TransactionService $transactionService)
    {
        $this->entityManager = $entityManager;
        $this->transactionService = $transactionService;
    }

    /**
     * @param array $userFilter
     *
     * @return array
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function paymentAll($userFilter = [])
    {
        $payments = [];

        $users = $this->entityManager->getRepository(User::class)->filter(['balanceGt' => 0] + $userFilter)->getQuery()->getResult();

        /** @var User $user */
        foreach ($users as $user) {
            $payments[] = ['user' => $user, 'amount' =>  $user->getBalance()];
            $this->transactionService->handling(
                $user,
                new TransactionDescriptionModel('account.payment_writer'),
                0,
                $user->getBalance(),
                null,
                [User::TRANSACTION_TAG_PAYOUT]
            );
        }

        return $payments;
    }
}
