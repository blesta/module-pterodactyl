<?php
namespace Blesta\PterodactylSDK;

/**
 * Pterodactyl API Response
 *
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PterodactylResponse
{
    /**
     * @var string The status code of this response
     */
    private $status;

    /**
     * @var string The raw data from this response
     */
    private $raw;

    /**
     * @var stdClass The formatted data from this response
     */
    private $response;

    /**
     * @var array A list of errors from the response data
     */
    private $errors;

    /**
     * @var array A list of headers from this response
     */
    private $headers;

    /**
     * PterodactylResponse constructor.
     *
     * @param array $apiResponse
     */
    public function __construct(array $apiResponse)
    {
        $this->raw = $apiResponse['content'];
        $this->response = json_decode($apiResponse['content']);
        $this->headers = $apiResponse['headers'];

        // Get the http status code from the header
        $this->status = '400';
        if (isset($this->headers[0])) {
            $status_parts = explode(' ', $this->headers[0]);
            if (isset($status_parts[1])) {
                $this->status = $status_parts[1];
            }
        }

        // Set any errors
        $this->errors =  [];
        if (isset($this->response->errors)) {
            foreach ($this->response->errors as $error) {
                $this->errors[] = $error->detail;
            }
        }
    }

    /**
     * Get the status of this response
     *
     * @return string The status of this response
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Get the raw data from this response
     *
     * @return string The raw data from this response
     */
    public function raw()
    {
        return $this->raw;
    }

    /**
     * Get the formatted data from this response
     *
     * @return string The formatted data from this response
     */
    public function response()
    {
        return $this->response;
    }

    /**
     * Get any errors from this response
     *
     * @return array The errors from this response
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Get the headers returned with this response
     *
     * @return array The headers returned with this response
     */
    public function headers()
    {
        return $this->headers;
    }
}
