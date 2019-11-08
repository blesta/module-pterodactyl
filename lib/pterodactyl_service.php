<?php
/**
 * Pterodactyl Service actions
 *
 * @package blesta
 * @subpackage blesta.components.modules.Pterodactyl.lib
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PterodactylService
{
    /**
     * Initialize
     */
    public function __construct()
    {
        // Load required components
        Loader::loadComponents($this, ['Input']);
    }

    /**
     * Retrieves a list of Input errors, if any
     */
    public function errors()
    {
        return $this->Input->errors();
    }

    /**
     * Gets a list of parameters to submit to Pterodactyl for user creation
     *
     * @param array $vars A list of input data
     * @return array A list containing the parameters
     */
    public function addUserParameters(array $vars)
    {
        Loader::loadModels($this, ['Clients']);
        $client = $this->Clients->get($vars['client_id']);
        return [
            'username' => $client->email,
            'email' => $client->email,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'external_id' => $client->id,
        ];
    }

    /**
     * Gets a list of parameters to submit to Pterodactyl for server creation
     *
     * @param array $vars An array of post fields
     * @param stdClass $package The package to pull server info from
     * @param stdClass $pterodactyl_user An object representing the Pterodacytl user
     * @param stdClass $pterodactyl_egg An object representing the Pterodacytl egg
     * @return array The list of parameters
     */
    public function addServerParameters(array $vars, $package, $pterodactyl_user, $pterodactyl_egg)
    {
        // Get environment data from the egg
        $environment = [];
        foreach ($pterodactyl_egg->attributes->relationships->variables->data as $env_variable) {
            // Check service options for the given variable
            $variable_name = $env_variable->attributes->env_variable;
            if (isset($vars[$variable_name])) {
                $environment[$variable_name] = $vars[$variable_name];
            } else {
                // Default to the default value
                $environment[$variable_name] = $env_variable->attributes->default_value;
            }
        }

        // Gather server data
        return [
            'description' => '', // TODO create service description
            'name' => $vars['server_name'],
            'user' => $pterodactyl_user->attributes->id,
            'nest' => $package->meta->nest_id,
            'egg' => $package->meta->egg_id,
            'pack' => $package->meta->pack_id,
            'docker_image' => !empty($package->meta->image)
                ? $package->meta->image
                : $pterodactyl_egg->attributes->docker_image,
            'startup' => !empty($package->meta->startup)
                ? $package->meta->startup
                : $pterodactyl_egg->attributes->startup,
            'limits' => [
                'memory' => $package->meta->memory,
                'swap' => $package->meta->swap,
                'io' => $package->meta->io,
                'cpu' => $package->meta->cpu,
                'disk' => $package->meta->disk,
            ],
            'feature_limits' => [
                'databases' => $package->meta->databases ? $package->meta->databases : null,
            ],
            'deploy' => [
                'locations' => [$package->meta->location_id],
                'dedicated_ip' => $package->meta->dedicated_ip,
                'port_range' => explode(',', $package->meta->port_range),
            ],
            'environment' => $environment,
            'start_on_completion' => true,
        ];
    }

    /**
     * Gets a list of parameters to submit to Pterodactyl for server detail edit
     *
     * @param array $vars An array of post fields
     * @param stdClass $pterodactyl_user An object representing the Pterodacytl user
     * @return array The list of parameters
     */
    public function editServerParameters(array $vars, $pterodactyl_user)
    {
        // Gather server data
        return [
            'name' => $vars['server_name'],
            'user' => $pterodactyl_user->attributes->id,
            'description' => '', // TODO create service description
        ];
    }

    /**
     * Gets a list of parameters to submit to Pterodactyl for server build edit
     *
     * @param stdClass $package The package to pull server info from
     * @return array The list of parameters
     */
    public function editServerBuildParameters($package)
    {
        // Gather server data
        return [
            'limits' => [
                'memory' => $package->meta->memory,
                'swap' => $package->meta->swap,
                'io' => $package->meta->io,
                'cpu' => $package->meta->cpu,
                'disk' => $package->meta->disk,
            ],
            'feature_limits' => [
                'databases' => $package->meta->databases ? $package->meta->databases : null,
            ]
        ];
    }

    /**
     * Gets a list of parameters to submit to Pterodactyl for server startup edit
     *
     * @param array $vars An array of post fields
     * @param stdClass $package The package to pull server info from
     * @param stdClass $pterodactyl_egg An object representing the Pterodacytl egg
     * @return array The list of parameters
     */
    public function editServerStartupParameters(array $vars, $package, $pterodactyl_egg)
    {
        // Get environment data from the egg
        $environment = [];
        foreach ($pterodactyl_egg->attributes->relationships->variables->data as $env_variable) {
            // Check service options for the given variable
            $variable_name = $env_variable->attributes->env_variable;
            if (isset($vars[$variable_name])) {
                $environment[$variable_name] = $vars[$variable_name];
            } else {
                // Default to the default value
                $environment[$variable_name] = $env_variable->attributes->default_value;
            }
        }

        // Gather server data
        return [
            'egg' => $package->meta->egg_id,
            'pack' => $package->meta->pack_id,
            'image' => !empty($package->meta->image)
                ? $package->meta->image
                : $pterodactyl_egg->attributes->docker_image,
            'startup' => !empty($package->meta->startup)
                ? $package->meta->startup
                : $pterodactyl_egg->attributes->startup,
            'environment' => $environment,
            'skip_scripts' => false,
        ];
    }

    /**
     * Returns all fields used when adding/editing a service, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param stdClass $pterodactyl_egg An object representing the Pterodacytl egg
     * @param stdClass $vars A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields
     *  to render as well as any additional HTML markup to include
     */
    public function getFields($pterodactyl_egg, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Set the server name
        $serverName = $fields->label(
            Language::_('PterodactylService.service_fields.server_name', true),
            'server_name'
        );
        $serverName->attach(
            $fields->fieldText(
                'server_name',
                $this->Html->ifSet($vars->server_name),
                ['id' => 'server_name']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylService.service_fields.tooltip.server_name', true));
        $serverName->attach($tooltip);
        $fields->setField($serverName);

        // Get service fields
        foreach ($pterodactyl_egg->attributes->relationships->variables->data as $env_variable) {
            // Create domain label
            $label = strpos($env_variable->attributes->rules, 'required') === 0
                ? $env_variable->attributes->name
                : Language::_('PterodactylService.service_fields.optional', true, $env_variable->attributes->name);
            $field = $fields->label($label, $env_variable->attributes->env_variable);
            // Create domain field and attach to domain label
            $field->attach(
                $fields->fieldText(
                    $env_variable->attributes->env_variable,
                    $this->Html->ifSet(
                        $vars->{$env_variable->attributes->env_variable},
                        $env_variable->attributes->default_value
                    ),
                    ['id' => $env_variable->attributes->env_variable]
                )
            );
            // Add tooltip
            $tooltip = $fields->tooltip($env_variable->attributes->description);
            $field->attach($tooltip);
            // Set the label as a field
            $fields->setField($field);
        }

        return $fields;
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param stdClass $package A stdClass object representing the selected package
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    public function getServiceRules(array $vars = null, $package = null, $edit = false)
    {
        ##
        # TODO Add service rules base on the egg variable rules. The fact that no rules exist will cause the service to
        # pass steps of approval that it should not (e.g. an admin can create a pending service with invalid credential)
        ##
        // Set rules
        $rules = [];

        // Set the values that may be empty
        $empty_values = [];
        if ($edit) {
        }

        // Remove rules on empty fields
        foreach ($empty_values as $value) {
            if (empty($vars[$value])) {
                unset($rules[$value]);
            }
        }

        return $rules;
    }
}
