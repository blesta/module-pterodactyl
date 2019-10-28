<?php
namespace Blesta\PterodactylSDK;

include 'PterodactylResponse.php';
include 'Requestors/Client.php';
include 'Requestors/Users.php';
include 'Requestors/Nodes.php';
include 'Requestors/Locations.php';
include 'Requestors/Servers.php';
include 'Requestors/Nests.php';

/**
 * Pterodactyl API
 *
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PterodactylApi
{
    /**
     * @var string The API URL
     */
    private $apiUrl;
    /**
     * @var string The Pterodactyl API key
     */
    private $apiKey;

    /**
     * Initializes the request parameter
     *
     * @param string $apiKey The API key
     */
    public function __construct($apiKey, $baseUrl)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = trim($baseUrl, '/') . '/api/';
    }

    function __get($className)
    {
        $r = new \ReflectionClass('\\Blesta\\PterodactylSDK\\Requestors\\' . $className);
        $this->{$className} = $r->newInstanceArgs([$this->apiKey, $this->apiUrl]);
        return $this->{$className};
    }
}
