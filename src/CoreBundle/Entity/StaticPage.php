<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\LanguageTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * StaticPage
 *
 * @ORM\Table(name="static_page", uniqueConstraints={@ORM\UniqueConstraint(name="search_idx", columns={"identificator", "language"})})
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\StaticPageRepository")
 */
class StaticPage
{
    use LanguageTrait;

    const PAGE_HELP_WEBMASTER = 'help_webmaster';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="identificator", type="string", length=255)
     *
     * @Assert\NotBlank()
     */
    private $identificator;

    /**
     * @var string
     *
     * @ORM\Column(name="page_content", type="text")
     *
     * @Assert\NotBlank()
     */
    private $pageContent;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return StaticPage
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentificator()
    {
        return $this->identificator;
    }

    /**
     * @param string $identificator
     *
     * @return StaticPage
     */
    public function setIdentificator($identificator)
    {
        $this->identificator = $identificator;

        return $this;
    }

    /**
     * @return string
     */
    public function getPageContent()
    {
        return $this->pageContent;
    }

    /**
     * @param string $pageContent
     *
     * @return StaticPage
     */
    public function setPageContent($pageContent)
    {
        $this->pageContent = $pageContent;

        return $this;
    }
}
