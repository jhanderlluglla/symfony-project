<?php

namespace Tests;

use CoreBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Swift_Plugins_MessageLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UserBundle\EventListener\LocaleListener;

abstract class AbstractTest extends KernelTestCase
{
    use ORMTestCaseTrait;

    /** @var ContainerInterface */
    private $container;

    /** @var Client */
    private $client;

    /** @var Swift_Plugins_MessageLogger */
    private $messageLogger;

    protected $headers = [
        'User-Agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:63.0) Gecko/20100101 Firefox/63.0',
    ];

    /**
     * setUp
     */
    protected function setUp()
    {
        static::bootKernel();

        $this->container = static::$kernel->getContainer();

        $this->client = $this->container->get('test.client');
        $this->client->disableReboot();

        $this->messageLogger = new Swift_Plugins_MessageLogger();

        $this->callTraitHookMethod('setup');
    }


    protected function tearDown()
    {
        $this->callTraitHookMethod('tearDown');

        parent::tearDown();
    }

    /**
     * @return ContainerInterface
     */
    public function container()
    {
        return $this->container;
    }

    /**
     * @return EntityManager
     */
    public function em()
    {
        return $this->em;
    }

    /**
     * @return \Symfony\Component\HttpKernel\KernelInterface
     */
    public function kernel()
    {
        return static::$kernel;
    }

    /**
     * @param $className
     * @param $criteria
     * @param bool $fail
     *
     * @return null|object
     */
    public function getObjectOf($className, $criteria, $fail = true)
    {
        $obj = $this->em->getRepository($className)->findOneBy($criteria);

        if ($fail && !$obj) {
            self::assertNotNull($obj, 'Test object not found: ', print_r($criteria, true));
        }

        return $obj;
    }

    /**
     * @return Client
     */
    public function client()
    {
        return $this->client;
    }

    /**
     * @param $hook
     *
     * @throws \ReflectionException
     */
    private function callTraitHookMethod($hook)
    {
        $rc = new \ReflectionClass(self::class);
        foreach($rc->getTraitNames() as $trait) {
            $traitStruct = explode('\\', $trait);
            $trait = end($traitStruct);
            $method = "{$hook}{$trait}";
            if (method_exists($this, $method)) {
                $this->$method();
            }
        }
    }

    /**
     * @param User $user
     *
     * @param string $pwd
     * @return $this
     */
    protected function setUser($user, $pwd = '123')
    {
        $this->client()->request('GET', '/logout');
        $crawler = $this->client()->request('GET', '/login');
        $form = $crawler->selectButton('_submit')->form([
            '_username'  => $user->getUsername(),
            '_password'  => $pwd,
        ]);

        $this->client()->submit($form);

        $session = $this->container()->get('session');
        $cookie = new Cookie($session->getName(), $session->getId());

        $this->client()->getCookieJar()->set($cookie);

        return $this;
    }

    /**
     * @param string $url
     * @param array $data
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function sendPost($url, $data = [])
    {
        $this->processParamWrappers($data);

        return $this->request(Request::METHOD_POST, $url, $data, $this->headers);
    }


    /**
     * @param string $url
     * @param array $data
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function sendGet($url, $data = [])
    {
        $this->processParamWrappers($data);

        return $this->request(Request::METHOD_GET, $url, $data, $this->headers);
    }

    /**
     * @param string $url
     * @param $formName
     * @param array $formData
     * @param array $getData
     * @param string $formSelector - If you need to simulate a click on a button, set the button selector in the form selector
     *
     * @return string
     */
    public function sendForm($url, $formName, $formData, $getData = [], $formSelector = null)
    {
        $this->processParamWrappers($formData);

        $crawler = $this->sendGet($url, $getData);

        $formSelector = $formSelector === null ? '[name="'.$formName.'"]' : $formSelector;
        /** @var Crawler $form */
        $formFilter = $crawler->filter($formSelector);
        if (!$formFilter) {
            self::fail('Form not found: ' . $formSelector);
        }

        $form = $formFilter->form([$formName => $formData]);

        $this->client()->submit($form);

        return $this->getResponse()->getContent();
    }

    /**
     * @return null|Response
     */
    public function getResponse()
    {
        return $this->client()->getResponse();
    }
    /**
     * @return null|Response
     */
    public function getJsonResponse()
    {
        return json_decode($this->getResponse()->getContent(), true);
    }

    /**
     * @param array $params
     */
    protected function processParamWrappers(array &$params)
    {
        array_walk_recursive($params, function (&$value) {
            $this->processParamWrapper($value);
        });
    }

    /**
     * @param $value
     */
    protected function processParamWrapper(&$value)
    {
        if (is_object($value) && $value instanceof ParamWrapper) {
            $entity = $this->getObjectOf($value->getClass(), $value->getCriteria());

            $value = PropertyAccess::createPropertyAccessorBuilder()
                ->enableExceptionOnInvalidIndex()
                ->getPropertyAccessor()
                ->getValue($entity, $value->getPath())
            ;
        }
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param array $files
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    private function request($method, $url, $data, $headers, $files = [])
    {
        $_headers = [];

        foreach ($headers as $key => $value) {
            $_headers['HTTP_' . $key] = $value;
        }

        return $this->client()->request($method, $url, $data, $files, $_headers);
    }

    public function enableMessageLogger()
    {
        $swiftmailer = $this->container()->get('swiftmailer.mailer');

        $swiftmailer->registerPlugin($this->messageLogger);

        $this->messageLogger->clear();
    }

    /**
     * @return Swift_Plugins_MessageLogger
     */
    public function messageLogger()
    {
        return $this->messageLogger;
    }

    /**
     * @param int $i
     * @return \Swift_Mime_Message
     */
    public function getMessage($i = 0)
    {
        if ($i >= $this->messageLogger()->countMessages()) {
            return null;
        }

        return $this->messageLogger()->getMessages()[$i];
    }
}
