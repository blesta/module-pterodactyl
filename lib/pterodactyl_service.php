<?php
/**
 * Pterodactyl Service helper
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
            'username' => 'bl_' . $client->id,
            'password' => $this->generatePassword(),
            'email' => $client->email,
            'first_name' => $client->first_name,
            'last_name' => $client->last_name,
            'external_id' => 'bl-' . $client->id,
        ];
    }

    /**
     * Generates a password.
     *
     * @param int $min_length The minimum character length for the password (5 or larger)
     * @param int $max_length The maximum character length for the password (14 or fewer)
     * @return string The generated password
     */
    private function generatePassword($min_length = 10, $max_length = 14)
    {
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
        $pool_size = strlen($pool);
        $length = mt_rand(max($min_length, 5), min($max_length, 14));
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size - 1), 1);
        }

        return $password;
    }

    /**
     * Gets a list of parameters to submit to Pterodactyl for server creation
     *
     * @param array $vars An array of post fields
     * @param stdClass $package The package to pull server info from
     * @param stdClass $pterodactylUser An object representing the Pterodacytl user
     * @param stdClass $pterodactylEgg An object representing the Pterodacytl egg
     * @return array The list of parameters
     */
    public function addServerParameters(array $vars, $package, $pterodactylUser, $pterodactylEgg)
    {
        // Gather server data
        return [
            'external_id' => $vars['client_id'] . '-' . uniqid(),
            'name' => $vars['server_name'],
            'description' => $vars['server_description'],
            'user' => $pterodactylUser->attributes->id,
            'nest' => $package->meta->nest_id,
            'egg' => $package->meta->egg_id,
            'pack' => $package->meta->pack_id,
            'docker_image' => !empty($package->meta->image)
                ? $package->meta->image
                : $pterodactylEgg->attributes->docker_image,
            'startup' => !empty($package->meta->startup)
                ? $package->meta->startup
                : $pterodactylEgg->attributes->startup,
            'limits' => [
                'memory' => $package->meta->memory,
                'swap' => $package->meta->swap,
                'io' => $package->meta->io,
                'cpu' => $package->meta->cpu,
                'disk' => $package->meta->disk,
            ],
            'feature_limits' => [
                'databases' => !empty($package->meta->databases) ? $package->meta->databases : null,
                'allocations' => !empty($package->meta->allocations) ? $package->meta->allocations : null,
                'backups' => !empty($package->meta->backups) ? $package->meta->backups : null,
            ],
            'deploy' => [
                'locations' => [$package->meta->location_id],
                'dedicated_ip' => !empty($package->meta->dedicated_ip) ? $package->meta->dedicated_ip : false,
                'port_range' => explode(',', $package->meta->port_range),
            ],
            'environment' =>  $this->getEnvironmentVariables($vars, $package, $pterodactylEgg),
            'start_on_completion' => true,
        ];
    }

    /**
     * Gets a list of parameters to submit to Pterodactyl for editing server details
     *
     * @param array $vars An array of post fields
     * @param stdClass $pterodactylUser An object representing the Pterodacytl user
     * @return array The list of parameters
     */
    public function editServerParameters(array $vars, $pterodactylUser)
    {
        // Gather server data
        return [
            'external_id' => $vars['client_id'] . '-' . (isset($vars['service_id']) ? $vars['service_id'] : uniqid()),
            'name' => $vars['server_name'],
            'description' => $vars['server_description'],
            'user' => $pterodactylUser->attributes->id,
        ];
    }

    /**
     * Gets a list of parameters to submit to Pterodactyl for editing the server build parameters
     *
     * @param array $vars An array of post fields
     * @param stdClass $package The package to pull server info from
     * @return array The list of parameters
     */
    public function editServerBuildParameters(array $vars, $package)
    {
        // Gather server data
        return [
            'allocation' => !empty($vars['allocation']) ? $vars['allocation'] : 1,
            'memory' => $package->meta->memory,
            'swap' => $package->meta->swap,
            'io' => $package->meta->io,
            'cpu' => $package->meta->cpu,
            'disk' => $package->meta->disk,
            'feature_limits' => [
                'databases' => !empty($package->meta->databases) ? $package->meta->databases : null,
                'allocations' => !empty($package->meta->allocations) ? $package->meta->allocations : null,
                'backups' => !empty($package->meta->backups) ? $package->meta->backups : null,
            ]
        ];
    }

    /**
     * Gets a list of parameters to submit to Pterodactyl for editing server startup parameters
     *
     * @param array $vars An array of post fields
     * @param stdClass $package The package to pull server info from
     * @param stdClass $pterodactylEgg An object representing the Pterodacytl egg
     * @param stdClass $serviceFields An object representing the fields set on the current service (optional)
     * @return array The list of parameters
     */
    public function editServerStartupParameters(array $vars, $package, $pterodactylEgg, $serviceFields = null)
    {
        // Gather server data
        return [
            'egg' => $package->meta->egg_id,
            'pack' => $package->meta->pack_id,
            'image' => !empty($package->meta->image)
                ? $package->meta->image
                : $pterodactylEgg->attributes->docker_image,
            'startup' => !empty($package->meta->startup)
                ? $package->meta->startup
                : $pterodactylEgg->attributes->startup,
            'environment' => $this->getEnvironmentVariables($vars, $package, $pterodactylEgg, $serviceFields),
            'skip_scripts' => false,
        ];
    }

    /**
     * Gets a list of environment variables to submit to Pterodactyl
     *
     * @param array $vars An array of post fields
     * @param stdClass $package The package to pull server info from
     * @param stdClass $pterodactylEgg An object representing the Pterodacytl egg
     * @param stdClass $serviceFields An object representing the fields set on the current service (optional)
     * @return array The list of environment variables and their values
     */
    public function getEnvironmentVariables(array $vars, $package, $pterodactylEgg, $serviceFields = null)
    {
        // Get environment data from the egg
        $environment = [];
        foreach ($pterodactylEgg->attributes->relationships->variables->data as $envVariable) {
            $variableName = $envVariable->attributes->env_variable;
            $blestaVariableName = strtolower($variableName);
            // Set the variable value based on values submitted in the following
            // priority order: config option, service field, package field, Pterodactyl default
            if (isset($vars['configoptions']) && isset($vars['configoptions'][$blestaVariableName])) {
                // Use a config option
                $environment[$variableName] = $vars['configoptions'][$blestaVariableName];
            } elseif (isset($vars[$blestaVariableName])) {
                // Use the service field
                $environment[$variableName] = $vars[$blestaVariableName];
            } elseif (isset($serviceFields) && isset($serviceFields->{$blestaVariableName})) {
                // Reset the previously saved value
                $environment[$variableName] = $serviceFields->{$blestaVariableName};
            } elseif (isset($package->meta->{$blestaVariableName})) {
                // Default to the value set on the package
                $environment[$variableName] = $package->meta->{$blestaVariableName};
            } else {
                // Default to the default value from Pterodactyl
                $environment[$variableName] = $envVariable->attributes->default_value;
            }
        }

        return $environment;
    }

    /**
     * Returns all fields used when adding/editing a service, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param stdClass $pterodactylEgg An object representing the Pterodacytl egg
     * @param stdClass $package The package to pull server info from
     * @param stdClass $vars A stdClass object representing a set of post fields (optional)
     * @param bool $admin Whether these fields will be displayed to an admin (optional)
     * @return ModuleFields A ModuleFields object, containing the fields
     *  to render as well as any additional HTML markup to include
     */
    public function getFields($pterodactylEgg, $package, $vars = null, $admin = false)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        if ($admin) {
            // Set the server ID
            $serverId = $fields->label(
                Language::_('PterodactylService.service_fields.server_id', true),
                'server_id'
            );
            $serverId->attach(
                $fields->fieldText(
                    'server_id',
                    (isset($vars->server_id) ? $vars->server_id : null),
                    ['id' => 'server_id']
                )
            );
            $tooltip = $fields->tooltip(Language::_('PterodactylService.service_fields.tooltip.server_id', true));
            $serverId->attach($tooltip);
            $fields->setField($serverId);
        }

        // Set the server name
        $serverName = $fields->label(
            Language::_('PterodactylService.service_fields.server_name', true),
            'server_name'
        );
        $serverName->attach(
            $fields->fieldText(
                'server_name',
                (isset($vars->server_name) ? $vars->server_name : null),
                ['id' => 'server_name']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylService.service_fields.tooltip.server_name', true));
        $serverName->attach($tooltip);
        $fields->setField($serverName);

        // Set the server description
        $serverDescription = $fields->label(
            Language::_('PterodactylService.service_fields.server_description', true),
            'server_description'
        );
        $serverDescription->attach(
            $fields->fieldText(
                'server_description',
                (isset($vars->server_description) ? $vars->server_description : null),
                ['id' => 'server_description']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylService.service_fields.tooltip.server_description', true));
        $serverDescription->attach($tooltip);
        $fields->setField($serverDescription);

        if ($pterodactylEgg) {
            // Get service fields from the egg
            foreach ($pterodactylEgg->attributes->relationships->variables->data as $envVariable) {
                // Hide the field from clients unless it is marked for display on the package
                $key = strtolower($envVariable->attributes->env_variable);
                if (!$admin
                    && (!isset($package->meta->{$key . '_display'}) || $package->meta->{$key . '_display'} != '1')
                ) {
                    continue;
                }

                // Create a label for the environment variable
                $label = strpos($envVariable->attributes->rules, 'required') === 0
                    ? $envVariable->attributes->name
                    : Language::_('PterodactylService.service_fields.optional', true, $envVariable->attributes->name);
                $field = $fields->label($label, $key);
                // Create the environment variable field and attach to the label
                $field->attach(
                    $fields->fieldText(
                        $key,
                        (isset($vars->{$key})
                            ? $vars->{$key}
                            : (isset($package->meta->{$key})
                                ? $package->meta->{$key}
                                : $envVariable->attributes->default_value
                            )
                        ),
                        ['id' => $key]
                    )
                );
                // Add tooltip based on the description from Pterodactyl
                $tooltip = $fields->tooltip($envVariable->attributes->description);
                $field->attach($tooltip);
                // Set the label as a field
                $fields->setField($field);
            }
        }

        $egg_id = isset($package->configurable_options['egg_id']) ? $package->configurable_options['egg_id'] : 0;
        $nest_id = isset($package->configurable_options['nest_id']) ? $package->configurable_options['nest_id'] : 0;
        $location_id = isset($package->configurable_options['location_id'])
            ? $package->configurable_options['location_id']
            : 0;
        // Set js to refetch options when the nest or egg is changed
        $fields->setHtml("
            <script type=\"text/javascript\">
                $(document).ready(function() {
                    // Re-fetch module options to pull in eggs and egg variables
                    // when a nest or egg respectively is selected
                    $('.package_options').on(
                        'focusout',
                        '*[name=\"configoptions[$egg_id]\"], *[name=\"configoptions[$nest_id]\"], *[name=\"configoptions[$location_id]\"]',
                        function() {
                            var form = $(this).closest('form');
                            $(form).append('<input type=\"hidden\" name=\"refresh_fields\" value=\"true\">');
                            $(form).submit();
                        }
                    );
                });
            </script>
        ");

        return $fields;
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars (optional)
     * @param stdClass $package A stdClass object representing the selected package (optional)
     * @param bool $edit True to get the edit rules, false for the add rules (optional)
     * @param stdClass $pterodactylEgg An egg object from Pterodactyl (optional)
     * @return array Service rules
     */
    public function getServiceRules(array $vars = null, $package = null, $edit = false, $pterodactylEgg = null)
    {
        // Set rules
        $rules = [
            'server_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('PterodactylService.!error.server_name.empty', true)
                ]
            ]
        ];

        // Get the rule helper
        Loader::load(dirname(__FILE__). DS . 'pterodactyl_rule.php');
        $rule_helper = new PterodactylRule();

        // Get egg variable rules
        if ($pterodactylEgg) {
            foreach ($pterodactylEgg->attributes->relationships->variables->data as $envVariable) {
                $fieldName = strtolower($envVariable->attributes->env_variable);
                $rules[$fieldName] = $rule_helper->parseEggVariable($envVariable);

                foreach ($rules[$fieldName] as $rule) {
                    if (array_key_exists('if_set', $rule)
                        && $rule['if_set'] == true
                        && empty($vars[$fieldName])
                    ) {
                        unset($rules[$fieldName]);
                    }
                }
            }
        }

        return $rules;
    }
}
