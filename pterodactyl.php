<?php
use Blesta\PterodactylSDK\PterodactylApi;
use Blesta\Core\Util\Validate\Server;

/**
 * Pterodactyl Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.pterodactyl
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Pterodactyl extends Module
{
    /**
     * Initializes the module
     */
    public function __construct()
    {
        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('pterodactyl', null, dirname(__FILE__) . DS . 'language' . DS);
        Language::loadLang('pterodactyl_package', null, dirname(__FILE__) . DS . 'language' . DS);
        Language::loadLang('pterodactyl_service', null, dirname(__FILE__) . DS . 'language' . DS);
        Language::loadLang('pterodactyl_rule', null, dirname(__FILE__) . DS . 'language' . DS);

        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load additional config values
        Configure::load('pterodactyl', dirname(__FILE__) . DS . 'config' . DS);
    }

    /**
     * Performs migration of data from $current_version (the current installed version)
     * to the given file set version. Sets Input errors on failure, preventing
     * the module from being upgraded.
     *
     * @param string $current_version The current installed version of this module
     */
    public function upgrade($current_version)
    {
        if (version_compare($current_version, '1.1.0', '<')) {
            if (!isset($this->ModuleManager)) {
                Loader::loadModels($this, ['ModuleManager']);
            }

            // Update all module rows to set host_name instead of panel_url
            $modules = $this->ModuleManager->getByClass('pterodactyl');
            foreach ($modules as $module) {
                $rows = $this->ModuleManager->getRows($module->id);
                foreach ($rows as $row) {
                    $meta = (array)$row->meta;
                    if (isset($meta['panel_url'])) {
                        $meta['host_name'] = $meta['panel_url'];
                        unset($meta['panel_url']);
                        $this->ModuleManager->editRow($row->id, $meta);
                    }
                }
            }
        }
    }

    /**
     * Loads a library class
     *
     * @param string $command The filename of the class to load
     */
    private function loadLib($command) {
        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . $command . '.php');
    }

    /**
     * Returns an array of available service deligation order methods. The module
     * will determine how each method is defined. For example, the method "first"
     * may be implemented such that it returns the module row with the least number
     * of services assigned to it.
     *
     * @return array An array of order methods in key/value paris where the key is
     *  the type to be stored for the group and value is the name for that option
     * @see Module::selectModuleRow()
     */
    public function getGroupOrderOptions()
    {
        return [
            'first' => Language::_('Pterodactyl.order_options.first', true)
        ];
    }

    /**
     * Determines which module row should be attempted when a service is provisioned
     * for the given group based upon the order method set for that group.
     *
     * @return int The module row ID to attempt to add the service with
     * @see Module::getGroupOrderOptions()
     */
    public function selectModuleRow($module_group_id)
    {
        if (!isset($this->ModuleManager)) {
            Loader::loadModels($this, ['ModuleManager']);
        }

        $group = $this->ModuleManager->getGroup($module_group_id);

        if ($group) {
            switch ($group->add_order) {
                default:
                case 'first':

                    foreach ($group->rows as $row) {
                        return $row->id;
                    }

                    break;
            }
        }
        return 0;
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request (optional)
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null)
    {
        return $this->getServiceRules($vars, $package);
    }

    /**
     * Attempts to validate an existing service against a set of service info updates. Sets Input errors on failure.
     *
     * @param stdClass $service A stdClass object representing the service to validate for editing
     * @param array $vars An array of user-supplied info to satisfy the request (optional)
     * @return bool True if the service update validates or false otherwise. Sets Input errors when false.
     */
    public function validateServiceEdit($service, array $vars = null)
    {
        $package = isset($service->package) ? $service->package : null;
        return $this->getServiceRules($vars, $package, true);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars (optional)
     * @param stdClass $package A stdClass object representing the selected package (optional)
     * @param bool $edit True to get the edit rules, false for the add rules (optional)
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $package = null, $edit = false)
    {
        // Get the service helper
        $this->loadLib('pterodactyl_service');
        $service_helper = new PterodactylService();

        if ($package) {
            // Get and set the module row to use for API calls
            if ($package->module_group) {
                $this->setModuleRow($this->getModuleRow($this->selectModuleRow($package->module_group)));
            } else {
                $this->setModuleRow($this->getModuleRow($package->module_row));
            }

            // Load egg
            $pterodactyl_egg = $this->apiRequest(
                'Nests',
                'eggsGet',
                ['nest_id' => $package->meta->nest_id, 'egg_id' => $package->meta->egg_id]
            );

            $errors = $this->Input->errors();
            if (!empty($errors)) {
                $pterodactyl_egg = null;
            } else {
                // Set egg variables from service, package, or config options
                $egg_variables = $service_helper->getEnvironmentVariables($vars, $package, $pterodactyl_egg);
                $vars = array_merge($vars, array_change_key_case($egg_variables));
            }
        }

        $this->Input->setRules($service_helper->getServiceRules($vars, $package, $edit, $pterodactyl_egg));
        return $this->Input->validates($vars);
    }

    /**
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request (optional)
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service) (optional)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being added (if the current service is an addon service
     *  service and parent service has already been provisioned) (optional)
     * @param string $status The status of the service being added. (optional) These include:
     *
     *  - active
     *  - canceled
     *  - pending
     *  - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = 'pending'
    ) {
        Loader::loadModels($this, ['ModuleClientMeta', 'Clients']);

        $meta = [];

        // Set configurable options
        $package = $this->getConfigurableOptions($vars, $package);

        // Load egg
        $pterodactyl_egg = $this->apiRequest(
            'Nests',
            'eggsGet',
            ['nest_id' => $package->meta->nest_id, 'egg_id' => $package->meta->egg_id]
        );
        if ($this->Input->errors()) {
            return;
        }

        $this->validateService($package, $vars);
        if ($this->Input->errors()) {
            return;
        }

        // Get the service helper
        $this->loadLib('pterodactyl_service');
        $service_helper = new PterodactylService();
        if ($vars['use_module'] == 'true') {
            // Load module
            $module = $this->getModule();

            // Fetch client
            $client = $this->Clients->get($vars['client_id'] ?? null);

            // Check if a user already exists with the client's email
            $pterodactyl_user = $this->apiRequest('Users', 'getByEmail', [$client->email]);
            $pterodactyl_user = !empty($pterodactyl_user->data) ? reset($pterodactyl_user->data) : null;

            if ($this->Input->errors()) {
                $this->Input->setErrors([]);
            }

            // Check if a user already exists with the current external id
            if (empty($pterodactyl_user)) {
                $pterodactyl_user = $this->apiRequest('Users', 'getByExternalID', ['bl-' . $vars['client_id']]);
            }

            if ($this->Input->errors()) {
                $this->Input->setErrors([]);
            }

            // If an account does not exists, create a new one and keep track of the credentials
            if (empty($pterodactyl_user)) {
                $addParameters = $service_helper->addUserParameters($vars);
                $pterodactyl_user = $this->apiRequest('Users', 'add', [$addParameters]);

                if ($this->Input->errors()) {
                    return;
                }

                // Keep track of the username and password used for this client
                $this->ModuleClientMeta->set(
                    $vars['client_id'],
                    $module->id,
                    0,
                    [
                        ['key' => 'pterodactyl_username', 'value' => $addParameters['username'], 'encrypted' => 0],
                        ['key' => 'pterodactyl_password', 'value' => $addParameters['password'], 'encrypted' => 1]
                    ]
                );
            }

            // Get Pterodactyl credentials
            $server_username = $this->ModuleClientMeta->get(
                $vars['client_id'],
                'pterodactyl_username',
                $module->id
            );
            $server_password = $this->ModuleClientMeta->get(
                $vars['client_id'],
                'pterodactyl_password',
                $module->id
            );

            $vars['server_username'] = isset($server_username->value) ? $server_username->value : null;
            $vars['server_password'] = isset($server_password->value) ? $server_password->value : null;

            // Create server
            $pterodactyl_server = $this->apiRequest(
                'Servers',
                'add',
                [$service_helper->addServerParameters($vars, $package, $pterodactyl_user, $pterodactyl_egg)]
            );
            if ($this->Input->errors()) {
                // No need to roll back user creation, we'll just use that user for future requests
                return;
            }

            $meta['server_id'] = $pterodactyl_server->attributes->id;
            $meta['external_id'] = $pterodactyl_server->attributes->external_id;
            if (isset($pterodactyl_server->attributes->relationships)
                && isset($pterodactyl_server->attributes->relationships->allocations)
                && isset($pterodactyl_server->attributes->relationships->allocations->data[0])
            ) {
                $allocation = $pterodactyl_server->attributes->relationships->allocations->data[0];
                $meta['server_ip'] = isset($allocation->attributes->ip) ? $allocation->attributes->ip : null;
                $meta['server_port'] = isset($allocation->attributes->port) ? $allocation->attributes->port : null;
            }
        }

        $return = [
            [
                'key' => 'server_id',
                'value' => isset($meta['server_id'])
                    ? $meta['server_id'] :
                    (isset($vars['server_id']) ? $vars['server_id'] : null),
                'encrypted' => 0
            ],
            [
                'key' => 'external_id',
                'value' => !empty($meta['external_id'])
                    ? $meta['external_id']
                    : (isset($vars['external_id']) ? $vars['external_id'] : null),
                'encrypted' => 0
            ],
            [
                'key' => 'server_ip',
                'value' => isset($meta['server_ip'])
                    ? $meta['server_ip'] :
                    (isset($vars['server_ip']) ? $vars['server_ip'] : null),
                'encrypted' => 0
            ],
            [
                'key' => 'server_port',
                'value' => isset($meta['server_port'])
                    ? $meta['server_port'] :
                    (isset($vars['server_port']) ? $vars['server_port'] : null),
                'encrypted' => 0
            ],
            [
                'key' => 'server_name',
                'value' => isset($vars['server_name']) ? $vars['server_name'] : '',
                'encrypted' => 0
            ],
            [
                'key' => 'server_description',
                'value' => isset($vars['server_description']) ? $vars['server_description'] : '',
                'encrypted' => 0
            ],
            [
                'key' => 'server_username',
                'value' => isset($vars['server_username']) ? $vars['server_username'] : '',
                'encrypted' => 0
            ],
            [
                'key' => 'server_password',
                'value' => isset($vars['server_password']) ? $vars['server_password'] : '',
                'encrypted' => 1
            ],
        ];

        $environment_variables = $service_helper->getEnvironmentVariables($vars, $package, $pterodactyl_egg);
        foreach ($environment_variables as $environment_variable => $value) {
            $return[] = [
                'key' => strtolower($environment_variable),
                'value' => $value,
                'encrypted' => 0
            ];
        }

        return $return;
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request (optional)
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service) (optional)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being edited (if the current service is an addon service) (optional)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editService(
        $package,
        $service,
        array $vars = null,
        $parent_package = null,
        $parent_service = null
    ) {
        $service_fields = $this->serviceFieldsToObject($service->fields);

        $this->validateServiceEdit($service, $vars);
        if ($this->Input->errors()) {
            return;
        }

        // Get the service helper
        $this->loadLib('pterodactyl_service');
        $service_helper = new PterodactylService();

        // Set configurable options
        $package = $this->getConfigurableOptions($vars, $package);

        // Load egg
        $pterodactyl_egg = $this->apiRequest(
            'Nests',
            'eggsGet',
            ['nest_id' => $package->meta->nest_id, 'egg_id' => $package->meta->egg_id]
        );
        if ($this->Input->errors()) {
            return;
        }

        if ($vars['use_module'] == 'true') {
            // Load user account
            $pterodactyl_user = $this->apiRequest('Users', 'getByExternalID', ['bl-' . $service->client_id]);

            // Load the server
            $pterodactyl_server = $this->getServer($service);

            if ($this->Input->errors()) {
                return;
            }

            // Edit server details
            $vars['service_id'] = $service->id;
            $vars['client_id'] = $service->client_id;
            $pterodactyl_server_edited = $this->apiRequest(
                'Servers',
                'editDetails',
                [$pterodactyl_server->attributes->id, $service_helper->editServerParameters($vars, $pterodactyl_user)]
            );
            if ($this->Input->errors()) {
                return;
            }

            // Set service fields
            $vars['server_id'] = $pterodactyl_server_edited->attributes->id;
            $vars['external_id'] = $pterodactyl_server_edited->attributes->external_id;
            if (isset($pterodactyl_server_edited->attributes->relationships)
                && isset($pterodactyl_server_edited->attributes->relationships->allocations)
                && isset($pterodactyl_server_edited->attributes->relationships->allocations->data[0])
            ) {
                $allocation = $pterodactyl_server_edited->attributes->relationships->allocations->data[0];
                $vars['server_ip'] = isset($allocation->attributes->ip) ? $allocation->attributes->ip : null;
                $vars['server_port'] = isset($allocation->attributes->port) ? $allocation->attributes->port : null;
            }

            // Set package fields
            $vars['allocation'] = $pterodactyl_server->attributes->allocation;
            $this->apiRequest(
                'Servers',
                'editBuild',
                [$pterodactyl_server->attributes->id, $service_helper->editServerBuildParameters($vars, $package)]
            );

            // Edit startup parameters
            $this->apiRequest(
                'Servers',
                'editStartup',
                [
                    $pterodactyl_server->attributes->id,
                    $service_helper->editServerStartupParameters($vars, $package, $pterodactyl_egg, $service_fields)
                ]
            );
            if ($this->Input->errors()) {
                return;
            }
        }

        $return = [
            [
                'key' => 'server_id',
                'value' => !empty($vars['server_id']) ? $vars['server_id'] : $service_fields->server_id,
                'encrypted' => 0
            ],
            [
                'key' => 'external_id',
                'value' => !empty($vars['external_id'])
                    ? $vars['external_id']
                    : (isset($service_fields->external_id) ? $service_fields->external_id : null),
                'encrypted' => 0
            ],
            [
                'key' => 'server_ip',
                'value' => !empty($vars['server_ip']) ? $vars['server_ip'] : $service_fields->server_ip,
                'encrypted' => 0
            ],
            [
                'key' => 'server_port',
                'value' => !empty($vars['server_port']) ? $vars['server_port'] : $service_fields->server_port,
                'encrypted' => 0
            ],
            [
                'key' => 'server_name',
                'value' => isset($vars['server_name']) ? $vars['server_name'] : $service_fields->server_name,
                'encrypted' => 0
            ],
            [
                'key' => 'server_description',
                'value' => isset($vars['server_description'])
                    ? $vars['server_description']
                    : $service_fields->server_description,
                'encrypted' => 0
            ],
            [
                'key' => 'server_username',
                'value' => isset($vars['server_username']) ? $vars['server_username'] : $service_fields->server_username,
                'encrypted' => 0
            ],
            [
                'key' => 'server_password',
                'value' => isset($vars['server_password']) ? $vars['server_password'] : $service_fields->server_password,
                'encrypted' => 1
            ],
        ];

        // Add egg variables
        $environment_variables = $service_helper->getEnvironmentVariables(
            $vars,
            $package,
            $pterodactyl_egg,
            $service_fields
        );
        foreach ($environment_variables as $environment_variable => $value) {
            $return[] = [
                'key' => strtolower($environment_variable),
                'value' => $value,
                'encrypted' => 0
            ];
        }

        return $return;
    }

    /**
     * Cancels the service on the remote server. Sets Input errors on failure,
     * preventing the service from being canceled.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service) (optional)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being canceled (if the current service is an addon service) (optional)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        // Load the server
        $pterodactyl_server = $this->getServer($service);

        // Delete the server
        $this->apiRequest(
            'Servers',
            'delete',
            ['server_id' => $pterodactyl_server ? $pterodactyl_server->attributes->id : null]
        );

        // We do not delete the user, but rather leave it around to be used for any current or future services

        return null;
    }

    /**
     * Suspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being suspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being suspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        // Load the server
        $pterodactyl_server = $this->getServer($service);

        // Suspend the server
        $this->apiRequest(
            'Servers',
            'suspend',
            ['server_id' => $pterodactyl_server ? $pterodactyl_server->attributes->id : null]
        );

        return null;
    }

    /**
     * Unsuspends the service on the remote server. Sets Input errors on failure,
     * preventing the service from being unsuspended.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being unsuspended (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function unsuspendService($package, $service, $parent_package = null, $parent_service = null)
    {
        // Load the server
        $pterodactyl_server = $this->getServer($service);

        // Unsuspend the server
        $this->apiRequest(
            'Servers',
            'unsuspend',
            ['server_id' => $pterodactyl_server ? $pterodactyl_server->attributes->id : null]
        );

        return null;
    }

    /**
     * Gets a Pterodactyl server for the given service
     *
     * @param stdClass $service A stdClass object representing the current service
     * @return PterodactylResponse An object representing the Pterodactyl server
     */
    private function getServer($service)
    {
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Load server
        if (!empty($service_fields->server_id)) {
            $pterodactyl_server = $this->apiRequest('Servers', 'get', [$service_fields->server_id]);
        } else {
            $pterodactyl_server = $this->apiRequest(
                'Servers',
                'getByExternalID',
                [!empty($service_fields->external_id) ? $service_fields->external_id : null]
            );
        }

        return $pterodactyl_server;
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * admin interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getAdminServiceInfo($service, $package)
    {
        Loader::loadModels($this, ['ModuleClientMeta']);
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'pterodactyl' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get username and password for the account
        $module = $this->getModule();
        $username = $this->ModuleClientMeta->get($service->client_id, 'pterodactyl_username', $module->id);
        $password = $this->ModuleClientMeta->get($service->client_id, 'pterodactyl_password', $module->id);

        // Set view data
        $this->view->set('module_row', $row);
        $this->view->set('username', $username ? $username->value : '');
        $this->view->set('password', $password ? $password->value : '');

        return $this->view->fetch();
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * client interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getClientServiceInfo($service, $package)
    {
        Loader::loadModels($this, ['ModuleClientMeta']);
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('client_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'pterodactyl' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get username and password for the account
        $module = $this->getModule();
        $username = $this->ModuleClientMeta->get($service->client_id, 'pterodactyl_username', $module->id);
        $password = $this->ModuleClientMeta->get($service->client_id, 'pterodactyl_password', $module->id);

        // Set view data
        $this->view->set('module_row', $row);
        $this->view->set('username', $username ? $username->value : '');
        $this->view->set('password', $password ? $password->value : '');

        return $this->view->fetch();
    }

    /**
     * Returns all tabs to display to an admin when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getAdminTabs($package)
    {
        return [
            'tabActions' => Language::_('Pterodactyl.tab_actions', true)
        ];
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title.
     *  Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getClientTabs($package)
    {
        return [
            'tabClientActions' => Language::_('Pterodactyl.tab_client_actions', true)
        ];
    }

    /**
     * Actions tab (start, stop, restart)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_actions', 'default');

        return $this->actionsTab($package, $service, false, $get, $post);
    }

    /**
     * Client Actions tab (start, stop, restart)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientActions($package, $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View('tab_client_actions', 'default');

        return $this->actionsTab($package, $service, true, $get, $post);
    }
    /**
     * Handles data for the actions tab in the client and admin interfaces
     * @see Pterodactyl::tabActions() and Pterodactyl::tabClientActions()
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param bool $client True if the action is being performed by the client, false otherwise
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     */
    private function actionsTab($package, $service, $client = false, array $get = null, array $post = null)
    {
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get server information from the application API
        $server = $this->getServer($service);
        $server_id = isset($server->attributes->identifier) ? $server->attributes->identifier : null;

        // Get the service fields
        $get_key = '3';
        if ($client) {
            $get_key = '2';
        }

        // Perform actions
        if (array_key_exists($get_key, (array)$get)
            && in_array($get[$get_key], ['start', 'stop', 'restart'])
            && isset($server->attributes->identifier)
        ) {
            // Send a power signal
            $signal_response = $this->apiRequest(
                'Client',
                'serverPowerSignal',
                [$server->attributes->identifier, $get[$get_key]],
                true
            );
            $errors = $this->Input->errors();
            if (empty($errors)) {
                $this->setMessage('success', Language::_('Pterodactyl.!success.' . $get[$get_key], true));
            }
        }

        // Fetch the server status from the account API
        $this->view->set('server', $this->apiRequest('Client', 'getServerUtilization', [$server_id], true));

        $this->view->set('client_id', $service->client_id);
        $this->view->set('service_id', $service->id);

        $this->view->set('view', $this->view->view);
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'pterodactyl' . DS);

        return $this->view->fetch();
    }

    /**
     * Returns an array of package fields, overriding the configurable options
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return stdClass The modified package object
     */
    private function getConfigurableOptions(array $vars, $package)
    {
        $fields = [
            'location_id', 'egg_id', 'nest_id', 'port_range',
            'pack_id', 'memory', 'swap', 'cpu', 'disk', 'io',
            'startup', 'image', 'databases', 'allocations', 'backups'
        ];

        // Override package fields, if an equivalent configurable option exists
        if (!empty($vars['configoptions'])) {
            foreach ($vars['configoptions'] as $field => $value) {
                if (in_array($field, $fields)) {
                    $package->meta->{$field} = $value;
                }
            }
        }

        return $package;
    }

    /**
     * Runs a particaluar API requestor method, logs, and reports errors
     *
     * @param string $requestor The name of the requestor class to use
     * @param string $action The name of the requestor method to use
     * @param array $data The parameters to submit to the method (optional)
     * @return mixed The response from Pterodactyl
     */
    private function apiRequest($requestor, $action, array $data = [], $client_api = false)
    {
        // Fetch the module row
        $row = $this->getModuleRow();
        if (!$row) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Pterodactyl.!error.module_row.missing', true)]]
            );
            return;
        }

        // Fetch the API
        $api = $this->getApi(
            $row->meta->host_name,
            $client_api ? $row->meta->account_api_key : $row->meta->application_api_key,
            $row->meta->use_ssl == 'true'
        );

        // Perform the request
        try {
            $response = call_user_func_array([$api->{$requestor}, $action], $data);
        } catch (Throwable $e) {
            // Try executing the request again, without array keys
            $response = call_user_func_array([$api->{$requestor}, $action], array_values($data));
        }

        $errors = $response->errors();
        $this->log($requestor . '.' . $action, json_encode($data), 'input', true);
        $this->log($requestor . '.' . $action, $response->raw(), 'output', empty($errors));

        // Check for request errors
        if (!empty($errors)) {
            $this->Input->setErrors([$requestor => $errors]);
            return;
        }

        return $response->response();
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array $vars An array of key/value pairs used to add the package (optional)
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addPackage(array $vars = null)
    {
        // Load the package helper
        $this->loadLib('pterodactyl_package');
        $package_helper = new PterodactylPackage();

        // Get package field lists from API
        $package_lists = $this->getPackageLists((object)$vars);

        // Validate and gather information using the package helper
        $meta = $package_helper->add($package_lists, $vars);
        if ($package_helper->errors()) {
            $this->Input->setErrors($package_helper->errors());
        }

        return $meta;
    }

    /**
     * Validates input data when attempting to edit a package, returns the meta
     * data to save when editing a package. Performs any action required to edit
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being edited.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of key/value pairs used to edit the package (optional)
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editPackage($package, array $vars = null)
    {
        // Adding and editing are the same
        return $this->addPackage($vars);
    }

    /**
     * Returns the rendered view of the manage module page
     *
     * @param mixed $module A stdClass object representing the module and its rows
     * @param array $vars An array of post data submitted to or on the manage module
     *  page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the manager module page
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('manage', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'pterodactyl' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        $this->view->set('module', $module);

        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the add module row page
     *
     * @param array $vars An array of post data submitted to or on the add module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the add module row page
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('add_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'pterodactyl' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        // Set unspecified checkboxes
        if (!empty($vars)) {
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Returns the rendered view of the edit module row page
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of post data submitted to or on the edit module
     *  row page (used to repopulate fields after an error)
     * @return string HTML content containing information to display when viewing the edit module row page
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('edit_row', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'pterodactyl' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html', 'Widget']);

        if (empty($vars)) {
            $vars = $module_row->meta;
        } else {
            // Set unspecified checkboxes
            if (empty($vars['use_ssl'])) {
                $vars['use_ssl'] = 'false';
            }
        }

        $this->view->set('vars', (object)$vars);
        return $this->view->fetch();
    }

    /**
     * Adds the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being added.
     *
     * @param array $vars An array of module info to add
     * @return array A numerically indexed array of meta fields for the module row containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'host_name', 'account_api_key', 'application_api_key', 'use_ssl'];
        $encrypted_fields = ['account_api_key', 'application_api_key'];

        // Set unspecified checkboxes
        if (empty($vars['use_ssl'])) {
            $vars['use_ssl'] = 'false';
        }

        $this->Input->setRules($this->getRowRules($vars));

        // Validate module row
        if ($this->Input->validates($vars)) {
            // Build the meta data for this row
            $meta = [];
            foreach ($vars as $key => $value) {
                if (in_array($key, $meta_fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
                    ];
                }
            }

            return $meta;
        }
    }

    /**
     * Edits the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being updated.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     * @param array $vars An array of module info to update
     * @return array A numerically indexed array of meta fields for the module row containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function editModuleRow($module_row, array &$vars)
    {
        // Same as adding
        return $this->addModuleRow($vars);
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields (optional)
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getPackageFields($vars = null)
    {
        // Fetch the package fields
        $this->loadLib('pterodactyl_package');
        $package = new PterodactylPackage();

        $package_lists = $this->getPackageLists($vars);

        return $package->getFields($package_lists, $vars);
    }

    /**
     * Get package field lists from API
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     */
    public function getPackageLists($vars)
    {
        // Fetch all packages available for the given server or server group
        $row = null;
        if (!isset($vars->module_group) || $vars->module_group == '') {
            if (isset($vars->module_row) && $vars->module_row > 0) {
                $row = $this->getModuleRow($vars->module_row);
            } else {
                $rows = $this->getModuleRows();
                if (isset($rows[0])) {
                    $row = $rows[0];
                }
                unset($rows);
            }
        } elseif (isset($vars->module_group)) {
            // Fetch the 1st server from the list of servers in the selected group
            $rows = $this->getModuleRows($vars->module_group);

            if (isset($rows[0])) {
                $row = $rows[0];
            }
            unset($rows);
        }

        $api = null;
        $package_lists = [];
        if ($row) {
            $api = $this->getApi($row->meta->host_name, $row->meta->application_api_key, $row->meta->use_ssl == 'true');

            // API request for locations
            $locations_response = $api->Locations->getAll();
            $this->log('Locations.getAll', json_encode([]), 'input', true);
            $this->log('Locations.getAll', $locations_response->raw(), 'output', $locations_response->status() == 200);

            // API request for nests
            $nests_response = $api->Nests->getAll();
            $this->log('Nests.getAll', json_encode([]), 'input', true);
            $this->log('Nests.getAll', $nests_response->raw(), 'output', $nests_response->status() == 200);

            // Gather a list of locations from the API response
            if ($locations_response->status() == 200) {
                $package_lists['locations'] = ['' => Language::_('AppController.select.please', true)];
                foreach ($locations_response->response()->data as $location) {
                    $package_lists['locations'][$location->attributes->id] = $location->attributes->long;
                }
            }

            // Gather a list of nests from the API response
            if ($nests_response->status() == 200) {
                $package_lists['nests'] = ['' => Language::_('AppController.select.please', true)];
                foreach ($nests_response->response()->data as $nest) {
                    $package_lists['nests'][$nest->attributes->id] = $nest->attributes->name;
                }
            }

            // Once we select a nest, gather a list of eggs from that belong to it
            if (!empty($vars->meta['nest_id'])) {
                // API request for eggs
                $eggs_response = $api->Nests->eggsGetAll($vars->meta['nest_id']);
                if ($eggs_response->status() == 200) {
                    $package_lists['eggs'] = ['' => Language::_('AppController.select.please', true)];
                    foreach ($eggs_response->response()->data as $egg) {
                        // TODO This lists egg IDs, but eggs have name, for some reason they are just not fetched by
                        // the API. We should probably look into that.
                        $package_lists['eggs'][$egg->attributes->id] = $egg;
                    }
                }

                // Log request data
                $this->log('Nests.eggsGetAll', json_encode(['nest_id' => $vars->meta['nest_id']]), 'input', true);
                $this->log('Nests.eggsGetAll', $eggs_response->raw(), 'output', $eggs_response->status() == 200);
            }
        }

        return $package_lists;
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param stdClass $vars A stdClass object representing a set of post fields (optional)
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        // Get and set the module row to use for API calls
        if ($package->module_group) {
            $this->setModuleRow($this->getModuleRow($this->selectModuleRow($package->module_group)));
        } else {
            $this->setModuleRow($this->getModuleRow($package->module_row));
        }

        // Load the service helper
        $this->loadLib('pterodactyl_service');
        $service_helper = new PterodactylService();

        // Get configurable options
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }
        if (!isset($this->Form)) {
            Loader::loadHelpers($this, ['Form']);
        }

        $nest_id = $package->meta->nest_id;
        $egg_id = $package->meta->egg_id;

        $option_groups = [];
        foreach ($package->option_groups as $option_group) {
            $option_groups[] = $option_group->id;
        }

        if (!empty($option_groups)) {
            $package->configurable_options = $this->Form->collapseObjectArray(
                $this->Record->select(['package_options.id', 'package_options.name'])
                    ->from('package_options')
                    ->innerJoin(
                        'package_option_group',
                        'package_option_group.option_id',
                        '=',
                        'package_options.id',
                        false
                    )
                    ->where('package_options.company_id', '=', Configure::get('Blesta.company_id'))
                    ->where('package_option_group.option_group_id', 'IN', $option_groups)
                    ->fetchAll(),
                'id',
                'name'
            );

            // Load nest/egg from config option if submitted
            if (isset($vars->configoptions)) {
                $config_options = $package->configurable_options;
                if (isset($config_options['nest_id'], $vars->configoptions[$config_options['nest_id']])) {
                    $nest_id = $vars->configoptions[$config_options['nest_id']];
                }

                if (isset($config_options['egg_id'], $vars->configoptions[$config_options['egg_id']])) {
                    $egg_id = $vars->configoptions[$config_options['egg_id']];
                }
            }
        }

        $pterodactyl_egg = $this->apiRequest(
            'Nests',
            'eggsGet',
            ['nest_id' => $nest_id, 'egg_id' => $egg_id]
        );

        // Fetch the service fields
        return $service_helper->getFields($pterodactyl_egg, $package, $vars, true);
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields (optional)
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        // Get and set the module row to use for API calls
        if ($package->module_group) {
            $this->setModuleRow($this->getModuleRow($this->selectModuleRow($package->module_group)));
        } else {
            $this->setModuleRow($this->getModuleRow($package->module_row));
        }

        // Load the service helper
        $this->loadLib('pterodactyl_service');
        $service_helper = new PterodactylService();

        // Get configurable options
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }
        if (!isset($this->Form)) {
            Loader::loadHelpers($this, ['Form']);
        }

        $nest_id = $package->meta->nest_id;
        $egg_id = $package->meta->egg_id;

        $option_groups = [];
        foreach ($package->option_groups as $option_group) {
            $option_groups[] = $option_group->id;
        }

        if (!empty($option_groups)) {
            $package->configurable_options = $this->Form->collapseObjectArray(
                $this->Record->select(['package_options.id', 'package_options.name'])
                    ->from('package_options')
                    ->innerJoin(
                        'package_option_group',
                        'package_option_group.option_id',
                        '=',
                        'package_options.id',
                        false
                    )
                    ->where('package_options.company_id', '=', Configure::get('Blesta.company_id'))
                    ->where('package_option_group.option_group_id', 'IN', $option_groups)
                    ->fetchAll(),
                'id',
                'name'
            );

            // Load nest/egg from config option if submitted
            if (isset($vars->configoptions)) {
                $config_options = $package->configurable_options;
                if (isset($config_options['nest_id'], $vars->configoptions[$config_options['nest_id']])) {
                    $nest_id = $vars->configoptions[$config_options['nest_id']];
                }

                if (isset($config_options['egg_id'], $vars->configoptions[$config_options['egg_id']])) {
                    $egg_id = $vars->configoptions[$config_options['egg_id']];
                }
            }
        }

        $pterodactyl_egg = $this->apiRequest(
            'Nests',
            'eggsGet',
            ['nest_id' => $nest_id, 'egg_id' => $egg_id]
        );

        // Fetch the service fields
        return $service_helper->getFields($pterodactyl_egg, $package, $vars);
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields (optional)
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        return $this->getAdminAddFields($package, $vars);
    }

    /**
     * Returns all fields to display to a client attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields (optional)
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getClientEditFields($package, $vars = null)
    {
        return $this->getClientAddFields($package, $vars);
    }

    /**
     * Initializes the PterodactylApi and returns an instance of that object with the given $host and $api_key set
     *
     * @param string $host The hostname of the Pterodactyl server
     * @param string $api_key The user to connect as
     * @param bool $use_ssl Whether to connect over ssl
     * @return PterodactylApi The PterodactylApi instance
     */
    private function getApi($host, $api_key, $use_ssl)
    {
        Loader::load(
            dirname(__FILE__) . DS . 'components' . DS . 'modules' . DS . 'pterodactyl-sdk' . DS . 'PterodactylApi.php'
        );

        return new PterodactylApi($api_key, $host, $use_ssl);
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(array &$vars)
    {
        return [
            'server_name' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Pterodactyl.!error.server_name.empty', true)
                ]
            ],
            'host_name' => [
                'valid' => [
                    'rule' => function ($host_name) {
                        $validator = new Server();
                        $parts = explode(':', $host_name, 2);
                        return ($validator->isDomain($parts[0]) || $validator->isIp($parts[0]))
                            && (!isset($parts[1]) || is_numeric($parts[1]));
                    },
                    'message' => Language::_('Pterodactyl.!error.host_name.valid', true)
                ]
            ],
            'account_api_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Pterodactyl.!error.account_api_key.empty', true)
                ],
                'valid' => [
                    'rule' => function ($api_key) use ($vars) {
                        try {
                            $api = $this->getApi(
                                isset($vars['host_name']) ? $vars['host_name'] : '',
                                $api_key,
                                (isset($vars['use_ssl']) ? $vars['use_ssl'] : 'true') == 'true'
                            );
                            $servers_response = $api->Client->getServers();

                            return $servers_response->status() == 200;
                        } catch (Exception $e) {
                            return false;
                        }
                    },
                    'message' => Language::_('Pterodactyl.!error.account_api_key.valid', true)
                ]
            ],
            'application_api_key' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('Pterodactyl.!error.application_api_key.empty', true)
                ],
                'valid' => [
                    'rule' => function ($api_key) use ($vars) {
                        try {
                            $api = $this->getApi(
                                isset($vars['host_name']) ? $vars['host_name'] : '',
                                $api_key,
                                (isset($vars['use_ssl']) ? $vars['use_ssl'] : 'true') == 'true'
                            );
                            $locations_response = $api->Locations->getAll();

                            return $locations_response->status() == 200;
                        } catch (Exception $e) {
                            return false;
                        }
                    },
                    'message' => Language::_('Pterodactyl.!error.application_api_key.valid', true)
                ]
            ]
        ];
    }
}
