<?php

namespace CoreBundle\Entity;

use CoreBundle\Entity\Traits\LanguageTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * EmailTemplates
 *
 * @ORM\Table(name="email_templates", uniqueConstraints={@ORM\UniqueConstraint(name="search_idx", columns={"identificator", "language"})})
 * @ORM\Entity(repositoryClass="CoreBundle\Repository\EmailTemplatesRepository")
 */
class EmailTemplates
{
    use LanguageTrait;

    const NOTIFICATION_CRITICAL_ERROR = 'notification_critical_error';

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
     * @ORM\Column(name="subject", type="string", length=255)
     *
     * @Assert\NotBlank()
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="email_content", type="text")
     *
     * @Assert\NotBlank()
     */
    private $emailContent;

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
     * @return EmailTemplates
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
     * @return EmailTemplates
     */
    public function setIdentificator($identificator)
    {
        $this->identificator = $identificator;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return EmailTemplates
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmailContent()
    {
        return $this->emailContent;
    }

    /**
     * @param string $emailContent
     *
     * @return EmailTemplates
     */
    public function setEmailContent($emailContent)
    {
        $this->emailContent = $emailContent;

        return $this;
    }
}
