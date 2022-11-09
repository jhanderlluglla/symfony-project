<?php

namespace UserBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AbstractUserService
 *
 * @package UserBundle\Services
 */
abstract class AbstractUserService
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * UserService constructor.
     *
     * @param EntityManager       $entityManager
     * @param TranslatorInterface $translator
     */
    public function __construct($entityManager, $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }
}