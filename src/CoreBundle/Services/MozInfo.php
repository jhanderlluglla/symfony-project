<?php

namespace CoreBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Class MozInfo
 *
 * @package CoreBundle\Services
 */
class MozInfo
{
    const SERVICE_HOST = 'https://lsapi.seomoz.com';

    const SERVICE_URI = '/linkscape/url-metrics/moz.com';

    /**
     * @var string
     */
    protected $accessID;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var int
     *
     */
    protected $expires;

    /**
     * Bit flags for pageAuthority
     * Learn more here: https://moz.com/help/guides/moz-api/mozscape/api-reference/url-metrics
     *
     * @var int
     */
    protected $pageAuthority = 34359738368;

    /**
     * Bit flags for domainAuthority
     *
     * @var int
     */
    protected $domainAuthority = 68719476736;

    /**
     * base64-encode of signature
     *
     * @var string
     */
    protected $signature;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $domainInfo;

    /**
     * MozInfo constructor.
     *
     * @param string $accessID
     * @param string $secretKey
     */
    public function __construct($accessID, $secretKey)
    {
        $this->accessID = $accessID;
        $this->secretKey = $secretKey;

        $this->expires = time() + 400;

        $stringToSign = $accessID."\n".$this->expires;

        $binarySignature = hash_hmac('sha1', $stringToSign, $secretKey, true);

        $this->signature = base64_encode($binarySignature);

        $this->client = new Client([
            'base_uri' => self::SERVICE_HOST,
            'timeout' => 50,
        ]);
    }

    /**
     * @param $domain
     * @return mixed
     */
    public function getPageAuthority($domain)
    {
        if (!isset($this->domainInfo[$domain])) {
            $this->domainInfo[$domain] = $this->retrieveData($domain);
        }

        return isset($this->domainInfo[$domain]['upa']) ? $this->domainInfo[$domain]['upa'] : null;
    }

    /**
     * @param string $domain
     * @return mixed
     */
    public function getDomainAuthority($domain)
    {
        if (!isset($this->domainInfo[$domain])) {
            $this->domainInfo[$domain] = $this->retrieveData($domain);
        }

        return isset($this->domainInfo[$domain]['pda']) ? $this->domainInfo[$domain]['pda'] : null;
    }

    /**
     * @param array $domains
     *
     * @return array
     */
    public function batchRetrieveData($domains)
    {
        $domainsArrays = array_chunk($domains, 2, true);

        $result = [];

        foreach ($domainsArrays as $domains) {
            $encodedDomains = json_encode($domains);

            $parameters = [
                'Cols' => $this->domainAuthority + $this->pageAuthority,
                'AccessID' => $this->accessID,
                'Expires' => $this->expires,
                'Signature' => $this->signature,
            ];

            try {
                $response = $this->client->request(Request::METHOD_POST, self::SERVICE_URI, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'query' => $parameters,
                    'body' => $encodedDomains
                ]);
            } catch (BadResponseException $e) {
                $response = $e->getResponse();
                // Monolog?
            }
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);

            // From docs : POST requests in excess of your access limits may return an HTTP 400 response.
            if ($data && count($domains) == count($data)) {
                $data = array_combine($domains, $data);
                $result = array_merge($result, $data);
            }
        }

        $this->domainInfo = $result;

        return $result;
    }

    /**
     * @param $domain
     *
     * @return array
     */
    protected function retrieveData($domain)
    {
        $parameters = [
            'Cols' => $this->domainAuthority + $this->pageAuthority,
            'AccessID' => $this->accessID,
            'Expires' => $this->expires,
            'Signature' => $this->signature,
        ];

        try {
            $response = $this->client->request(Request::METHOD_GET, self::SERVICE_URI.urlencode($domain), ['query' => $parameters]);
        } catch (BadResponseException $e) {
            $response = $e->getResponse();
        }

        $content = $response->getBody()->getContents();

        $data = json_decode($content, true);

        return $data;
    }

    public function clearData()
    {
        $this->domainInfo = [];
    }
}
