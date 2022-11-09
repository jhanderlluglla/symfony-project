<?php

namespace UserBundle\Services\CopywritersStatistics;

use CoreBundle\Entity\CopywritingOrder;
use Doctrine\ORM\EntityManager;

class CopywritersStatisticsBuilder
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function build($year)
    {
        $data = $this->em->getRepository(CopywritingOrder::class)->getEarningsForMonthsAndWriters($year);

        $copywritersStatistics = new CopywritersStatistics();

        foreach($this->groupStatistics($data) as $copywriter => $statistic) {
            $copywriterEarnings = new CopywriterEarnings();
            $copywriterEarnings->copywriterName = $copywriter;
            $copywriterEarnings->copywriterRegisteredAt = $statistic['registered_at'];
            foreach ($statistic['months'] as $monthNumber => $month) {
                $copywriterEarnings->monthsEarnings[$monthNumber] = $month['earning'];
                $copywriterEarnings->monthsCounts[$monthNumber] = $month['order_count'];

                $copywriterEarnings->totalCount += $month['order_count'];
                $copywriterEarnings->totalEarning += $month['earning'];

                $copywritersStatistics->increaseMonthTotalCount($monthNumber, $month['order_count']);
                $copywritersStatistics->increaseMonthTotalEarning($monthNumber, $month['earning']);
            }

            $copywritersStatistics->addCopywritersEarnings($copywriterEarnings);
        }

        return $copywritersStatistics;
    }

    private function groupStatistics($data)
    {
        $statisticsArray = [];

        foreach($data as $earning) {
            $statisticsArray[$earning['full_name']]['registered_at'] = $earning['registered_at'];
            $statisticsArray[$earning['full_name']]['months'][$earning['month']]['earning'] = $earning['earning'];
            $statisticsArray[$earning['full_name']]['months'][$earning['month']]['order_count'] = $earning['order_count'];
        }

        return $statisticsArray;
    }

}