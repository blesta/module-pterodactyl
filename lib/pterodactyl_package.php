<?php
/**
 * Pterodactyl Package actions
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
     * @param array $packageLists An array of package fields lists from the API
     * @param array $vars An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing: (optional)
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
        $checkboxes = [
            'dedicated_ip',
        ];
        foreach ($checkboxes as $checkbox) {
            if (empty($vars['meta'][$checkbox])) {
                $vars['meta'][$checkbox] = '0';
            }
        }

        // Set rules to validate input data
        $this->Input->setRules($this->getRules($packageLists, $vars));

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
     * @param array $packageLists An array of package fields lists from the API
     * @param stdClass $vars A stdClass object representing a set of post fields (optional)
     * @return ModuleFields A ModuleFields object, containing the fields
     *  to render as well as any additional HTML markup to include
     */
    public function getFields($packageLists, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					// Re-fetch module options to pull in eggs when a nest is selected
					$('#Pterodactyl_nest_id').change(function() {
						fetchModuleOptions();
					});
				});
			</script>
		");

        // Set the Location ID
        $locationId = $fields->label(
            Language::_('PterodactylPackage.package_fields.location_id', true),
            'Pterodactyl_location_id'
        );
        $locationId->attach(
            $fields->fieldSelect(
                'meta[location_id]',
                isset($packageLists['locations']) ? $packageLists['locations'] : [],
                $this->Html->ifSet($vars->meta['location_id']),
                ['id' => 'Pterodactyl_location_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.location_id', true));
        $locationId->attach($tooltip);
        $fields->setField($locationId);

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
                $this->Html->ifSet($vars->meta['dedicated_ip']) == 1,
                ['id' => 'Pterodactyl_dedicated_ip', 'class' => 'inline']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.dedicated_ip', true));
        $dedicatedIp->attach($tooltip);
        $fields->setField($dedicatedIp);

        // Set the Port Range
        $portRange = $fields->label(
            Language::_('PterodactylPackage.package_fields.port_range', true),
            'Pterodactyl_port_range'
        );
        $portRange->attach(
            $fields->fieldText(
                'meta[port_range]',
                $this->Html->ifSet($vars->meta['port_range']),
                ['id' => 'Pterodactyl_port_range']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.port_range', true));
        $portRange->attach($tooltip);
        $fields->setField($portRange);

        // Set the Nest ID
        $nestId = $fields->label(
            Language::_('PterodactylPackage.package_fields.nest_id', true),
            'Pterodactyl_nest_id'
        );
        $nestId->attach(
            $fields->fieldSelect(
                'meta[nest_id]',
                isset($packageLists['nests']) ? $packageLists['nests'] : [],
                $this->Html->ifSet($vars->meta['nest_id']),
                ['id' => 'Pterodactyl_nest_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.nest_id', true));
        $nestId->attach($tooltip);
        $fields->setField($nestId);

        // Set the Egg ID
        $eggId = $fields->label(Language::_('PterodactylPackage.package_fields.egg_id', true), 'Pterodactyl_egg_id');
        $eggId->attach(
            $fields->fieldSelect(
                'meta[egg_id]',
                isset($packageLists['eggs']) ? $packageLists['eggs'] : [],
                $this->Html->ifSet($vars->meta['egg_id']),
                ['id' => 'Pterodactyl_egg_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.egg_id', true));
        $eggId->attach($tooltip);
        $fields->setField($eggId);

        // Set the Pack ID
        $packId = $fields->label(
            Language::_('PterodactylPackage.package_fields.pack_id', true),
            'Pterodactyl_pack_id'
        );
        $packId->attach(
            $fields->fieldText(
                'meta[pack_id]',
                $this->Html->ifSet($vars->meta['pack_id']),
                ['id' => 'Pterodactyl_pack_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.pack_id', true));
        $packId->attach($tooltip);
        $fields->setField($packId);


        // Set the memory (in MB)
        $memory = $fields->label(Language::_('PterodactylPackage.package_fields.memory', true), 'Pterodactyl_memory');
        $memory->attach(
            $fields->fieldText(
                'meta[memory]',
                $this->Html->ifSet($vars->meta['memory']),
                ['id' => 'Pterodactyl_memory']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.memory', true));
        $memory->attach($tooltip);
        $fields->setField($memory);

        // Set the swap (in MB)
        $swap = $fields->label(Language::_('PterodactylPackage.package_fields.swap', true), 'Pterodactyl_swap');
        $swap->attach(
            $fields->fieldText(
                'meta[swap]',
                $this->Html->ifSet($vars->meta['swap']),
                ['id' => 'Pterodactyl_swap']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.swap', true));
        $swap->attach($tooltip);
        $fields->setField($swap);

        // Set the CPU Limit (%)
        $cpu = $fields->label(Language::_('PterodactylPackage.package_fields.cpu', true), 'Pterodactyl_cpu');
        $cpu->attach(
            $fields->fieldText(
                'meta[cpu]',
                $this->Html->ifSet($vars->meta['cpu']),
                ['id' => 'Pterodactyl_cpu']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.cpu', true));
        $cpu->attach($tooltip);
        $fields->setField($cpu);

        // Set the Disk MB
        $disk = $fields->label(Language::_('PterodactylPackage.package_fields.disk', true), 'Pterodactyl_disk');
        $disk->attach(
            $fields->fieldText(
                'meta[disk]',
                $this->Html->ifSet($vars->meta['disk']),
                ['id' => 'Pterodactyl_disk']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.disk', true));
        $disk->attach($tooltip);
        $fields->setField($disk);

        // Set the Block IO Weight
        $io = $fields->label(Language::_('PterodactylPackage.package_fields.io', true), 'Pterodactyl_io');
        $io->attach(
            $fields->fieldText(
                'meta[io]',
                $this->Html->ifSet($vars->meta['io'], 500),
                ['id' => 'Pterodactyl_io']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.io', true));
        $io->attach($tooltip);
        $fields->setField($io);

        // Set the startup command
        $startup = $fields->label(
            Language::_('PterodactylPackage.package_fields.startup', true),
            'Pterodactyl_startup'
        );
        $startup->attach(
            $fields->fieldText(
                'meta[startup]',
                $this->Html->ifSet($vars->meta['startup']),
                ['id' => 'Pterodactyl_startup']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.startup', true));
        $startup->attach($tooltip);
        $fields->setField($startup);

        // Set the image
        $image = $fields->label(Language::_('PterodactylPackage.package_fields.image', true), 'Pterodactyl_image');
        $image->attach(
            $fields->fieldText(
                'meta[image]',
                $this->Html->ifSet($vars->meta['image']),
                ['id' => 'Pterodactyl_image']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.image', true));
        $image->attach($tooltip);
        $fields->setField($image);

        // Set the server databases
        $databases = $fields->label(
            Language::_('PterodactylPackage.package_fields.databases', true),
            'Pterodactyl_databases'
        );
        $databases->attach(
            $fields->fieldText(
                'meta[databases]',
                $this->Html->ifSet($vars->meta['databases']),
                ['id' => 'Pterodactyl_databases']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.databases', true));
        $databases->attach($tooltip);
        $fields->setField($databases);

        ##
        # TODO It is possible we should add a field for allocations
        ##

        return $fields;
    }

    /**
     * Builds and returns the rules required to add/edit a package
     *
     * @param array $packageLists An array of package fields lists from the API
     * @param array $vars An array of key/value data pairs
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
                        if (!empty($portRanges)) {
                            $ranges = explode(',', $portRanges);
                            foreach ($ranges as $range) {
                                if (!preg_match('/^[0-9]+\-[0-9]+$/', $range)) {
                                    return false;
                                }
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
                    'rule' => ['matches', '/^[0-9]+$/'],
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
        ];

        return $rules;
    }
}
