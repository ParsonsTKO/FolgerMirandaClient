<?php
/**
 * File containing the ContentService class.
 *
 * (c) http://parsonstko.com/
 * (c) Developer jdiaz
 */

namespace DAPClientBundle\Services;

use Symfony\Component\DependencyInjection\Container;
use Psr\Log\LoggerInterface;

class ContentService
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    protected $dapClientLogger;

    public function __construct(Container $container, LoggerInterface $dapClientLogger = null)
    {
        $this->container = $container;
        $this->dapClientLogger = $dapClientLogger;
    }

    /**
     * Get metadata content.
     *
     * @param array $params
     *
     * return
     */
    public function getMetadata($params = array())
    {
        $headSettings = $this->container->getParameter('dap_client.head');
        $metadata = array();

        foreach ($headSettings['metadata'] as $metadataIdentifier => $metadataValue) {
            $metadata[$metadataIdentifier] = $metadataValue;
        }

        return $metadata;
    }
}
