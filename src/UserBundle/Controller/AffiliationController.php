<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use CoreBundle\Entity\Affiliation;
use CoreBundle\Entity\AffiliationClick;
use UserBundle\Security\MainVoter;

/**
 * Class AffiliationController
 *
 * @package UserBundle\Controller
 */
class AffiliationController extends Controller
{

    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        $affiliation = [];

        $this->denyAccessUnlessGranted(MainVoter::AFFILIATION . '.show', null);

        for ($i = 6; $i > -1; $i--) {
            $now = new \DateTime();
            $date = $now->sub(new \DateInterval('P' .$i. 'M'));

            $statistic = $this->getDoctrine()->getRepository(Affiliation::class)->getStatisticByUser($user, date('Y-m', $date->getTimestamp()));

            $affiliation[] = [
                'date' => date('F Y', $date->getTimestamp()),
                'clicks' => $this->getDoctrine()->getRepository(AffiliationClick::class)->getCountByUser($user, date('Y-m', $date->getTimestamp())),
                'registered' => $statistic['registered'],
                'earnings' => $statistic['earnings'],
            ];
        }

        return $this->render('affiliation/index.html.twig',
            [
                'affiliation' => $affiliation,
            ]);
    }

}