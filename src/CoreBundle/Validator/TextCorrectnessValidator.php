<?php

namespace CoreBundle\Validator;

use CoreBundle\Entity\CopywritingArticle;
use CoreBundle\Entity\CopywritingOrder;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UserBundle\Services\CopywritingArticleProcessor;

class TextCorrectnessValidator extends ConstraintValidator
{
    /** @var EntityManager */
    private $entityManager;

    /** @var TranslatorInterface */
    private $translator;

    /** @var CopywritingArticleProcessor $articleProcessor */
    private $articleProcessor;

    /**
     * TextCorrectnessValidator constructor.
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param CopywritingArticleProcessor $reportBuilder
     */
    public function __construct(
        EntityManager $entityManager,
        TranslatorInterface $translator,
        CopywritingArticleProcessor $reportBuilder
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->articleProcessor = $reportBuilder;
    }

    /**
     * @param mixed $article
     * @param Constraint $constraint
     *
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public function validate($article, Constraint $constraint)
    {
        return $this->validateArticle($article, $constraint);
    }

    /**
     * @param CopywritingArticle $article
     * @param Constraint $constraint
     * @param bool $ignoreNonConform
     *
     * @return \Symfony\Component\Validator\ConstraintViolationListInterface
     */
    public function validateArticle($article, Constraint $constraint, $ignoreNonConform = false)
    {
        $text = $article->getText();
        /** @var CopywritingOrder $order */
        $order = $article->getOrder();

        $words = $this->articleProcessor->getWords($text);
        $wordsCount = count($words);
        $requiredWordsCount = $order->getWordsNumber();

        $keywords = $order->getKeywords();

        if ($wordsCount < $requiredWordsCount) {
            $this->buildViolation(
                'words_number',
                [
                    '%n%' => $wordsCount,
                    '%m%' => $requiredWordsCount,
                    'name' => 'words_number',
                ],
                $wordsCount
            );
        }

        if (!$text || $text === '<p><br></p>') {
            return null;
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($text);

        if ($keywords) {
            list($keywordsCount, $missedKeywords,) = $this->articleProcessor->getMissedKeywords($words, $keywords);

            $keywordsPerArticleFrom = $order->getKeywordsPerArticleFrom();
            $keywordsPerArticleTo = $order->getKeywordsPerArticleTo();

            if ($keywordsPerArticleFrom || $keywordsPerArticleTo) {
                if ((($keywordsPerArticleFrom && $keywordsPerArticleFrom > $keywordsCount) || ($keywordsPerArticleTo && $keywordsPerArticleTo < $keywordsCount)) && ($ignoreNonConform || !$article->isNonconformExist('keywords'))) {
                    $this->buildViolation(
                        'keywords',
                        [
                            '%n%' => $keywordsPerArticleFrom,
                            '%m%' => $keywordsPerArticleTo,
                            '%l%' => $keywordsCount,
                            'name' => 'keywords',
                        ],
                        $keywordsCount
                    );
                }
            }

            if ($missedKeywords && !$article->isNonconformExist('keywords_used')) {
                $this->buildViolation(
                    'keywords_used',
                    [
                        '%keywords%' => implode(', ', $missedKeywords),
                        'name' => 'keywords_used',
                    ]
                );
            }
        }

        if ($order->isMetaTitle()) {
            $tagOccurrences = [$article->getMetaTitle()];
            $count = 0;

            if (!$article->getMetaTitle() && ($ignoreNonConform || !$article->isNonconformExist('meta_title'))) {
                $this->buildViolation('meta_title', ['name' => 'meta_title']);

            } elseif ($article->getMetaTitle()) {
                list($count,,) = $this->articleProcessor->getMissedKeywords($tagOccurrences, $keywords, true);
            }

            if ($order->isKeywordInMetaTitle() && !$count && ($ignoreNonConform || !$article->isNonconformExist('keyword_meta_title'))) {
                $this->buildViolation('keyword_meta_title', ['name' => 'keyword_meta_title']);
            }
        }

        if ($order->isMetaDescription() && !$article->getMetaDesc() && ($ignoreNonConform || !$article->isNonconformExist('meta_desc'))) {
            $this->buildViolation('meta_desc', ['name' => 'meta_desc']);
        }

        if ($order->getLinks()) {
            $links = $order->getLinks();
            $linksInText = $dom->getElementsByTagName('a');
            foreach ($links as $link) {
                $trimmedUrl = trim($link['url'], '/');

                foreach ($linksInText as $linkInText) {
                    $trimmedHref = trim($linkInText->getAttribute('href'), '/');
                    if ($trimmedUrl === $trimmedHref && $link['anchor'] === utf8_decode($linkInText->textContent)) {
                        continue 2;
                    }
                }
                $name = 'link-'.str_replace(' ', '-', $link['anchor']);
                if ($ignoreNonConform || !$article->isNonconformExist($name)) {
                    $this->buildViolation('links', [
                        'name' => $name, 'url' => $link['url'],
                        'anchor' => $link['anchor']
                    ]);
                }
            }
        }

        foreach (CopywritingArticleProcessor::TAGS as $tag) {
            $tagOccurrences = $dom->getElementsByTagName($tag['value']);
            $isNotNonconform = $ignoreNonConform || !$article->isNonconformExist($tag['name']);

            if ($order->isTagRequired($tag['value'])) {
                $isRange = $order->isTagRange($tag['value']);

                if ($isRange && !$article->isTagRangeValid($tag['value'], $tagOccurrences) && $isNotNonconform) {
                    if ($tag['value'] == 'img') {
                        $parameters = [
                            '%n%' => $order->getImagesPerArticleFrom(),
                            '%m%' => $order->getImagesPerArticleTo(),
                            '%l%' => $tagOccurrences->length,
                            'name' => $tag['name'],
                        ];
                    } else {
                        $parameters = [
                            '%n%' => $order->getTagRangeStart($tag['value']),
                            '%m%' => $order->getTagRangeEnd($tag['value']),
                            '%l%' => $tagOccurrences->length,
                            'name' => $tag['name'],
                        ];
                    }
                    $this->buildViolation($tag['name'], $parameters, $tagOccurrences->length);
                } elseif (!$isRange && !$tagOccurrences->length && $isNotNonconform) {
                    $this->buildViolation($tag['name'], ['name' => $tag['name']]);
                }

                if (isset($tag['maxCount']) && $tagOccurrences->length > $tag['maxCount']) {
                    $this->buildViolation(
                        $tag['name'],
                        ['%max_count%' => $tag['maxCount'],
                        '%n%' => $tagOccurrences->length],
                        0,
                        'max_count'
                    );
                }

                list($count,,) = $this->articleProcessor->getMissedKeywords($tagOccurrences, $keywords, true);
                if ($order->isKeywordInTagRequired($tag['value']) && !$count && ($ignoreNonConform || !$article->isNonconformExist($tag['keyword']))) {
                    $this->buildViolation($tag['keyword'], ['name' => $tag['keyword']]);
                }
            } elseif ($tagOccurrences->length && $isNotNonconform) {
                $this->buildViolation($tag['name'], ['name' => $tag['name']], 0, 'not_contain');
            }
        }

        return $this->context->getViolations();
    }

    /**
     * @param $requirement
     * @param $parameters
     * @param int $plural
     * @param string $status
     */
    private function buildViolation($requirement, $parameters, $plural = 1, $status = 'fail')
    {
        $this->context->buildViolation('requirements.'.$requirement.'.' . $status)
            ->setParameters($parameters)
            ->setPlural($plural)
            ->setTranslationDomain('copywriting')
            ->addViolation();
    }
}
