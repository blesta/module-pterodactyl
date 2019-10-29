<?php
/**
 * Pterodactyl Package actions
 *
 * @package blesta
 * @subpackage blesta.components.modules.Pterodactyl.lib
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
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
     * Fetches the module keys usable in email tags
     *
     * @return array A list of module email tags
     */
    public function getEmailTags()
    {
        return [];
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
    public function add(array $vars = null)
    {
    }

    /**
     * Retrieves a list of JAR directories
     *
     * @param array A key/value array of JAR directories and their names
     */
    public function getJarDirectories()
    {
        return [
            'daemon' => Language::_('PterodactylPackage.package_fields.jardir_daemon', true),
            'server' => Language::_('PterodactylPackage.package_fields.jardir_server', true),
            'server_base' => Language::_('PterodactylPackage.package_fields.jardir_server_base', true)
        ];
    }

    /**
     * Retrieves a list of default roles
     *
     * @param array A key/value array of default roles and their names
     */
    public function getDefaultRoles()
    {
        return [
            '0' => Language::_('PterodactylPackage.package_fields.default_level_0', true),
            '10' => Language::_('PterodactylPackage.package_fields.default_level_10', true),
            '20' => Language::_('PterodactylPackage.package_fields.default_level_20', true),
            '30' => Language::_('PterodactylPackage.package_fields.default_level_30', true)
        ];
    }

    /**
     * Retrieves a list of server visibility options
     *
     * @param array A key/value array of visibility options and their names
     */
    public function getServerVisibilityOptions()
    {
        return [
            '0' => Language::_('PterodactylPackage.package_fields.server_visibility_0', true),
            '1' => Language::_('PterodactylPackage.package_fields.server_visibility_1', true),
            '2' => Language::_('PterodactylPackage.package_fields.server_visibility_2', true)
        ];
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param array $package_lists A stdClass object representing a set of post fields
     * @param stdClass $vars A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields
     *  to render as well as any additional HTML markup to include
     */
    public function getFields($package_lists, $vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $fields->setHtml("
			<script type=\"text/javascript\">
				$(document).ready(function() {
					// Re-fetch module options to pull in eggs
					$('#Pterodactyl_nest_id').change(function() {
						fetchModuleOptions();
					});
				});
			</script>
		");

        // Set the server name
        $server_name = $fields->label(
            Language::_('PterodactylPackage.package_fields.server_name', true),
            'Pterodactyl_server_name'
        );
        $server_name->attach(
            $fields->fieldText(
                'meta[server_name]',
                $this->Html->ifSet($vars->meta['server_name'], 'Minecraft Server'),
                ['id' => 'Pterodactyl_server_name']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.server_name', true));
        $server_name->attach($tooltip);
        $fields->setField($server_name);

        // Set the Location ID
        $location_id = $fields->label(
            Language::_('PterodactylPackage.package_fields.location_id', true),
            'Pterodactyl_location_id'
        );
        $location_id->attach(
            $fields->fieldSelect(
                'meta[location_id]',
                isset($package_lists['locations']) ? $package_lists['locations'] : [],
                $this->Html->ifSet($vars->meta['location_id']),
                ['id' => 'Pterodactyl_location_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.location_id', true));
        $location_id->attach($tooltip);
        $fields->setField($location_id);

        // Set the Dedicated IP
        $dedicated_ip = $fields->label(
            Language::_('PterodactylPackage.package_fields.dedicated_ip', true),
            'Pterodactyl_dedicated_ip'
        );
        $dedicated_ip->attach(
            $fields->fieldText(
                'meta[dedicated_ip]',
                $this->Html->ifSet($vars->meta['dedicated_ip']),
                ['id' => 'Pterodactyl_dedicated_ip']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.dedicated_ip', true));
        $dedicated_ip->attach($tooltip);
        $fields->setField($dedicated_ip);

        // Set the Port Range
        $port_range = $fields->label(
            Language::_('PterodactylPackage.package_fields.port_range', true),
            'Pterodactyl_port_range'
        );
        $port_range->attach(
            $fields->fieldText(
                'meta[port_range]',
                $this->Html->ifSet($vars->meta['port_range']),
                ['id' => 'Pterodactyl_port_range']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.port_range', true));
        $port_range->attach($tooltip);
        $fields->setField($port_range);

        // Set the Nest ID
        $nest_id = $fields->label(
            Language::_('PterodactylPackage.package_fields.nest_id', true),
            'Pterodactyl_nest_id'
        );
        $nest_id->attach(
            $fields->fieldSelect(
                'meta[nest_id]',
                isset($package_lists['nests']) ? $package_lists['nests'] : [],
                $this->Html->ifSet($vars->meta['nest_id']),
                ['id' => 'Pterodactyl_nest_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.nest_id', true));
        $nest_id->attach($tooltip);
        $fields->setField($nest_id);

        // Set the Egg ID
        $egg_id = $fields->label(Language::_('PterodactylPackage.package_fields.egg_id', true), 'Pterodactyl_egg_id');
        $egg_id->attach(
            $fields->fieldSelect(
                'meta[egg_id]',
                isset($package_lists['eggs']) ? $package_lists['eggs'] : [],
                $this->Html->ifSet($vars->meta['egg_id']),
                ['id' => 'Pterodactyl_egg_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.egg_id', true));
        $egg_id->attach($tooltip);
        $fields->setField($egg_id);

        // Set the Pack ID
        $pack_id = $fields->label(
            Language::_('PterodactylPackage.package_fields.pack_id', true),
            'Pterodactyl_pack_id'
        );
        $pack_id->attach(
            $fields->fieldText(
                'meta[pack_id]',
                $this->Html->ifSet($vars->meta['pack_id']),
                ['id' => 'Pterodactyl_pack_id']
            )
        );
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.pack_id', true));
        $pack_id->attach($tooltip);
        $fields->setField($pack_id);


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
        $tooltip = $fields->tooltip(Language::_('PterodactylPackage.package_fields.tooltip.io', true));
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

        return $fields;
    }

    /**
     * Builds and returns the rules required to add/edit a package
     *
     * @param array $package_lists An array of package fields lists from the API
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    public function getRules(array $package_lists, array $vars)
    {
        ##
        # TODO Finish Validation Rules
        ##
        $rules = [
            'meta[server_name]' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('PterodactylPackage.!error.meta[server_name].format', true)
                ]
            ],
            'meta[location_id]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[location_id].format', true)
                ],
                'valid' => [
                    'rule' => [
                        'array_key_exists',
                        isset($package_lists['locations']) ? $package_lists['locations'] : []
                    ],
                    'message' => Language::_('PterodactylPackage.!error.meta[location_id].valid', true)
                ]
            ],
//            'meta[dedicated_ip]' => [
//                'format' => [
//                    'rule' => [/* Validate hostname */],
//                    'message' => Language::_('PterodactylPackage.!error.meta[dedicated_ip].format', true)
//                ]
//            ],
//            'meta[port_range]' => [
//                'format' => [
//                    'rule' => [/* Validate range */],
//                    'message' => Language::_('PterodactylPackage.!error.meta[port_range].format', true)
//                ]
//            ],
            'meta[nest_id]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[nest_id].format', true)
                ],
                'valid' => [
                    'rule' => [
                        'array_key_exists',
                        isset($package_lists['nests']) ? $package_lists['nests'] : []
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
                        isset($package_lists['eggs']) ? $package_lists['eggs'] : []
                    ],
                    'message' => Language::_('PterodactylPackage.!error.meta[egg_id].valid', true)
                ]
            ],
            'meta[pack_id]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('PterodactylPackage.!error.meta[pack_id].format', true)
                ]
            ],
//            'meta[memory]' => [
//                'format' => [
//                    'rule' => ['matches', '/^[0-9]+$/'],
//                    'message' => Language::_('PterodactylPackage.!error.meta[memory].format', true)
//                ]
//            ],
//            'meta[swap]' => [
//                'format' => [
//                    'rule' => [/* Validate swap */],
//                    'message' => Language::_('PterodactylPackage.!error.meta[swap].format', true)
//                ]
//            ],
//            'meta[cpu]' => [
//                'format' => [
//                    'rule' => [/* Validate cpu */],
//                    'message' => Language::_('PterodactylPackage.!error.meta[cpu].format', true)
//                ]
//            ],
//            'meta[disk]' => [
//                'format' => [
//                    'rule' => [/* Validate disk */],
//                    'message' => Language::_('PterodactylPackage.!error.meta[disk].format', true)
//                ]
//            ],
//            'meta[io]' => [
//                'format' => [
//                    'rule' => [/* Validate io */],
//                    'message' => Language::_('PterodactylPackage.!error.meta[io].format', true)
//                ]
//            ],
//            'meta[startup]' => [
//                'format' => [
//                    'rule' => [/* Validate startup */],
//                    'message' => Language::_('PterodactylPackage.!error.meta[startup].format', true)
//                ]
//            ]
        ];

        return $rules;
    }
}
