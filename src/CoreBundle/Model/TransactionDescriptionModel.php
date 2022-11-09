<?php

namespace CoreBundle\Model;

/**
 * Class TransactionDescriptionModel
 *
 * @package CoreBundle\Model
 */
class TransactionDescriptionModel
{
    /** @var string */
    private $idTranslate;

    /** @var array  */
    private $marks;

    /**
     * TransactionDescriptionModel constructor.
     *
     * @param $idTranslate
     * @param array $marks
     */
    public function __construct($idTranslate, $marks = [])
    {
        $this->idTranslate = $idTranslate;
        $this->marks = $marks;
    }

    /**
     * @return array
     */
    public function getMarks()
    {
        return $this->marks;
    }

    /**
     * @param array $marks
     *
     * @return TransactionDescriptionModel
     */
    public function setMarks($marks)
    {
        $this->marks = $marks;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIdTranslate()
    {
        return $this->idTranslate;
    }

    /**
     * @param mixed $idTranslate
     *
     * @return TransactionDescriptionModel
     */
    public function setIdTranslate($idTranslate)
    {
        $this->idTranslate = $idTranslate;

        return $this;
    }
}
