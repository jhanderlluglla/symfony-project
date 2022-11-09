<?php

namespace AppBundle\Services;

use CoreBundle\Model\TransactionDescriptionModel;
use Doctrine\ORM\EntityManager;

use CoreBundle\Services\TransactionService;

use CoreBundle\Entity\Affiliation;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;

class AffiliationService
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * AffiliationService constructor.
     *
     * @param EntityManager      $entityManager
     * @param TransactionService $transactionService
     */
    public function __construct($entityManager, $transactionService)
    {
        $this->entityManager = $entityManager;
        $this->transactionService = $transactionService;
    }

    /**
     * @param string $affiliation
     * @param User   $subject
     */
    public function handling($affiliation, $subject)
    {
        /** @var User $parentUser */
        $parentUser = $this->entityManager->getRepository(User::class)->getAffilationUser($affiliation);
        $hasAffilation = $this->entityManager->getRepository(Affiliation::class)->hasAffilation($parentUser, $subject);

        if (!is_null($parentUser) && !$hasAffilation) {
            $subject->setAffiliation($parentUser);
            $this->entityManager->persist($subject);

            $tariff = $this->getRemunerationAffiliation($parentUser);

            $affiliation = new Affiliation();
            $affiliation
                ->setParent($parentUser)
                ->setAffiliation($subject)
                ->setTariff($tariff)
            ;

            $this->entityManager->persist($affiliation);

            $this->transactionService->handling(
                $parentUser,
                new TransactionDescriptionModel('affiliation.affiliation'),
                $tariff,
                0,
                null,
                [Affiliation::TRANSACTION_TAG]
            );
        }
    }

    /**
     * @param User $parentUser
     *
     * @return float
     */
    private function getRemunerationAffiliation($parentUser)
    {
        $affiliationTariff = $parentUser->getAffiliationTariff();

        if ($affiliationTariff > 0) {
            return $affiliationTariff;
        }

        $affiliation = $this->entityManager->getRepository(Settings::class)->getSettingValue('affiliation');

        return !is_null($affiliation) ? (float) $affiliation:0;
    }
}