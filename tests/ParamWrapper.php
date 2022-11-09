<?php

namespace Tests;

class ParamWrapper
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var array
     */
    private $criteria;

    /**
     * @var string
     */
    private $path;

    /**
     * ParamWrapper constructor.
     * @param $class
     * @param array $criteria
     * @param string $path
     */
    public function __construct($class, array $criteria, $path = 'id')
    {
        $this->class = $class;
        $this->criteria = $criteria;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class)
    {
        $this->class = $class;
    }

    /**
     * @return array
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }

    /**
     * @param array $criteria
     */
    public function setCriteria(array $criteria)
    {
        $this->criteria = $criteria;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
