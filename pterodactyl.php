<?php
use Blesta\PterodactylSDK\PterodactylApi;
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
        // Load the Pterodactyl API
        Loader::load(dirname(__FILE__) . DS . 'api' . DS . 'pterodactyl_api.php');

        // Load components required by this module
        Loader::loadComponents($this, ['Input']);

        // Load the language required by this module
        Language::loadLang('pterodactyl', null, dirname(__FILE__) . DS . 'language' . DS);
        Language::loadLang('pterodactyl_package', null, dirname(__FILE__) . DS . 'language' . DS);


        // Load configuration required by this module
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
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
     * Returns a noun used to refer to a module row (e.g. "Server", "VPS", "Reseller Account", etc.)
     *
     * @return string The noun used to refer to a module row
     */
    public function moduleRowName()
    {
        return Language::_('Pterodactyl.module_row', true);
    }

    /**
     * Returns a noun used to refer to a module row in plural form (e.g. "Servers", "VPSs", "Reseller Accounts", etc.)
     *
     * @return string The noun used to refer to a module row in plural form
     */
    public function moduleRowNamePlural()
    {
        return Language::_('Pterodactyl.module_row_plural', true);
    }

    /**
     * Returns a noun used to refer to a module group (e.g. "Server Group", "Cloud", etc.)
     *
     * @return string The noun used to refer to a module group
     */
    public function moduleGroupName()
    {
        return Language::_('Pterodactyl.module_group', true);
    }

    /**
     * Returns the key used to identify the primary field from the set of module row meta fields.
     * This value can be any of the module row meta fields.
     *
     * @return string The key used to identify the primary field from the set of module row meta fields
     */
    public function moduleRowMetaKey()
    {
        return 'server_name';
    }

    /**
     * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @return bool True if the service validates, false otherwise. Sets Input errors when false.
     */
    public function validateService($package, array $vars = null)
    {
        $this->Input->setRules($this->getServiceRules($vars, $package));
        return $this->Input->validates($vars);
    }

    /**
     * Attempts to validate an existing service against a set of service info updates. Sets Input errors on failure.
     *
     * @param stdClass $service A stdClass object representing the service to validate for editing
     * @param array $vars An array of user-supplied info to satisfy the request
     * @return bool True if the service update validates or false otherwise. Sets Input errors when false.
     */
    public function validateServiceEdit($service, array $vars = null)
    {
        $package = isset($service->package) ? $service->package : null;

        $this->Input->setRules($this->getServiceRules($vars, $package, true));
        return $this->Input->validates($vars);
    }

    /**
     * Returns the rule set for adding/editing a service
     *
     * @param array $vars A list of input vars
     * @param stdClass $package A stdClass object representing the selected package
     * @param bool $edit True to get the edit rules, false for the add rules
     * @return array Service rules
     */
    private function getServiceRules(array $vars = null, $package = null, $edit = false)
    {
        // Set rules
        $rules = [
        ];

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

    /**
     * Adds the service to the remote server. Sets Input errors on failure,
     * preventing the service from being added.
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being added (if the current service is an addon service
     *  service and parent service has already been provisioned)
     * @param string $status The status of the service being added. These include:
     *  - active
     *  - canceled
     *  - pending
     *  - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
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
        // Get module row and API
        if (!($module_row = $this->getModuleRow())) {
            $this->Input->setErrors(
                ['module_row' => ['missing' => Language::_('Pterodactyl.!error.module_row.missing', true)]]
            );
            return;
        }
        $api = $this->getApi(
            $module_row->meta->panel_url,
            $module_row->meta->account_api_key
        );

        $params = $this->getFieldsFromInput((array)$vars, $package);

        $this->validateService($package, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
            $response = $api->User->add($params);
        }

        // Return service fields
        return [
        ];
    }

    /**
     * Edits the service on the remote server. Sets Input errors on failure,
     * preventing the service from being edited.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $vars An array of user supplied info to satisfy the request
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being edited (if the current service is an addon service)
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
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
        // Get module row and API
        $module_row = $this->getModuleRow();
        $api = $this->getApi(
            $module_row->meta->panel_url,
            $module_row->meta->account_api_key
        );

        $params = $this->getFieldsFromInput((array)$vars, $package);

        $this->validateServiceEdit($service, $vars);

        if ($this->Input->errors()) {
            return;
        }

        // Only use the module to update the service if 'use_module' is true
        if ($vars['use_module'] == 'true') {
        }

        // Set fields to update locally
        $fields = [];

        return $fields;
    }

    /**
     * Cancels the service on the remote server. Sets Input errors on failure,
     * preventing the service from being canceled.
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param stdClass $parent_package A stdClass object representing the parent
     *  service's selected package (if the current service is an addon service)
     * @param stdClass $parent_service A stdClass object representing the parent
     *  service of the service being canceled (if the current service is an addon service)
     * @return mixed null to maintain the existing meta fields or a numerically
     *  indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancelService($package, $service, $parent_package = null, $parent_service = null)
    {
        if (($module_row = $this->getModuleRow())) {
            $api = $this->getApi(
                $module_row->meta->panel_url,
                $module_row->meta->account_api_key
            );
        }

        return null;
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function addPackage(array $vars = null)
    {
        // Fetch the package fields
        $this->loadLib('pterodactyl_package');
        $package = new PterodactylPackage();

        // Set missing checkboxes
        $checkboxes = [
            'user_jar',
            'user_name',
            'user_schedule',
            'user_ftp',
            'user_visibility',
            'autostart',
            'create_ftp'
        ];
        foreach ($checkboxes as $checkbox) {
            if (empty($vars['meta'][$checkbox])) {
                $vars['meta'][$checkbox] = '0';
            }
        }

        // Get package field lists from API
        $package_lists = $this->getPackageLists((object)$vars);

        // Set rules to validate input data
        $this->Input->setRules($package->getRules($package_lists, $vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
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
     * @param array An array of key/value pairs used to edit the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function editPackage($package, array $vars = null)
    {
        // Set rules to validate input data
        $this->Input->setRules($this->getPackageRules($vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }
        return $meta;
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
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     */
    public function addModuleRow(array &$vars)
    {
        $meta_fields = ['server_name', 'panel_url', 'account_api_key', 'application_api_key'];
        $encrypted_fields = ['account_api_key', 'application_api_key'];

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
     * @param $vars stdClass A stdClass object representing a set of post fields
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
        $module_row = null;
        if (!isset($vars->module_group) || $vars->module_group == '') {
            if (isset($vars->module_row) && $vars->module_row > 0) {
                $module_row = $this->getModuleRow($vars->module_row);
            } else {
                $rows = $this->getModuleRows();
                if (isset($rows[0])) {
                    $module_row = $rows[0];
                }
                unset($rows);
            }
        } elseif (isset($vars->module_group)) {
            // Fetch the 1st server from the list of servers in the selected group
            $rows = $this->getModuleRows($vars->module_group);

            if (isset($rows[0])) {
                $module_row = $rows[0];
            }
            unset($rows);
        }

        $api = null;
        $package_lists = [];
        if ($module_row) {
            $api = $this->getApi($module_row->meta->panel_url, 'nClrY6tL4o72Kmc0qFFoaxHBZxtRVymUCsd0Z4tgwZFjtnZW');

            // API request for locations
            $locations_response = $api->Locations->getAll();
            $this->log('getlocations', json_encode([]), 'input', true);
            $this->log('getlocations', $locations_response->raw(), 'output', $locations_response->status() == 200);

            // API request for nests
            $nests_response = $api->Nests->getAll();
            $this->log('getnests', json_encode([]), 'input', true);
            $this->log('getnests', $nests_response->raw(), 'output', $nests_response->status() == 200);

            // Get locations
            if (!$locations_response->errors()) {
                $package_lists['locations'] = ['' => Language::_('AppController.select.please', true)];
                foreach ($locations_response->response()->data as $location) {
                    $package_lists['locations'][$location->attributes->id] = $location->attributes->long;
                }
            }

            // Get nests
            if (!$nests_response->errors()) {
                $package_lists['nests'] = ['' => Language::_('AppController.select.please', true)];
                foreach ($nests_response->response()->data as $nest) {
                    $package_lists['nests'][$nest->attributes->id] = $nest->attributes->name;
                }
            }

            // Get eggs
            if (!empty($vars->meta['nest_id'])) {
                $eggs_response = $api->Nests->eggsGetAll($vars->meta['nest_id']);
                if (!$eggs_response->errors()) {
                    $package_lists['eggs'] = ['' => Language::_('AppController.select.please', true)];
                    foreach ($eggs_response->response()->data as $egg) {
                        $package_lists['eggs'][$egg->attributes->id] = $egg->attributes->id;
                    }
                }

                // Log request data
                $this->log('geteggs', json_encode(['nest_id' => $vars->meta['nest_id']]), 'input', true);
                $this->log('geteggs', $eggs_response->raw(), 'output', $eggs_response->status() == 200);
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
            'module' => ['panel_url'],
            'package' => [],
            'service' => []
        ];
    }

    /**
     * Returns all fields to display to an admin attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        return $fields;
    }

    /**
     * Returns all fields to display to a client attempting to add a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getClientAddFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        return $fields;
    }

    /**
     * Returns all fields to display to an admin attempting to edit a service with the module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containg the fields to render
     *  as well as any additional HTML markup to include
     */
    public function getAdminEditFields($package, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        return $fields;
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

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

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

        $this->view->set('module_row', $row);
        $this->view->set('package', $package);
        $this->view->set('service', $service);
        $this->view->set('service_fields', $this->serviceFieldsToObject($service->fields));

        return $this->view->fetch();
    }

    /**
     * Returns an array of service fields to set for the service using the given input
     *
     * @param array $vars An array of key/value input pairs
     * @param stdClass $package A stdClass object representing the package for the service
     * @return array An array of key/value pairs representing service fields
     */
    private function getFieldsFromInput(array $vars, $package)
    {
        $fields = [
        ];

        return $fields;
    }

    /**
     * Initializes the PterodactylApi and returns an instance of that object with the given $host and $api_key set
     *
     * @param string $host The host to the Pterodactyl server
     * @param string $api_key The user to connect as
     * @return PterodactylApi The PterodactylApi instance
     */
    private function getApi($host, $api_key)
    {
        Loader::load(
            dirname(__FILE__) . DS . 'components' . DS . 'modules' . DS . 'pterodactyl-sdk' . DS . 'PterodactylApi.php'
        );

        return new PterodactylApi($api_key, $host);
    }

    /**
     * Builds and returns the rules required to add/edit a module row (e.g. server)
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRowRules(&$vars)
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
                    'rule' => [[$this, 'validateHostName']],
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
                            $api = $this->getApi(isset($vars['panel_url']) ? $vars['panel_url'] : '', $api_key);
                            $serversResponse = $api->Client->getServers();

                            return empty($serversResponse->errors());
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
                            $api = $this->getApi(isset($vars['panel_url']) ? $vars['panel_url'] : '', $api_key);
                            $serversResponse = $api->Locations->getAll();

                            return empty($serversResponse->errors());
                        } catch (Exception $e) {
                            return false;
                        }
                    },
                    'message' => Language::_('Pterodactyl.!error.application_api_key.valid', true)
                ]
            ]
        ];
    }

    /**
     * Validates that the given hostname is valid
     *
     * @param string $host_name The host name to validate
     * @return bool True if the hostname is valid, false otherwise
     */
    public function validateHostName($host_name)
    {
        if (strlen($host_name) > 255) {
            return false;
        }

        return $this->Input->matches(
            $host_name,
            "/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))+$/i"
        );
    }
}
