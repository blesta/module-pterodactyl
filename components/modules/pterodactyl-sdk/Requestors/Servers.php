<?php
namespace Blesta\PterodactylSDK\Requestors;

class Servers extends Requestor
{
    /**
     * Fetches a list of servers from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getAll()
    {
        return $this->apiRequest('application/servers');
    }

    /**
     * Fetches a server from Pterodactyl
     *
     * @param int $server_id The ID of the server to fetch
     * @return PterodactylResponse
     */
    public function get($server_id)
    {
        return $this->apiRequest('application/servers/' . $server_id);
    }

    /**
     * Fetches a server from Pterodactyl by external ID
     *
     * @param int $external_id The external ID of the server to fetch
     * @return PterodactylResponse
     */
    public function getByExternalID($external_id)
    {
        return $this->apiRequest('application/servers/external/' . $external_id);
    }

    /**
     * Adds a server in Pterodactyl
     *
     * @param array $params A list of request parameters including:
     *
     *  Please note that every environment variable from the egg must be set.
     * @return PterodactylResponse
     */
    public function add(array $params)
    {
        return $this->apiRequest('application/servers', $params, 'POST');
    }

    /**
     * Edits the details for a server in Pterodactyl
     *
     * @param int $server_id The ID of the server to edit
     * @param array $params A list of request parameters including:
     * @return PterodactylResponse
     */
    public function editDetails($server_id, array $params)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/details', $params, 'PATCH');
    }

    /**
     * Edits the build for a server in Pterodactyl
     *
     * @param int $server_id The ID of the server to edit
     * @param array $params A list of request parameters including:
     * @return PterodactylResponse
     */
    public function editBuild($server_id, array $params)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/details', $params, 'PATCH');
    }

    /**
     * Edits the startup parameters for a server in Pterodactyl
     *
     * @param int $server_id The ID of the server to edit
     * @param array $params A list of request parameters including:
     * @return PterodactylResponse
     */
    public function editStartup($server_id, array $params)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/details', $params, 'PATCH');
    }

    /**
     * Suspends a server in Pterodactyl
     *
     * @param int $server_id The ID of the server to suspend
     * @return PterodactylResponse
     */
    public function suspend($server_id)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/suspend', [], 'POST');
    }

    /**
     * Unsuspends a server in Pterodactyl
     *
     * @param int $server_id The ID of the server to unsuspend
     * @return PterodactylResponse
     */
    public function unsuspend($server_id)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/unsuspend', [], 'POST');
    }

    /**
     * Reinstall a server in Pterodactyl
     *
     * @param int $server_id The ID of the server to reinstall
     * @return PterodactylResponse
     */
    public function reinstall($server_id)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/reinstall', [], 'POST');
    }

    /**
     * Rebuild a server in Pterodactyl
     *
     * @param int $server_id The ID of the server to rebuild
     * @return PterodactylResponse
     */
    public function rebuild($server_id)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/rebuild', [], 'POST');
    }

    /**
     * Deletes a server in Pterodactyl
     *
     * @param int $server_id The ID of the server to delete
     * @return PterodactylResponse
     */
    public function delete($server_id)
    {
        return $this->apiRequest('application/servers/' . $server_id, [], 'DELETE');
    }

    /**
     * Forcefully deletes a server in Pterodactyl
     *
     * @param int $server_id The ID of the server to delete
     * @return PterodactylResponse
     */
    public function forceDelete($server_id)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/force', [], 'DELETE');
    }

    /**
     * Fetches all databases from the given server in Pterodactyl
     *
     * @param int $server_id The ID of the server from which to fetch databases
     * @return PterodactylResponse
     */
    public function databasesGetAll($server_id)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/databases', [], 'GET');
    }

    /**
     * Fetches a database from the given server in Pterodactyl
     *
     * @param int $server_id The ID of the server from which to fetch the database
     * @param int $database_id The ID of the database to fetch
     * @return PterodactylResponse
     */
    public function databasesGet($server_id, $database_id)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/databases/' . $database_id, [], 'GET');
    }

    /**
     * Adds database for the given server in Pterodactyl
     *
     * @param int $server_id The ID of the server for which to create the database
     * @param array $params A list of request parameters including:
     *
     *  - shortcode The shortcode of the server
     *  - description A description of the server
     * @return PterodactylResponse
     */
    public function databasesAdd($server_id, array $params)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/databases', $params, 'POST');
    }

    /**
     * Resets the password for a database in Pterodactyl
     *
     * @param int $server_id The ID of the server the database is on
     * @param int $database_id The ID of the database for which to reset the password
     * @return PterodactylResponse
     */
    public function databasesResetPassword($server_id, $database_id)
    {
        return $this->apiRequest(
            'application/servers/' . $server_id . '/databases/' . $database_id . 'reset-password',
            [],
            'POST'
        );
    }

    /**
     * Deletes a database in Pterodactyl
     *
     * @param int $server_id The ID of the server the database is on
     * @param int $database_id The ID of the database to delete
     * @return PterodactylResponse
     */
    public function databasesDelete($server_id, $database_id)
    {
        return $this->apiRequest('application/servers/' . $server_id . '/databases/' . $database_id, [], 'DELETE');
    }
}