<?php

namespace UserBundle\Form\Transformer;

use CoreBundle\Entity\CopywritingKeyword;
use CoreBundle\Entity\CopywritingOrder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class KeywordsTransformer
 * @package UserBundle\Form\Transformer
 */
class KeywordsTransformer implements DataTransformerInterface
{
    /**
     *
     * @param  ArrayCollection $keywordCollection
     * @return string
     */
    public function transform($keywordCollection)
    {
        if($keywordCollection) {
            return implode(', ', $keywordCollection->toArray());
        }

    }

    /**
     * @param  string $keywords
     * @return ArrayCollection|null
     */
    public function reverseTransform($keywords)
    {
        if ($keywords) {

            $keywords =  preg_split( "/(, |,)/", $keywords);
            $keywordCollection = new ArrayCollection();

            foreach ($keywords as $keyword) {

                $keywordEntity = new CopywritingKeyword();
                $keywordEntity->setWord($keyword);

                $keywordCollection->add($keywordEntity);
            }

            return $keywordCollection;
        }
    }
}