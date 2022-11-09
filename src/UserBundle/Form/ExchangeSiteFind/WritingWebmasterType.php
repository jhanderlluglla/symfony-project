<?php

namespace UserBundle\Form\ExchangeSiteFind;

/**
 * Class WritingWebmasterType
 *
 * @package UserBundle\Form\ExchangeSiteFind
 */
class WritingWebmasterType extends WritingErefererType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'user_writing_webmaster';
    }
}