<?php

namespace CoreBundle\Entity\Page\Elements;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Container
 *
 * @ORM\Table(name="page_container")
 * @ORM\Entity
 */
class Container
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
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="CoreBundle\Entity\Page\Elements\ButtonBlock",
     *     mappedBy="container",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $buttonBlocks;

    public function __construct()
    {
        $this->buttonBlocks = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ArrayCollection
     */
    public function getButtonBlocks()
    {
        return $this->buttonBlocks;
    }

    /**
     * @param ArrayCollection $buttonBlocks
     */
    public function setButtonBlocks($buttonBlocks)
    {
        foreach($buttonBlocks as $block){
            $this->addButtonBlock($block);
        }
    }

    /**
     * @param ButtonBlock $buttonBlock
     */
    public function addButtonBlock($buttonBlock)
    {
        if (!$this->buttonBlocks->contains($buttonBlock)) {
            $this->buttonBlocks->add($buttonBlock);

            $buttonBlock->setContainer($this);
        }
    }

    /**
     * @param ButtonBlock $buttonBlock
     */
    public function removeButtonBlock($buttonBlock)
    {
        if ($this->buttonBlocks->contains($buttonBlock)) {
            $this->buttonBlocks->removeElement($buttonBlock);
        }
    }
}