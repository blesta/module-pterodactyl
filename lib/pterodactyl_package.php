<?php
/**
 * Pterodactyl Package helper
 *
 * @package blesta
 * @subpackage blesta.components.modules.Pterodactyl.lib
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PterodactylPackage
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
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array $packageLists An array of package fields lists from the API including:
     *
     *  - locations A list of location IDs
     *  - nests A list of nest IDs
     *  - eggs A list of eggs keyed by their IDs
     * @param array $vars An array of key/value pairs used to add the package (optional) including:
     *
     *  - location_id The ID of the Location to automatically deploy servers to.
     *  - nest_id The ID of the Nest to use for created servers.
     *  - egg_id The ID of the Egg to use for created servers.
     *  - dedicated_ip Whether to assign a dedicated ip to created servers (optional)
     *  - port_range Comma seperated port ranges to assign to created servers (optional)
     *  - pack_id The ID of the Pack to use for created servers (optional)
     *  - memory The memory limit in megabytes to assign created servers
     *  - swap The swap memory limit in megabytes to assign created servers
     *  - cpu The CPU limit in percentage to assign created servers
     *  - disk The disk space limit in megabytes to assign created servers
     *  - io The block IO adjustment number to assign created servers
     *  - startup The custom startup command to assign created servers (optional)
     *  - image The custom docker image to assign created servers (optional)
     *  - databases The database limit to assign created servers (optional)
     *  - allocations The allocations limit to assign created servers (optional)
     *  - backups The backups limit to assign created servers (optional)
     *  - * Egg variables should also be submitted
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function add(array $packageLists, array $vars = null)
    {
        // Set missing checkboxes
        $checkboxes = ['dedicated_ip'];
        foreach ($checkboxes as $checkbox) {
            if (empty($vars['meta'][$checkbox])) {
                $vars['meta'][$checkbox] = '0';
            }
        }

        // Get the rule helper
        Loader::load(dirname(__FILE__) . DS . 'pterodactyl_rule.php');
        $rule_helper = new PterodactylRule();

        $rules = $this->getRules($packageLists, $vars);
        // Get egg variable rules
        if (isset($vars['meta']['egg_id']) && isset($packageLists['eggs'][$vars['meta']['egg_id']])) {
            $egg = $packageLists['eggs'][$vars['meta']['egg_id']];
            foreach ($egg->attributes->relationships->variables->data as $envVariable) {
                $fieldName = strtolower($envVariable->attributes->env_variable);
                $rules['meta[' . $fieldName . ']'] = $rule_helper->parseEggVariable($envVariable);

                foreach ($rules['meta[' . $fieldName . ']'] as $rule) {
                    if (array_key_exists('if_set', $rule)
                        && $rule['if_set'] == true
                        && empty($vars['meta'][$fieldName])
                    ) {
                        unset($rules['meta[' . $fieldName . ']']);
                    }
                }
            }
        }

        // Set rules to validate input data
        $this->Input->setRules($rules);

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
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param array $packageLists An array of package fields lists from the API including:
     *
     *  - locations A list of location IDs
     *  - nests A list of nest IDs
     *  - eggs A list of eggs keyed by their IDs
     * @param stdClass $vars A stdClass object representing a set of post fields (optional) including:
     *
     *  - location_id The ID of the Location to automatically deploy servers to.
     *  - nest_id The ID of the Nest to use for created servers.
     *  - egg_id The ID of the Egg to use for created servers.
     *  - dedicated_ip Whether to assign a dedicated ip to created servers (optional)
     *  - port_range Comma seperated port ranges to assign to created servers (optional)
     *  - pack_id The ID of the Pack to use for created servers (optional)
     *  - memory The memory limit in megabytes to assign created servers
     *  - swap The swap memory limit in megabytes to assign created servers
     *  - cpu The CPU limit in percentage to assign created servers
     *  - disk The disk space limit in megabytes to assign created servers
     *  - io The block IO adjustment number to assign created servers
     *  - startup The custom startup command to assign created servers (optional)
     *  - image The custom docker image to assign created servers (optional)
     *  - databases The database limit to assign created servers (optional)
     *  - allocations The allocations limit to assign created servers (optional)
     *  - backups The backups limit to assign created servers (optional)
     *  - * Egg variables should also be submitted
     * @return ModuleFields A ModuleFields object, containing the fields
     *  to render as well as any additional HTML markup to include
     */
    public function getFields(array $packageLists, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Set js to refetch options when the nest or egg is changed
        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					// Re-fetch module options to pull in eggs and egg variables
                    // when a nest or egg respectively is selected
					$('#Pterodactyl_nest_id, #Pterodactyl_egg_id').change(function() {
						fetchModuleOptions();
					});
				});
			</script>
		");

        // Set the select fields
        $selectFields = [
            'location_id' => isset($packageLists['locations']) ? $packageLists['locations'] : [],
            'nest_id' => isset($packageLists['nests']) ? $packageLists['nests'] : [],
            'egg_id' => isset($packageLists['eggs'])
                ? array_combine(array_keys($packageLists['eggs']), array_keys($packageLists['eggs']))
                : [],
        ];
        foreach ($selectFields as $selectField => $list) {
            // Create the select field label
            $field = $fields->label(
                Language::_('PterodactylPackage.package_fields.' . $selectField, true),
                'Pterodactyl_' . $selectField
            );
            // Set the select field
            $field->attach(
                $fields->fieldSelect(
                    'meta[' . $selectField . ']',
                    $list,
                    (isset($vars->meta[$selectField]) ? $vars->meta[$selectField] : null),
                    ['id' => 'Pterodactyl_' . $selectField]
                )
            );
            // Add a tooltip based on the select field
            $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.' . $selectField, true));
            $field->attach($tooltip);
            $fields->setField($field);
        }

        // Set the Dedicated IP
        $dedicatedIp = $fields->label(
            Language::_('PterodactylPackage.package_fields.dedicated_ip', true),
            'Pterodactyl_dedicated_ip',
            ['class' => 'inline']
        );
        $dedicatedIp->attach(
            $fields->fieldCheckbox(
                'meta[dedicated_ip]',
                '1',
                (isset($vars->meta['dedicated_ip']) ? $vars->meta['dedicated_ip'] : null) == 1,
                ['id' => 'Pterodactyl_dedicated_ip', 'class' => 'inline']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.dedicated_ip', true));
        $dedicatedIp->attach($tooltip);
        $fields->setField($dedicatedIp);

        // Set text fields
        $textFields = [
            'port_range', 'pack_id', 'memory', 'swap', 'cpu', 'disk',
            'io', 'startup', 'image', 'databases', 'allocations', 'backups'
        ];
        foreach ($textFields as $textField) {
            // Create the text field label
            $field = $fields->label(
                Language::_('PterodactylPackage.package_fields.' . $textField, true),
                'Pterodactyl_' . $textField
            );
            // Set the text field
            $field->attach(
                $fields->fieldText(
                    'meta[' . $textField . ']',
                    (isset($vars->meta[$textField]) ? $vars->meta[$textField] : null),
                    ['id' => 'Pterodactyl_' . $textField]
                )
            );
            // Add a tooltip based on the text field
            $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.' . $textField, true));
            $field->attach($tooltip);
            $fields->setField($field);
        }

        // Return standard package fields and attach any applicable egg fields
        return isset($packageLists['eggs'][(isset($vars->meta['egg_id']) ? $vars->meta['egg_id'] : null)])
            ? $this->attachEggFields($packageLists['eggs'][(isset($vars->meta['egg_id']) ? $vars->meta['egg_id'] : null)], $fields, $vars)
            : $fields;
    }

    /**
     * Attaches package fields for each environment from the Pterodactyle egg
     *
     * @param stdClass $pterodactyl_egg The egg to pull environment variables from
     * @param ModuleFields $fields The fields object to attach the new fields to
     * @param stdClass $vars A stdClass object representing a set of post fields (optional)
     * @return ModuleFields The new fields object with all environment variable fields attached
     */
    private function attachEggFields($pterodactyl_egg, $fields, $vars = null)
    {
        if (!is_object($pterodactyl_egg)) {
            return $fields;
        }

        // Get service fields from the egg
        foreach ($pterodactyl_egg->attributes->relationships->variables->data as $env_variable) {
            // Create a label for the environment variable
            $label = strpos($env_variable->attributes->rules, 'required') === 0
                ? $env_variable->attributes->name
                : Language::_('PterodactylPackage.package_fields.optional', true, $env_variable->attributes->name);
            $key = strtolower($env_variable->attributes->env_variable);
            $field = $fields->label($label, $key);
            // Create the environment variable field and attach to the label
            $field->attach(
                $fields->fieldText(
                    'meta[' . $key . ']',
                    (isset($vars->meta[$key]) ? $vars->meta[$key] : $env_variable->attributes->default_value),
                    ['id' => $key]
                )
            );
            // Add tooltip based on the description from Pterodactyl
            $tooltip = $fields->tooltip(
                $env_variable->attributes->description
                . ' '
                . Language::_('PterodactylPackage.package_fields.tooltip.display', true)
            );
            // Create a field for whether to display the environment variable to the client
            $checkboxKey = $key . '_display';
            $field->attach($tooltip);
            $field->attach(
                $fields->fieldCheckbox(
                    'meta[' . $checkboxKey . ']',
                    '1',
                    (isset($vars->meta[$checkboxKey]) ? $vars->meta[$checkboxKey] : '0') == '1',
                    ['id' => $checkboxKey, 'class' => 'inline']
                )
            );
            // Set the label as a field
            $fields->setField($field);
        }

        return $fields;
    }

    /**
     * Builds and returns the rules required to add/edit a package
     *
     * @param array $packageLists An array of package fields lists from the API including:
     *
     *  - locations A list of location IDs
     *  - nests A list of nest IDs
     *  - eggs A list of eggs keyed by their IDs
     * @param array $vars An array of key/value data pairs
     *
     *  - location_id The ID of the Location to automatically deploy servers to.
     *  - nest_id The ID of the Nest to use for created servers.
     *  - egg_id The ID of the Egg to use for created servers.
     *  - dedicated_ip Whether to assign a dedicated ip to created servers (optional)
     *  - port_range Comma seperated port ranges to assign to created servers (optional)
     *  - pack_id The ID of the Pack to use for created servers (optional)
     *  - memory The memory limit in megabytes to assign created servers
     *  - swap The swap memory limit in megabytes to assign created servers
     *  - cpu The CPU limit in percentage to assign created servers
     *  - disk The disk space limit in megabytes to assign created servers
     *  - io The block IO adjustment number to assign created servers
     *  - startup The custom startup command to assign created servers (optional)
     *  - image The custom docker image to assign created servers (optional)
     *  - databases The database limit to assign created servers (optional)
     *  - allocations The allocations limit to assign created servers (optional)
     *  - backups The backups limit to assign created servers (optional)
     *  - * Egg variables should also be submitted
     * @return array An array of Input rules suitable for Input::setRules()
     */
    public function getRules(array $packageLists, array $vars)
    {
        $rules = [
            'meta[location_id]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[location_id].format', true)
                ],
                'valid' => [
                    'rule' => [
                        'array_key_exists',
                        isset($packageLists['locations']) ? $packageLists['locations'] : []
                    ],
                    'message' => Language::_('PterodactylPackage.!error.meta[location_id].valid', true)
                ]
            ],
            'meta[dedicated_ip]' => [
                'format' => [
                    'rule' => ['in_array', [0, 1]],
                    'message' => Language::_('PterodactylPackage.!error.meta[dedicated_ip].format', true)
                ]
            ],
            'meta[port_range]' => [
                'format' => [
                    'rule' => function ($portRanges) {
                        $ranges = explode(',', $portRanges);
                        foreach ($ranges as $range) {
                            if (!preg_match('/^[0-9]+\-[0-9]+$/', $range)) {
                                return false;
                            }
                        }

                        return true;
                    },
                    'message' => Language::_('PterodactylPackage.!error.meta[port_range].format', true)
                ]
            ],
            'meta[nest_id]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[nest_id].format', true)
                ],
                'valid' => [
                    'rule' => [
                        'array_key_exists',
                        isset($packageLists['nests']) ? $packageLists['nests'] : []
                    ],
                    'message' => Language::_('PterodactylPackage.!error.meta[nest_id].valid', true)
                ]
            ],
            'meta[egg_id]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[egg_id].format', true)
                ],
                'valid' => [
                    'rule' => [
                        'array_key_exists',
                        isset($packageLists['eggs']) ? $packageLists['eggs'] : []
                    ],
                    'message' => Language::_('PterodactylPackage.!error.meta[egg_id].valid', true)
                ]
            ],
            'meta[pack_id]' => [
                'format' => [
                    'rule' => function ($packId) {
                        return empty($packId) || preg_match('/^[0-9]+$/', $packId);
                    },
                    'message' => Language::_('PterodactylPackage.!error.meta[pack_id].format', true)
                ]
            ],
            'meta[memory]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[memory].format', true)
                ]
            ],
            'meta[swap]' => [
                'format' => [
                    'rule' => ['matches', '/^(?:\-1|[0-9]+)$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[swap].format', true)
                ]
            ],
            'meta[cpu]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[cpu].format', true)
                ]
            ],
            'meta[disk]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[disk].format', true)
                ]
            ],
            'meta[io]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[io].format', true)
                ]
            ],
            'meta[image]' => [
                'length' => [
                    'rule' => ['maxLength', 255],
                    'message' => Language::_('PterodactylPackage.!error.meta[image].length', true)
                ]
            ],
            'meta[databases]' => [
                'format' => [
                    'rule' => function ($databaseLimit) {
                        return empty($databaseLimit) || preg_match('/^[0-9]+$/', $databaseLimit);
                    },
                    'message' => Language::_('PterodactylPackage.!error.meta[databases].format', true)
                ]
            ],
            'meta[allocations]' => [
                'format' => [
                    'rule' => function ($allocationLimit) {
                        return empty($allocationLimit) || preg_match('/^[0-9]+$/', $allocationLimit);
                    },
                    'message' => Language::_('PterodactylPackage.!error.meta[allocations].format', true)
                ]
            ],
            'meta[backups]' => [
                'format' => [
                    'rule' => function ($backupLimit) {
                        return empty($backupLimit) || preg_match('/^[0-9]+$/', $backupLimit);
                    },
                    'message' => Language::_('PterodactylPackage.!error.meta[backups].format', true)
                ]
            ],
        ];

        return $rules;
    }
}
