<?php
namespace UserBundle\View;

use WhiteOctober\PagerfantaBundle\View\TwitterBootstrapTranslatedView;

class CustomTwitterBootstrapTranslatedView extends TwitterBootstrapTranslatedView
{
    protected function buildPreviousMessage($text)
    {
        return $text;
    }

    protected function buildNextMessage($text)
    {
        return $text;
    }
}