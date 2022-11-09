<?php

namespace UserBundle\Security;

use CoreBundle\Entity\Invoice;
use CoreBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class InvoiceVoter extends Voter
{
    const VIEW = 'view';

    const DOWNLOAD = 'download';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * InvoiceVoter constructor.
     * @param AccessDecisionManagerInterface $decisionManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, array(self::VIEW, self::DOWNLOAD))) {
            return false;
        }

        if (!$subject instanceof Invoice) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($this->decisionManager->decide($token, array('ROLE_SUPER_ADMIN'))) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Invoice $invoice */
        $invoice = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($invoice, $user);
            case self::DOWNLOAD:
                return $this->canDownload($invoice, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param Invoice $invoice
     * @param User $user
     * @return bool
     */
    private function canView(Invoice $invoice, User $user)
    {
        return $user === $invoice->getUser();
    }

    /**
     * @param Invoice $invoice
     * @param User $user
     * @return bool
     */
    private function canDownload(Invoice $invoice, User $user)
    {
        return $user === $invoice->getUser();
    }
}