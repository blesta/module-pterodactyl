<?php
namespace Blesta\PterodactylSDK\Requestors;

include_once dirname(__DIR__) . '/Requestor.php';

class Users extends \Blesta\PterodactylSDK\Requestor
{
    /**
     * Fetches a list of users from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getAll()
    {
        return $this->apiRequest('application/users');
    }

    /**
     * Fetches a user from Pterodactyl
     *
     * @param int $user_id The ID of the user to fetch
     * @return PterodactylResponse
     */
    public function get($user_id)
    {
        return $this->apiRequest('application/users/' . $user_id);
    }

    /**
     * Fetches a user from Pterodactyl by external ID
     *
     * @param int $external_id The external ID of the user to fetch
     * @return PterodactylResponse
     */
    public function getByExternalID($external_id)
    {
        return $this->apiRequest('application/users/external/' . $external_id);
    }

    /**
     * Adds a user in Pterodactyl
     *
     * @param array $params A list of request parameters including:
     *
     *  - username The username for the accoount
     *  - email The email address for the account
     *  - first_name The user's first name
     *  - last_name The user's last name
     *  - password A plain text input of the desired password
     * @return PterodactylResponse
     */
    public function add(array $params)
    {
        return $this->apiRequest('application/users', $params, 'POST');
    }

    /**
     * Edits a user in Pterodactyl
     *
     * @param int $user_id The ID of the user to edit
     * @param array $params A list of request parameters including:
     *
     *  - username The username for the accoount
     *  - email The email address for the account
     *  - first_name The user's first name
     *  - last_name The user's last name
     *  - password A plain text input of the desired password
     * @return PterodactylResponse
     */
    public function edit($user_id, array $params)
    {
        return $this->apiRequest('application/users/' . $user_id, $params, 'PATCH');
    }

    /**
     * Deletes a user in Pterodactyl
     *
     * @param int $user_id The ID of the user to delete
     * @return PterodactylResponse
     */
    public function delete($user_id)
    {
        return $this->apiRequest('application/users/' . $user_id, [], 'DELETE');
    }
}