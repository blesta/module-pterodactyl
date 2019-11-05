<?php
namespace Blesta\PterodactylSDK;

/**
 * Pterodactyl API Rquestor
 *
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Requestor
{
    /**
     * @var string The Pterodactyl API key
     */
    private $apiKey;
    /**
     * @var string The Pterodactyl API URL
     */
    private $apiUrl;

    /**
     * Initializes the request parameter
     *
     * @param string $apiKey The API key
     * @param string $apiUrl The API URL
     */
    public function __construct($apiKey, $apiUrl)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }

    /**
     * Send an API request to Pterodactyl
     *
     * @param string $route The path to the API method
     * @param array $body The data to be sent
     * @param string $method Data transfer method (POST, GET, PUT, DELETE)
     * @return PterodactylResponse
     */
    protected function apiRequest($route, array $body = [], $method = 'GET')
    {
        $url = 'https://' . $this->apiUrl . '/' . $route;
        $curl = curl_init();

        switch (strtoupper($method)) {
            case 'DELETE':
                // Set data using get parameters
            case 'GET':
                $url .= empty($body) ? '' : '?' . http_build_query($body);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                // Use the default behavior to set data fields
            default:
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
                break;
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);

        $headers = [];
        $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: Application/vnd.pterodactyl.v1+json';
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $this->lastRequest = ['content' => $body, 'headers' => $headers];
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            $error = [
                'errors' => [
                    (object)['detail' => 'An internal error occurred, or the server did not respond to the request.']
                ],
                'status' => 500
            ];

            return new PterodactylResponse(['content' => json_encode($error), 'headers' => []]);
        }
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        // Return request response
        return new PterodactylResponse(
            ['content' => substr($result, $header_size), 'headers' => explode("\n", substr($result, 0, $header_size))]
        );
    }
}
