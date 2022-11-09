<?php

namespace CoreBundle\Exceptions;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WorkflowTransitionException extends HttpException
{
    /** @var string */
    private $entityClass;

    /** @var int */
    private $entityId;

    /** @var string */
    private $transitionName;

    /** @var string */
    private $currentStatus;

    public function __construct($entityClass, $entityId, $currentStatus, $transitionName)
    {
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->transitionName = $transitionName;
        $this->currentStatus = $currentStatus;

        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            $entityClass . ' #' . $entityId . ' transition "' . $transitionName . '" from status "' . $currentStatus . '" impossible'
        );
    }
}
