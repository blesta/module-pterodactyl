<?php
namespace Blesta\PterodactylSDK;

include_once dirname(__DIR__) . '/Requestor.php';

class Locations extends \Blesta\PterodactylSDK\Requestor
{
    /**
     * Fetches a list of locations from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getAll()
    {
        return $this->apiRequest('application/locations');
    }

    /**
     * Fetches a location from Pterodactyl
     *
     * @param int $location_id The ID of the location to fetch
     * @return PterodactylResponse
     */
    public function get($location_id)
    {
        return $this->apiRequest('application/locations/' . $location_id);
    }

    /**
     * Adds a location in Pterodactyl
     *
     * @param array $params A list of request parameters including:
     *
     *  - shortcode The shortcode of the location
     *  - description A description of the location
     * @return PterodactylResponse
     */
    public function add(array $params)
    {
        return $this->apiRequest('application/locations', $params, 'POST');
    }

    /**
     * Edits a location in Pterodactyl
     *
     * @param int $location_id The ID of the location to edit
     * @param array $params A list of request parameters including:
     *
     *  - shortcode The shortcode of the location
     *  - description A description of the location
     * @return PterodactylResponse
     */
    public function edit($location_id, array $params)
    {
        return $this->apiRequest('application/locations/' . $location_id, $params, 'PATCH');
    }

    /**
     * Deletes a location in Pterodactyl
     *
     * @param int $location_id The ID of the location to delete
     * @return PterodactylResponse
     */
    public function delete($location_id)
    {
        return $this->apiRequest('application/locations/' . $location_id, [], 'DELETE');
    }
}