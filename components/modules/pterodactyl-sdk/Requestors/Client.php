<?php
namespace Blesta\PterodactylSDK\Requestors;

class Client extends Requestor
{
    /**
     * Fetches a list of servers from Pterodactyl
     *
     * @return PterodactylResponse
     */
    public function getServers()
    {
        return $this->apiRequest('client');
    }

    /**
     * Fetches a server from Pterodactyl
     *
     * @param int $server_id The ID of the server to fetch
     * @return PterodactylResponse
     */
    public function getServer($server_id)
    {
        return $this->apiRequest('client/servers/' . $server_id);
    }

    /**
     * Fetches utinilzation stats for a server from Pterodactyl
     *
     * @param int $server_id The ID of the server to fetch
     * @return PterodactylResponse
     */
    public function getServerUtilization($server_id)
    {
        return $this->apiRequest('client/servers/' . $server_id . '/utilization');
    }

    /**
     * Sends a console command to the given server from Pterodactyl
     *
     * @param int $server_id The ID of the server to which a command is being sent
     * @param string $command The command being sent
     * @return PterodactylResponse
     */
    public function serverConsoleCommand($server_id, $command)
    {
        return $this->apiRequest('client/servers/' . $server_id . '/command', ['command' => $command], 'POST');
    }

    /**
     * Sends a power signal to the given server from Pterodactyl
     *
     * @param int $server_id The ID of the server to which a power signal is being sent
     * @param string $signal The power signal to send ('start', 'stop', 'restart', or 'kill')
     * @return PterodactylResponse
     */
    public function serverPowerSignal($server_id, $signal)
    {
        return $this->apiRequest('client/servers/' . $server_id . '/power', ['signal' => $signal], 'POST');
    }
}