<?php
namespace Blesta\PterodactylSDK;

/**
 * Pterodactyl API
 *
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PterodactylApi
{
    use PterodactyleResponse,
        Objects\Client,
        Objects\Users,
        Objects\Nodes,
        Objects\Locations,
        Objects\Servers,
        Objects\Nests;
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

    function __get($class_name)
    {
        if (class_exists($class_name)) {
            $this->{$class_name} = new $class_name($this->apiKey, $this->apiUrl);
            return $this->{$class_name};
        } else {
            // Throw exception of unfound class name
        }
    }
}
