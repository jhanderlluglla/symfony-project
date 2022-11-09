<?php
namespace CoreBundle\Entity\Page\Elements;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Block
 *
 * @ORM\Table(name="page_button_block")
 * @ORM\Entity
 */
class ButtonBlock
{
    const DIRECTORY_ICON = "directory";
    const REDACTION_ICON = "redaction";
    const EXCHANGE_ICON = "exchange";

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    private $text;

    /**
     * @var Button $button
     *
     * @ORM\OneToOne(targetEntity="Button", cascade={"persist"})
     * @ORM\JoinColumn(name="button_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $button;

    /**
     * @ORM\ManyToOne(targetEntity="Container", inversedBy="buttonBlocks")
     * @ORM\JoinColumn(name="container_id", referencedColumnName="id")
     */
    private $container;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Choice(
     *      choices = {
     *          ButtonBlock::DIRECTORY_ICON,
     *          ButtonBlock::REDACTION_ICON,
     *          ButtonBlock::EXCHANGE_ICON,
     *      }
     * )
     */
    private $icon;

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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return Button
     */
    public function getButton()
    {
        return $this->button;
    }

    /**
     * @param Button $button
     */
    public function setButton($button)
    {
        $this->button = $button;
    }

    /**
     * @return mixed
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param mixed $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }
}