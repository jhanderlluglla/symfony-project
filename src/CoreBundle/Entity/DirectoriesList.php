<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\ExternalIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * DirectoriesList
 *
 * @ORM\Table(name="directories_list")
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\DirectoriesListRepository")
 * @UniqueEntity(
 *      fields={"name", "user"},
 *      errorPath="name",
 *      ignoreNull=true
 * )
 */
class DirectoriesList
{
    use ExternalIdTrait;

    const ENABLE  = 1;
    const DISABLE = 0;

    public const CONTAINS_ONLY_BLOG = 'blog';
    public const CONTAINS_ONLY_DIRECTORY = 'directory';
    public const CONTAINS_ALL = 'all';

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
     */
    private $name;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     **/
    private $user;

    /**
     * @var ArrayCollection $directories
     *
     * @ORM\ManyToMany(targetEntity="CoreBundle\Entity\Directory", inversedBy="directoriesList", cascade={"persist"})
     * @ORM\JoinTable(name="directories_list_directory",
     *      joinColumns={@ORM\JoinColumn(name="directory_list_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="directory_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $directories;

    /**
     * @var ArrayCollection $directories
     *
     * @ORM\ManyToMany(targetEntity="CoreBundle\Entity\ExchangeSite", inversedBy="directoriesList", cascade={"persist"})
     * @ORM\JoinTable(name="exchange_site_list_directory",
     *      joinColumns={@ORM\JoinColumn(name="directory_list_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="exchange_site_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    private $exchangeSite;

    /**
     * @var integer
     *
     * @ORM\Column(name="words_count", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $wordsCount;

    /**
     * @var string
     *
     * @ORM\Column(name="filter", type="text", nullable=true)
     */
    private $filter;

    /**
     * @var int
     *
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_seen", type="datetime", nullable=true)
     */
    private $lastSeen;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var ArrayCollection $netlinkingProject
     *
     * @ORM\OneToMany(targetEntity="NetlinkingProject", mappedBy="directoryList", cascade={"persist", "remove"})
     */
    private $netlinkingProject;

    /**
     * DirectoriesList constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime;
        $this->enabled = self::ENABLE;
        $this->directories = new ArrayCollection();
        $this->exchangeSite = new ArrayCollection();
        $this->netlinkingProject = new ArrayCollection();
    }

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
     * @return DirectoriesList
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return DirectoriesList
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return int
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param int $enabled
     *
     * @return DirectoriesList
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return DirectoriesList
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return DirectoriesList
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getWordsCount()
    {
        return $this->wordsCount === null ? 0 : $this->wordsCount;
    }

    /**
     * @param int $wordsCount
     *
     * @return DirectoriesList
     */
    public function setWordsCount($wordsCount)
    {
        $this->wordsCount = $wordsCount;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastSeen()
    {
        return $this->lastSeen;
    }

    /**
     * @param \DateTime $lastSeen
     *
     * @return DirectoriesList
     */
    public function setLastSeen($lastSeen)
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * @param ArrayCollection $directories
     *
     * @return DirectoriesList
     */
    public function setDirectories($directories)
    {
        $this->directories = $directories;

        return $this;
    }

    /**
     * Add directories
     *
     * @param Directory $directory
     *
     * @return DirectoriesList
     */
    public function addDirectories(Directory $directory)
    {
        if (!$this->directories->contains($directory)) {
            $directory->addDirectoriesList($this);
            $this->directories->add($directory);
        }

        return $this;
    }

    /**
     * Remove directories
     *
     * @param Directory $directory
     */
    public function removeDirectories(Directory $directory)
    {
        if ($this->directories->contains($directory)) {
            $this->directories->removeElement($directory);
        }

        return $this;
    }

    /**
     * @param Directory $directory
     *
     * @return bool
     */
    public function hasDirectory(Directory $directory)
    {
        return $this->directories->contains($directory);
    }

    /**
     * @return ArrayCollection
     */
    public function getExchangeSite()
    {
        return $this->exchangeSite;
    }

    /**
     * @param ArrayCollection $exchangeSite
     *
     * @return DirectoriesList
     */
    public function setExchangeSite($exchangeSite)
    {
        $this->exchangeSite = $exchangeSite;

        return $this;
    }

    /**
     * Add ExchangeSite
     *
     * @param ExchangeSite $exchangeSite
     *
     * @return DirectoriesList
     */
    public function addExchangeSite(ExchangeSite $exchangeSite)
    {
        if (!$this->exchangeSite->contains($exchangeSite)) {
            $exchangeSite->addDirectoriesList($this);
            $this->exchangeSite->add($exchangeSite);
        }

        return $this;
    }

    /**
     * Remove ExchangeSite
     *
     * @param ExchangeSite $exchangeSite
     *
     * @return DirectoriesList
     */
    public function removeExchangeSite(ExchangeSite $exchangeSite)
    {
        if ($this->exchangeSite->contains($exchangeSite)) {
            $this->exchangeSite->removeElement($exchangeSite);
        }

        return $this;
    }

    /**
     * @param ExchangeSite $exchangeSite
     *
     * @return bool
     */
    public function hasExchangeSite(ExchangeSite $exchangeSite)
    {
        return $this->exchangeSite->contains($exchangeSite);
    }

    /**
     * @return ArrayCollection
     */
    public function getNetlinkingProject()
    {
        return $this->netlinkingProject;
    }

    /**
     * @param ArrayCollection $netlinkingProject
     *
     * @return DirectoriesList
     */
    public function setNetlinkingProject($netlinkingProject)
    {
        $this->netlinkingProject = $netlinkingProject;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param string $filter
     *
     * @return DirectoriesList
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @return string
     */
    public function getContainsType()
    {
        if ($this->getDirectories()->isEmpty() && !$this->getExchangeSite()->isEmpty()) {
            return DirectoriesList::CONTAINS_ONLY_BLOG;
        } elseif (!$this->getDirectories()->isEmpty() && $this->getExchangeSite()->isEmpty()) {
            return DirectoriesList::CONTAINS_ONLY_DIRECTORY;
        }

        return DirectoriesList::CONTAINS_ALL;
    }
}
