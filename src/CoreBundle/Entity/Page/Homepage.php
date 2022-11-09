<?php
namespace CoreBundle\Entity\Page;

use CoreBundle\Entity\Page\Elements\Button;
use CoreBundle\Entity\Page\Elements\Container;
use CoreBundle\Entity\Page\Elements\ListBlock;
use CoreBundle\Entity\Page\Elements\TextBlock;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Homepage
 *
 * @ORM\Table(name="page_homepage")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\Page\HomepageRepository")
 * @UniqueEntity("language")
 */
class Homepage
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var array
     * @ORM\Column(type="array", nullable=true)
     */
    private $topList;

    /**
     * @var Button $topButton
     *
     * @ORM\OneToOne(targetEntity="CoreBundle\Entity\Page\Elements\Button", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $topButton;

    /**
     * @var Container
     *
     * @ORM\OneToOne(targetEntity="CoreBundle\Entity\Page\Elements\Container", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $blockContainer;

    /**
     * @var TextBlock
     *
     * @ORM\OneToOne(targetEntity="CoreBundle\Entity\Page\Elements\TextBlock", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $textBlock;

    /**
     * @var ListBlock
     *
     * @ORM\OneToOne(targetEntity="CoreBundle\Entity\Page\Elements\ListBlock", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $listBlock;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=2, unique=true)
     *
     * @Assert\NotBlank()
     * @Assert\Choice(callback={"CoreBundle\Entity\Constant\Language", "getAll"})
     */
    private $language;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getTopList()
    {
        return $this->topList;
    }

    /**
     * @param array $topList
     */
    public function setTopList($topList)
    {
        $this->topList = $topList;
    }

    /**
     * @return Button
     */
    public function getTopButton()
    {
        return $this->topButton;
    }

    /**
     * @param Button $topButton
     */
    public function setTopButton($topButton)
    {
        $this->topButton = $topButton;
    }

    /**
     * @return Container
     */
    public function getBlockContainer()
    {
        return $this->blockContainer;
    }

    /**
     * @param Container $blockContainer
     */
    public function setBlockContainer($blockContainer)
    {
        $this->blockContainer = $blockContainer;
    }

    /**
     * @return TextBlock
     */
    public function getTextBlock()
    {
        return $this->textBlock;
    }

    /**
     * @param TextBlock $textBlock
     */
    public function setTextBlock($textBlock)
    {
        $this->textBlock = $textBlock;
    }

    /**
     * @return ListBlock
     */
    public function getListBlock()
    {
        return $this->listBlock;
    }

    /**
     * @param ListBlock $listBlock
     */
    public function setListBlock($listBlock)
    {
        $this->listBlock = $listBlock;
    }

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
