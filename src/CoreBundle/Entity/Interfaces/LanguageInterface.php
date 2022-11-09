<?php

namespace CoreBundle\Entity\Interfaces;

/**
 * Interface LanguageInterface
 * @package CoreBundle\Entity\Interfaces
 */
interface LanguageInterface
{
    /**
     * @return string
     */
    public function getLanguage();

    /**
     * @param $language
     *
     * @return $this
     */
    public function setLanguage($language);
}
