<?php

namespace UserBundle\Twig\Extension;

use CoreBundle\Services\ExchangePropositionService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

use CoreBundle\Entity\ExchangeProposition;
use CoreBundle\Entity\Settings;

class ExchangePropositionExtension extends \Twig_Extension
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $docsLocalPath;

    /** @var ExchangePropositionService */
    private $exchangePropositionService;

    /**
     * ExchangeSiteExtension constructor.
     *
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param string $docsLocalPath
     * @param ExchangePropositionService $exchangePropositionService
     */
    public function __construct($entityManager, $translator, $docsLocalPath, ExchangePropositionService $exchangePropositionService)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
        $this->docsLocalPath = $docsLocalPath;
        $this->exchangePropositionService = $exchangePropositionService;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'exchange_proposition_status_text',
                [$this, 'exchange_proposition_status_text'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'exchange_proposition_status_class',
                [$this, 'exchange_proposition_status_class'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'exchange_proposition_comment',
                [$this, 'exchange_proposition_comment'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @return array
     */
    public function getFilters() {
        return array(
            'hasDaysAnswer' => new \Twig_SimpleFilter('hasDaysAnswer', [$this, 'days_answer_filter'], ['is_safe' => ['html']]),
        );
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     *
     * @return string
     */
    public function exchange_proposition_status_text($exchangeProposition)
    {
        return $this->translator->trans('statuses.' . $exchangeProposition->getStatus(), [], 'exchange_site_result_proposals');
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     *
     * @return string
     */
    public function exchange_proposition_status_class($exchangeProposition)
    {
        //todo: remove this function, use only $exchangeProposition->getStatus()
        $cssClass = '';
        switch ($exchangeProposition->getStatus()) {
            case ExchangeProposition::STATUS_IN_PROGRESS:
                $cssClass = 'written_process';
                break;

            case ExchangeProposition::STATUS_AWAITING_WRITER:
                $cssClass = 'awaiting_writing';
                break;

            case ExchangeProposition::STATUS_CHANGED:
                $cssClass = '';
                break;

            case ExchangeProposition::STATUS_REFUSED:
                $cssClass = 'refused';
                break;

            case ExchangeProposition::STATUS_EXPIRED:
                $cssClass = 'partner_not_answer';
                break;

            case ExchangeProposition::STATUS_IMPOSSIBLE:
                $cssClass = 'impossible';
                break;

            case ExchangeProposition::STATUS_ACCEPTED:
                $cssClass = '';
                break;

            case ExchangeProposition::STATUS_PUBLISHED:
                $cssClass = 'published';
                break;
        }

        return $cssClass . ' status_' . $exchangeProposition->getStatus();
    }

    /**
     * @param ExchangeProposition $exchangeProposition
     *
     * @return string
     */
    public function exchange_proposition_comment($exchangeProposition)
    {
        $replace = [];
        $comment = '';
        $addToComment = '';

        switch ($exchangeProposition->getStatus()) {
            case ExchangeProposition::STATUS_AWAITING_WEBMASTER:
            case ExchangeProposition::STATUS_EXPIRED:
            case ExchangeProposition::STATUS_ACCEPTED:
                $replace = ['%days%' => $this->exchangePropositionService->getDaysForResponse($exchangeProposition->getCreatedAt())];
                break;

            case ExchangeProposition::STATUS_CHANGED:
            case ExchangeProposition::STATUS_REFUSED:
                $addToComment = $exchangeProposition->getComments();
                break;

            case ExchangeProposition::STATUS_PUBLISHED:
                $comment = '<p>' .$exchangeProposition->getComments(). '</p>';
                if ($exchangeProposition->getModificationStatus() > ExchangeProposition::MODIFICATION_STATUS_0) {
                    if ($exchangeProposition->getModificationStatus() == ExchangeProposition::MODIFICATION_STATUS_3) {
                        $replace =  ['%comment%' => $exchangeProposition->getModificationRefuseComment()];
                    }
                    $comment = '<p>' . $this->translator->trans('comments.modification_status_' . $exchangeProposition->getModificationStatus(), $replace, 'exchange_site_result_proposals').'</p>';
                }
                break;
        }

        if ($comment === '') {
            $comment = '<p>' . $this->translator->trans('comments.'.$exchangeProposition->getStatus(), $replace, 'exchange_site_result_proposals') . '</p>';
        }

        if ($addToComment !== '') {
            $comment = '<p>' . $addToComment . '</p>';
        }

        if ($exchangeProposition->getDocumentImage() && $exchangeProposition->getStatus() !== ExchangeProposition::STATUS_PUBLISHED) {
            $href = $this->docsLocalPath . DIRECTORY_SEPARATOR . $exchangeProposition->getDocumentImage();
            $comment.= $this->translator->trans('comments.retrieve_article', ['%href%' => $href], 'exchange_site_result_proposals');
        }

        return $comment;
    }

    /**
     * @param \DateTime $date
     *
     * @return string
     */
    public function days_answer_filter($date)
    {
        return $this->translator->trans('table.have_days', ['%days%' => $this->exchangePropositionService->getDaysForResponse($date)], 'exchange_site_proposals');
    }
}
