<?php

namespace CoreBundle\Entity;


interface StateInterface
{

    const ACTIVE_YES = 1;
    const ACTIVE_NO  = 0;

    /**
     * @return int
     */
    public function getActive();

    /**
     * @param int $active
     */
    public function setActive($active);
}