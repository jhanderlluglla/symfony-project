<?php

namespace CoreBundle\Services;

use CoreBundle\Entity\Constant\Language;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class LanguageService
 *
 * @package CoreBundle\Services
 */
class LanguageService
{
    /** @var TokenStorage */
    private $tokenStorage;

    /** @var Request */
    private $request;

    /** @var string */
    private $host;

    /**
     * LanguageService constructor.
     *
     * @param TokenStorage $tokenStorage
     * @param RequestStack $requestStack
     * @param  string $host
     */
    public function __construct(TokenStorage $tokenStorage, RequestStack $requestStack, $host)
    {
        $this->tokenStorage = $tokenStorage;
        $this->request = $requestStack->getCurrentRequest();
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getClientLanguage()
    {
        if (!$this->request) {
            return Language::EN;
        }

        $locale = $this->getLanguageFromHeaders();

        if ($locale === null) {
            $locale = $this->getLanguageFromUrl();
        }

        return $locale;
    }

    /**
     * @return string
     */
    public function getLanguageFromHeaders()
    {
        if (!$this->request || !$this->request->headers) {
            return null;
        }
        $header = $this->request->headers->get('Accept-Language');
        $acceptLanguage = explode(',', $header);

        foreach ($acceptLanguage as $locale) {
            $locale = explode('-', $locale);
            if (in_array($locale[0], Language::getAll())) {
                return $locale[0];
            }
        }

        return null;
    }

    /**
     * @param bool $normalize
     *
     * @return string|null
     */
    public function getLanguageFromUrl($normalize = true)
    {
        if (!$this->request) {
            return null;
        }

        $domainParse = explode('.', $this->request->getHost());
        if (count($domainParse) > 2 && in_array($domainParse[0], Language::getAll())) {
            return $domainParse[0];
        } elseif ($normalize === true) {
            return Language::EN;
        } else {
            return null;
        }
    }

    /**
     * @param $language
     *
     * @return string
     */
    public function getHostByLanguage($language)
    {
        if (!$this->request) {
            return $this->host;
        }

        return $language . '.' . $this->host;
    }

    /**
     * @param string $url
     * @param string $language
     *
     * @return string
     */
    public function prepareUrlForLanguage($url, $language)
    {
        $host = $this->getHostByLanguage($language);

        return str_replace(parse_url($url, PHP_URL_HOST), $host, $url);
    }

    /**
     * @return string
     */
    public function host()
    {
        return $this->host;
    }
}
