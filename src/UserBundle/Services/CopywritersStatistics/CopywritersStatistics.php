<?php

namespace UserBundle\Services\CopywritersStatistics;

class CopywritersStatistics
{
    /**
     * @var
     */
    private $monthsTotalEarnings;

    /**
     * @var
     */
    private $monthsTotalCounts;

    /** @var  CopywriterEarnings[] */
    private $copywriterEarnings;

    /**
     * @param $month
     * @param $count
     */
    public function increaseMonthTotalCount($month, $count)
    {
        if (isset($this->monthsTotalCounts[$month])) {
            $this->monthsTotalCounts[$month] += $count;
        } else {
            $this->monthsTotalCounts[$month] = $count;
        }
    }

    /**
     * @param $month
     * @param $earning
     */
    public function increaseMonthTotalEarning($month, $earning)
    {
        if (isset($this->monthsTotalEarnings[$month])) {
            $this->monthsTotalEarnings[$month] += $earning;
        } else {
            $this->monthsTotalEarnings[$month] = $earning;
        }
    }

    /**
     * @return mixed
     */
    public function getMonthsTotalEarnings()
    {
        return $this->monthsTotalEarnings;
    }

    /**
     * @return mixed
     */
    public function getMonthsTotalCounts()
    {
        return $this->monthsTotalCounts;
    }

    /**
     * @return CopywriterEarnings[]
     */
    public function getCopywriterEarnings()
    {
        return $this->copywriterEarnings;
    }

    /**
     * @param CopywriterEarnings $copywriterEarnings
     */
    public function addCopywritersEarnings(CopywriterEarnings $copywriterEarnings)
    {
        $this->copywriterEarnings[] = $copywriterEarnings;
    }

    /**
     * @return mixed
     */
    public function getTotalCount()
    {
        return $this->monthsTotalEarnings ? array_sum($this->monthsTotalCounts) : 0;
    }

    /**
     * @return mixed
     */
    public function getTotalEarning()
    {
        return $this->monthsTotalEarnings ? array_sum($this->monthsTotalEarnings) : 0;
    }
}