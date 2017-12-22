<?php
/**
 * File containing the SearchService class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer jdiaz
 */

namespace DAPClientBundle\Services;

use Symfony\Component\DependencyInjection\Container;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class SearchService
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    protected $dapClientLogger;

    /**
     * @var UUIDv4Pattern
     */
    private $UUIDv4Pattern;

    /**
     * @var Container
     */
    private $client;

    /**
     * @var array
     */
    public $searchSettings;

    public function __construct(Container $container, LoggerInterface $dapClientLogger = null)
    {
        $this->container = $container;
        $this->dapClientLogger = $dapClientLogger;
        $this->UUIDv4Pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
    }

    /**
     * Sets search settings.
     *
     * @param array $searchSettings the children settings list
     *
     * set searchSettings property
     */
    public function setSearchSettings(array $searchSettings = null)
    {
        $this->searchSettings = $searchSettings;
    }

    /**
     * Get content by HTTP Client.
     *
     * @param array $viewSettings
     *
     * return
     */
    public function getContent($type, $viewSettings)
    {
        try {
            $client = new Client();
            $method = $viewSettings['method'];
            $endpoint = $viewSettings['endpoint'];
            $options = [
            		$viewSettings[$type][$method.'_option'] => [
            		$viewSettings[$type][$method.'_option_param'] => $viewSettings[$type][$method.'_option_value'],
                ],
            ];

            $response = $client->request($method, $endpoint, $options);

            return json_decode($response->getBody());
        } catch (RequestException $e) {
        	//$this->get('dap_client.logger')->error($e->getMessage());
        	throw $this->createNotFoundException('Validation could not be found. Error: '.$e->getMessage());
        }
    }

    /**
     * Validate a UUID val.
     *
     * @param string $value
     *
     * return
     */
    public function validateUUID($value)
    {
        try {
            return preg_match($this->UUIDv4Pattern, $value);
        } catch (\Exception $e) {
            //$this->get('dap_client.logger')->error($e->getMessage());
            throw $this->createNotFoundException('Validation could not be found. Error: '.$e->getMessage());
        }
    }
}
