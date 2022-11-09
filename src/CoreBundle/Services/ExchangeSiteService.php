<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\ExchangeSite;
use CoreBundle\Entity\MetricsHistory;
use CoreBundle\Entity\Settings;
use CoreBundle\Utils\ExchangeSiteUtil;
use Doctrine\ORM\EntityManager;

class ExchangeSiteService
{
    /** @var EntityManager */
    private $em;

    /**
     * ExchangeSiteService constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param ExchangeSite $site
     *
     * @throws \Exception
     */
    public function updatePrice(ExchangeSite $site)
    {
        $bwaAge = $site->getBwaAge() ?? new \DateTime();
        $age = floatval(ExchangeSiteUtil::dateDifference(new \DateTime(), $bwaAge, '%y.%m'));
        $credits = ExchangeSiteUtil::creditAlgo($site->getMajesticTrustFlow(), $site->getMajesticRefDomains(), $age);

        $creditPurchasePrice = $this->em->getRepository(Settings::class)->getSettingValue('prix_achat_credit');
        $maximumCredits = max($credits['cred'] * $creditPurchasePrice, $site->getSemrushTraffic()/100, $site->getSemrushTrafficCost()/100);

        $site->setMaximumCredits($maximumCredits);
        if ($maximumCredits < $site->getCredits()) {
            $site->setCredits($maximumCredits);
        }
    }
}
