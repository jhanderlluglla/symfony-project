<?php

namespace UserBundle\View;

use Pagerfanta\View\TwitterBootstrap3View;

class CustomTwitterBootstrap3View extends TwitterBootstrap3View
{
    protected function getDefaultProximity() {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'custom_twitter_bootstrap3';
    }
}