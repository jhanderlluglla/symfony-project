<?php

namespace UserBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Bridge\Monolog\Logger;

use OnlineConvert\Configuration;
use OnlineConvert\Client\OnlineConvertClient;
use OnlineConvert\Api;

/**
 * Class OnlineConvertService
 *
 * @package UserBundle\Services
 */
class OnlineConvertService
{

    const STATUS_CODE_FAIL = 'fail';
    const STATUS_CODE_COMPLETED = 'completed';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Api
     */
    private $syncApi;

    /**
     * @var string
     */
    private $tempDir;

    /**
     * @var string
     */
    private $docsLocalPath;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Logger
     */
    protected $monolog;

    /**
     * OnlineConvertService constructor.
     *
     * @param RequestStack $requestStack
     * @param string $onlineconvertApiKey
     * @throws \Exception
     */
    public function __construct(RequestStack $requestStack, $onlineconvertApiKey, $docsLocalPath, $monolog)
    {

        $this->request = $requestStack->getCurrentRequest();
        $this->docsLocalPath = $docsLocalPath;
        $this->monolog = $monolog;

        $this->tempDir = sys_get_temp_dir();

        $config = new Configuration();
        if(is_null($onlineconvertApiKey)){
            throw new \Exception("Define online convert api key in parameters.yml");
        }
        $config->setApiKey('main', $onlineconvertApiKey);
        $config->downloadFolder = $this->tempDir;

        $client = new OnlineConvertClient($config, 'main');
        $this->syncApi = new \OnlineConvert\Api($client);

        $this->fs = new Filesystem();
    }

    /**
     * @param array $syncJob
     *
     * @return array
     */
    public function postFullJob($syncJob)
    {
        try {
            $outputEndpoint = $this->syncApi->getOutputEndpoint();
            $this->monolog->info('Got output point');

            $result = $this->syncApi->postFullJob($syncJob)->getJobCreated();
            $this->monolog->info('Posted full job');

            $outputEndpoint->downloadOutputs($result);
            $this->monolog->info(sprintf('Download outputs: %s', json_encode($result)));
        } catch (\Exception $e) {
            $this->monolog->info(sprintf('Post full job exceptopn: %s', $e->getMessage()));
            $result = [
                'status' => [
                    'code' => 'fail'
                ]
            ];
        }

        return $result;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    public function getGlobalFilePath($fileName)
    {
        $this->monolog->info(sprintf('Got global file path %s', $this->request->getScheme() . '://' . $this->request->getHost() . $this->docsLocalPath . DIRECTORY_SEPARATOR . $fileName));

//        return 'http://ereferer.requestumdemo.com/uploads/docs/' . $fileName;
        return $this->request->getScheme() . '://' . $this->request->getHost() . $this->docsLocalPath . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * @param array $result
     *
     * @return string
     */
    public function getLocalOutputedFilePath($result)
    {
        return $this->getLocalOutputedDirPath($result) . DIRECTORY_SEPARATOR . $result['input']['0']['filename'];
    }

    /**
     * @param array $result
     *
     * @return string
     */
    public function getLocalOutputedDirPath($result)
    {
        return $this->tempDir . DIRECTORY_SEPARATOR . $result['output']['0']['id'];
    }

    /**
     * @param array $result
     *
     * @return string
     */
    public function getZipFileName($result)
    {
        $info = pathinfo($result['input']['0']['filename']);

        return $info['filename'] . '.zip';
    }

    /**
     * @param array $result
     *
     * @return string
     */
    public function getHtmlFileName($result)
    {
        $info = pathinfo($result['input']['0']['filename']);

        return $info['filename'] . '.html';
    }

    /**
     * @param array $result
     *
     * @return string
     */
    public function getLocalOutputedZipFilePath($result)
    {
        return $this->getLocalOutputedDirPath($result) . DIRECTORY_SEPARATOR . $this->getZipFileName($result);
    }

    /**
     * @param array $result
     *
     * @return string
     */
    public function getLocalOutputedHtmlFilePath($result)
    {
        return $this->getLocalOutputedDirPath($result) . DIRECTORY_SEPARATOR . $this->getHtmlFileName($result);
    }

    /**
     * @param array $result
     *
     * @return bool
     */
    public function isLocalOutputedFileZip($result)
    {
        return $this->fs->exists($this->getLocalOutputedZipFilePath($result));
    }

    /**
     * @param array $result
     *
     * @return bool
     */
    public function isLocalOutputedFileHtml($result)
    {
        return $this->fs->exists($this->getLocalOutputedHtmlFilePath($result));
    }
}