<?php
namespace Blesta\PterodactylSDK\Requestors;

include_once dirname(__DIR__) . '/Requestor.php';

class Nests extends \Blesta\PterodactylSDK\Requestor
{
    /**
     * Fetches a list of nests from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getAll()
    {
        return $this->apiRequest('application/nests');
    }

    /**
     * Fetches a nest from Pterodactyl
     *
     * @param int $nest_id The ID of the nest to fetch
     * @return PterodactylResponse
     */
    public function get($nest_id)
    {
        return $this->apiRequest('application/nests/' . $nest_id);
    }

    /**
     * Fetches a list of eggs for the given nest nest from Pterodactyl
     *
     * @param int $nest_id The ID of the nest to fetch eggs for
     * @return PterodactylResponse
     */
    public function eggsGetAll($nest_id)
    {
        return $this->apiRequest('application/nests/' . $nest_id . '/eggs');
    }

    /**
     * Fetches an egg from Pterodactyl
     *
     * @param int $nest_id The ID of the nest to which the egg belongs
     * @param int $egg_id The ID of the egg to fetch
     * @return PterodactylResponse
     */
    public function eggsGet($nest_id, $egg_id)
    {
        return $this->apiRequest('application/nests/' . $nest_id . '/eggs/' . $egg_id);
    }
}