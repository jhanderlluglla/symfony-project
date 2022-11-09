<?php

namespace CoreBundle\Exceptions;

class WorkflowTransitionEntityException extends WorkflowTransitionException
{

    /**
     * WorkflowTransitionEntityException constructor.
     *
     * @param object $entity
     * @param $transitionName
     */
    public function __construct($entity, $transitionName)
    {
        parent::__construct(get_class($entity), $entity->getId(), $entity->getStatus(), $transitionName);
    }
}
