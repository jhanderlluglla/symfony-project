<?php

namespace CoreBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use CoreBundle\Entity\Constant\Language;
use Symfony\Component\Validator\Constraints as Assert;

trait LanguageTrait
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=2, options={"default": Language::EN})
     *
     * @Assert\NotBlank()
     * @Assert\Choice(callback={"CoreBundle\Entity\Constant\Language", "getAll"})
     */
    protected $language;

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }
}
