<?php

namespace UserBundle\Security;

use CoreBundle\Services\AccessManager;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class AbstractVoter extends Voter
{
    public const ACTION_SHOW_LIST = 'show_list';
    public const ACTION_SHOW = 'show_item';
    public const ACTION_DELETE = 'delete';
    public const ACTION_EDIT = 'edit';

    /** @var AccessDecisionManagerInterface */
    protected $decisionManager;

    /** @var AccessManager */
    protected $accessManager;

    /** @var string */
    protected $entity;

    /** @var string[] */
    protected $actions;

    /**
     * AbstractVoter constructor.
     *
     * @param AccessDecisionManagerInterface $decisionManager
     * @param AccessManager $accessManager
     * @param string $entity
     * @param string[] $actions
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, AccessManager $accessManager, $entity, $actions)
    {
        $this->decisionManager = $decisionManager;
        $this->accessManager = $accessManager;
        $this->entity = $entity;
        $this->actions = $this->prepareActions($actions);
    }

    /**
     * @param $actions
     *
     * @return array
     */
    private function prepareActions($actions)
    {
        $prepareActions = [];
        foreach ($actions as $action) {
            $prepareActions[] = mb_substr_count($action, '::') === 1 ? constant($action) : $action;
        }

        return $prepareActions;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     *
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (($subject !== $this->getEntity() && !is_a($subject, $this->getEntity())) || !in_array($attribute, $this->getActions())) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getActions()
    {
        return $this->actions;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
