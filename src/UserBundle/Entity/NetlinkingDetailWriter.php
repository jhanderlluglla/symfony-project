<?php

namespace UserBundle\Entity;

/**
 * Class NetlinkingDetailWriter
 *
 * @package UserBundle\Entity
 */
class NetlinkingDetailWriter
{
    /**
     * @var float
     */
    private $compensation;

    /**
     * @var float
     */
    private $compensationBonus;

    /**
     * @var float
     */
    private $webmasterTaskCost;

    /**
     * @var float
     */
    private $writerTaskCost;

    /**
     * @var int
     */
    private $taskWordsCount;

    /**
     * @var string
     */
    private $projectInstructions;

    /**
     * @var string
     */
    private $directoryInstructions;

    /**
     * @var string
     */
    private $anchors;

    /**
     * @return float
     */
    public function getCompensation()
    {
        return $this->compensation;
    }

    /**
     * @param float $compensation
     *
     * @return NetlinkingDetailWriter
     */
    public function setCompensation($compensation)
    {
        $this->compensation = $compensation;

        return $this;
    }

    /**
     * @return float
     */
    public function getCompensationBonus()
    {
        return $this->compensationBonus;
    }

    /**
     * @param float $compensationBonus
     *
     * @return NetlinkingDetailWriter
     */
    public function setCompensationBonus($compensationBonus)
    {
        $this->compensationBonus = $compensationBonus;

        return $this;
    }

    /**
     * @return float
     */
    public function getWebmasterTaskCost()
    {
        return $this->webmasterTaskCost;
    }

    /**
     * @param float $webmasterTaskCost
     *
     * @return NetlinkingDetailWriter
     */
    public function setWebmasterTaskCost($webmasterTaskCost)
    {
        $this->webmasterTaskCost = $webmasterTaskCost;

        return $this;
    }

    /**
     * @return float
     */
    public function getWriterTaskCost()
    {
        return $this->writerTaskCost;
    }

    /**
     * @param float $writerTaskCost
     *
     * @return NetlinkingDetailWriter
     */
    public function setWriterTaskCost($writerTaskCost)
    {
        $this->writerTaskCost = $writerTaskCost;

        return $this;
    }

    /**
     * @return int
     */
    public function getTaskWordsCount()
    {
        return $this->taskWordsCount;
    }

    /**
     * @param int $taskWordsCount
     *
     * @return NetlinkingDetailWriter
     */
    public function setTaskWordsCount($taskWordsCount)
    {
        $this->taskWordsCount = $taskWordsCount;

        return $this;
    }

    /**
     * @return string
     */
    public function getProjectInstructions()
    {
        return nl2br(stripslashes($this->projectInstructions));
    }

    /**
     * @return bool
     */
    public function isProjectInstructions()
    {
        return !empty(trim($this->projectInstructions));
    }

    /**
     * @param string $projectInstructions
     *
     * @return NetlinkingDetailWriter
     */
    public function setProjectInstructions($projectInstructions)
    {
        $this->projectInstructions = $projectInstructions;

        return $this;
    }

    /**
     * @return string
     */
    public function getDirectoryInstructions()
    {
        return nl2br(stripslashes($this->directoryInstructions));
    }

    /**
     * @return bool
     */
    public function isDirectoryInstructions()
    {
        return !empty(trim($this->directoryInstructions));
    }

    /**
     * @param string $directoryInstructions
     *
     * @return NetlinkingDetailWriter
     */
    public function setDirectoryInstructions($directoryInstructions)
    {
        $this->directoryInstructions = $directoryInstructions;

        return $this;
    }

    /**
     * @return string
     */
    public function getAnchors()
    {
        return $this->anchors;
    }

    /**
     * @param string $anchors
     *
     * @return NetlinkingDetailWriter
     */
    public function setAnchors($anchors)
    {
        if (!is_array($anchors)) {
            return $this;
        }

        $this->anchors = implode(', ', $anchors);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAnchors()
    {
        return !empty($this->anchors);
    }
}

