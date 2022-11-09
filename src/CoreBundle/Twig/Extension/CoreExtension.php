<?php

namespace CoreBundle\Twig\Extension;

use CoreBundle\Entity\Constant\Country;
use CoreBundle\Entity\Constant\Language;
use CoreBundle\Services\AccessManager;
use CoreBundle\Services\LanguageService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Serializer\Serializer;

use CoreBundle\Entity\Message;
use CoreBundle\Entity\Settings;
use CoreBundle\Entity\User;

use Twig\TwigFunction;
/**
 * Class CoreExtension
 *
 * @package CoreBundle\Twig\Extension
 */
class CoreExtension extends \Twig_Extension
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Serializer
     */
    private $serializer;

    /** @var LanguageService */
    private $languageService;

    /** @var AccessManager */
    private $accessManager;

    /** @var RequestStack */
    private  $requestStack;

    /**
     * TransactionService constructor.
     *
     * @param EntityManager $entityManager
     * @param TranslatorInterface $translator
     * @param Serializer $serializer
     * @param LanguageService $languageService
     * @param AccessManager $accessManager
     * @param RequestStack  $requestStack
     */
    public function __construct($entityManager, TranslatorInterface $translator, Serializer $serializer, LanguageService $languageService, AccessManager $accessManager, RequestStack $requestStack)
    {
        $this->entityManager   = $entityManager;
        $this->translator      = $translator;
        $this->serializer      = $serializer;
        $this->languageService = $languageService;
        $this->accessManager   = $accessManager;
        $this->requestStack    = $requestStack;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'str_repeat',
                [$this, 'str_repeat'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'remuneration_webmaster',
                [$this, 'remuneration_webmaster'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'count_unread_messages',
                [$this, 'count_unread_messages'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'file_exists',
                [$this, 'check_if_file_exists'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'instanceof',
                [$this, 'instanceof_extension'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'floatval',
                [$this, 'to_floatval'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'isEuropeanCountry',
                [$this, 'isEuropeanCountry'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'isEuropeanCountryExceptFrance',
                [$this, 'isEuropeanCountryExceptFrance'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'getLanguageOptions',
                [$this, 'getLanguageOptions'],
                ['is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'getSiteLanguage',
                [$this, 'getSiteLanguage'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'checkCurrentRouteParams',
                [$this, 'checkCurrentRouteParams']
            ),
        ];
    }

    /**
     * @return array
     */
    public function getFilters() {
        return array(
            'md5' => new \Twig_SimpleFilter('md5', [$this, 'md5_filter']),
            'age' => new \Twig_SimpleFilter('age', [$this, 'age_filter'], ['is_safe' => ['html']]),
            'object_serialize' => new \Twig_SimpleFilter('object_serialize', [$this, 'object_serialize']),
        );
    }

    /**
     * @param string  $string
     * @param integer $multiplier
     * @param string  $input
     *
     * @return string
     */
    public function str_repeat($string, $multiplier ,$input = '&nbsp;')
    {
        return str_repeat($input, $multiplier) . ' ' . $string;
    }

    /**
     * @param User $user
     *
     * @return float|int
     */
    public function remuneration_webmaster($user)
    {
        $spending = $user->getSpending();

        if ($spending > 0) {
            return $spending;
        }

        $spending = $this->entityManager->getRepository(Settings::class)->getSettingValue(Settings::TARIFF_WEB);

        return !is_null($spending) ? (float) $spending:0;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function md5_filter($string)
    {
        return !empty($string) ? md5($string):md5(time());
    }

    /**
     * @param \DateTime $date
     *
     * @return string
     */
    public function age_filter($date)
    {
        $age = '';
        if (!is_null($date) && ($date instanceof \DateTimeInterface)) {
            $now = new \DateTime();
            $interval = $date->diff($now);

            if ($interval->y > 0) {
                $age.=  "<span> $interval->y " . $this->translator->trans('year', [], 'settings') . ", </span>";
            }

            if ($interval->m > 0) {
                $age.=  "<span> $interval->m " . $this->translator->trans('month', [], 'settings') . "</span>";
            }

//            if ($interval->d > 0) {
//                $age.=  "<span> $interval->d " . $this->translator->trans('day', [], 'settings') . "</span>";
//            }
        }

        return $age;
    }

    /**
     * @param User $user
     *
     * @return int
     */
    public function count_unread_messages($user)
    {
        return $this->entityManager->getRepository(Message::class)->getCountUnreadMessages($user, $this->accessManager->canAnswerMessage() && !$user->isSuperAdmin());
    }

    /**
     * @param object $object
     *
     * @return bool|float|int|string
     */
    public function object_serialize($object)
    {
        return $this->serializer->serialize($object, 'json', ['groups' => ['default']]);
    }

    /**
     * @param string $file
     * @return bool
     */
    public function check_if_file_exists($file)
    {
        return file_exists($file);
    }

    /**
     * @param $object
     * @param string $type
     * @return bool
     */
    public function instanceof_extension($object, $type)
    {
        return $object instanceof $type;
    }

    /**
     * @param string $value
     * @return float
     */
    public function to_floatval($value)
    {
        return floatval($value);
    }

    /**
     * @param string $isoCode
     * @return bool
     */
    public function isEuropeanCountry($isoCode)
    {
        return Country::isEuropeanCountry($isoCode);
    }

    /**
     * @param string $isoCode
     * @return bool
     */
    public function isEuropeanCountryExceptFrance($isoCode)
    {
        return Country::isEuropeanCountryExceptFrance($isoCode);
    }

    /**
     * @return array
     */
    public function getLanguageOptions()
    {
        return Language::getOptions();
    }

    /**
     * @return string
     */
    public function getSiteLanguage()
    {
        return $this->languageService->getLanguageFromUrl();
    }

    /**
     * @param string $route
     * @param array $params
     * @return boolean|string
     */
    public function checkCurrentRouteParams(array $routes,array $params){
        $currentRoute = $this->requestStack->getCurrentRequest()->attributes->get('_route');
        $currentRouteParams = $this->requestStack->getCurrentRequest()->attributes->get('_route_params');

        if (isset($currentRouteParams['status']) && in_array($currentRouteParams['status'],$params) && in_array($currentRoute,$routes)){
            return true;
        }
        if (!isset($currentRouteParams['status']) && in_array($currentRoute,$routes)){
            return true;
        }
        return false;
    }
}
