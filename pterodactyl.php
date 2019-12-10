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

            if (!empty($this->Input->errors())) {
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
        $meta = [];
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
        $pterodactyl_user = $this->apiRequest('Users', 'getByExternalId', [$vars['client_id']]);
        if ($vars['use_module'] == 'true') {
            // Load/create user account
            if ($this->Input->errors()) {
                $this->Input->setErrors([]);
                $pterodactyl_user = $this->apiRequest('Users', 'add', [$service_helper->addUserParameters($vars)]);
                if ($this->Input->errors()) {
                    return;
                }
            }


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
        }

        $return = [
            [
                'key' => 'username',
                'value' => isset($pterodactyl_user->attributes->username)
                    ? $pterodactyl_user->attributes->username
                    : '',
                'encrypted' => 0
            ],
            [
                'key' => 'server_id',
                'value' => isset($meta['server_id'])
                    ? $meta['server_id'] :
                    (isset($vars['server_id']) ? $vars['server_id'] : null),
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

        // Load user account
        $pterodactyl_user = $this->apiRequest('Users', 'getByExternalId', [$service->client_id]);
        if ($vars['use_module'] == 'true') {
            // Load egg
            $pterodactyl_egg = $this->apiRequest(
                'Nests',
                'eggsGet',
                ['nest_id' => $package->meta->nest_id, 'egg_id' => $package->meta->egg_id]
            );
            if ($this->Input->errors()) {
                return;
            }

            // Edit server details
            $this->apiRequest(
                'Servers',
                'editDetails',
                [$service_fields->server_id, $service_helper->editServerParameters($vars, $pterodactyl_user)]
            );
            if ($this->Input->errors()) {
                return;
            }

            // It is also possible to edit build details, but that is affeced purely by package
            // fields so we opted not to modify those when we edit the service

            // Edit startup parameters
            $this->apiRequest(
                'Servers',
                'editStartup',
                [
                    $service_fields->server_id,
                    $service_helper->editServerStartupParameters($vars, $package, $pterodactyl_egg, $service_fields)
                ]
            );
            if ($this->Input->errors()) {
                return;
            }
        }


        $return = [
            [
                'key' => 'username',
                'value' => isset($pterodactyl_user->attributes->username)
                    ? $pterodactyl_user->attributes->username
                    : '',
                'encrypted' => 0
            ],
            [
                'key' => 'server_id',
                'value' => !empty($vars['server_id']) ? $vars['server_id'] : $service_fields->server_id,
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
        // Delete the server
        $service_fields = $this->serviceFieldsToObject($service->fields);
        $this->apiRequest('Servers', 'delete', ['server_id' => $service_fields->server_id]);

        // We do not delete the user, but rather leave it around to be used for any current or future services

        return null;
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
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('admin_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'pterodactyl' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $service_fields = $this->serviceFieldsToObject($service->fields);
        $this->view->set('module_row', $row);
        $this->view->set('service_fields', $service_fields);

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
        $row = $this->getModuleRow();

        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View('client_service_info', 'default');
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView('components' . DS . 'modules' . DS . 'pterodactyl' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        $service_fields = $this->serviceFieldsToObject($service->fields);
        $this->view->set('module_row', $row);
        $this->view->set('service_fields', $service_fields);

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
    private function actionsTab($package, $service,$client = false, array $get = null, array $post = null)
    {
        $this->view->base_uri = $this->base_uri;
        // Load the helpers required for this view
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get the service fields
        $service_fields = $this->serviceFieldsToObject($service->fields);

        // Get server information from the application API
        $server = $this->apiRequest('Servers', 'get', [$service_fields->server_id]);
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
            if (empty($this->Input->errors())) {
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
            $row->meta->panel_url,
            $client_api ? $row->meta->account_api_key : $row->meta->application_api_key,
            $row->meta->use_ssl == 'true'
        );

        // Perform the request
        $response = call_user_func_array([$api->{$requestor}, $action], $data);
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
        $meta_fields = ['server_name', 'panel_url', 'account_api_key', 'application_api_key', 'use_ssl'];
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
            $api = $this->getApi($row->meta->panel_url, $row->meta->application_api_key, $row->meta->use_ssl == 'true');

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
     * Returns an array of key values for fields stored for a module, package,
     * and service under this module, used to substitute those keys with their
     * actual module, package, or service meta values in related emails.
     *
     * @return array A multi-dimensional array of key/value pairs where each key is
     *  one of 'module', 'package', or 'service' and each value is a numerically
     *  indexed array of key values that match meta fields under that category.
     * @see Modules::addModuleRow()
     * @see Modules::editModuleRow()
     * @see Modules::addPackage()
     * @see Modules::editPackage()
     * @see Modules::addService()
     * @see Modules::editService()
     */
    public function getEmailTags()
    {
        return [
            'module' => ['server_name', 'panel_url'],
            'package' => ['location_id', 'nest_id', 'egg_id', 'image'],
            'service' => ['server_name']
        ];
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields (optional)
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

        // Load egg
        $pterodactyl_egg = $this->apiRequest(
            'Nests',
            'eggsGet',
            ['nest_id' => $package->meta->nest_id, 'egg_id' => $package->meta->egg_id]
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

        // Load egg
        $pterodactyl_egg = $this->apiRequest(
            'Nests',
            'eggsGet',
            ['nest_id' => $package->meta->nest_id, 'egg_id' => $package->meta->egg_id]
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
            'panel_url' => [
                'valid' => [
                    'rule' => function ($host_name) {
                        $validator = new Server();
                        return $validator->isDomain($host_name);
                    },
                    'message' => Language::_('Pterodactyl.!error.panel_url.valid', true)
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
                                isset($vars['panel_url']) ? $vars['panel_url'] : '',
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
                                isset($vars['panel_url']) ? $vars['panel_url'] : '',
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
