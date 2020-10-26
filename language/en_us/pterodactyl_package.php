<?php
// Errors
$lang['PterodactylPackage.!error.meta[location_id].format'] = 'The location ID must be numeric.';
$lang['PterodactylPackage.!error.meta[location_id].valid'] = 'The location ID does not match any in Pterodactyl.';
$lang['PterodactylPackage.!error.meta[dedicated_ip].format'] = 'Whether to use a dedicated IP must be set to 1 or 0.';
$lang['PterodactylPackage.!error.meta[port_range].format'] = 'The port range most be in the format 25565-25570,25580-25590.';
$lang['PterodactylPackage.!error.meta[nest_id].format'] = 'The nest ID must be numeric.';
$lang['PterodactylPackage.!error.meta[nest_id].valid'] = 'The nest does not match any in Pterodactyl.';
$lang['PterodactylPackage.!error.meta[egg_id].format'] = 'The egg ID must be numeric.';
$lang['PterodactylPackage.!error.meta[egg_id].valid'] = 'The egg does not match any in Pterodactyl.';
$lang['PterodactylPackage.!error.meta[pack_id].format'] = 'The pack ID must be numeric.';
$lang['PterodactylPackage.!error.meta[memory].format'] = 'The memory amount must be numeric.';
$lang['PterodactylPackage.!error.meta[swap].format'] = 'The swap amount must be numeric.';
$lang['PterodactylPackage.!error.meta[cpu].format'] = 'The cpu percentage must be numeric.';
$lang['PterodactylPackage.!error.meta[disk].format'] = 'The disk space amount must be numeric.';
$lang['PterodactylPackage.!error.meta[io].format'] = 'The IO weight must be numeric.';
$lang['PterodactylPackage.!error.meta[image].length'] = 'The image path must be a most 255 characters.';
$lang['PterodactylPackage.!error.meta[databases].format'] = 'The number of databases must be numeric.';
$lang['PterodactylPackage.!error.meta[allocations].format'] = 'The number of allocations must be numeric.';
$lang['PterodactylPackage.!error.meta[backups].format'] = 'The number of backups must be numeric.';


// Package fields
$lang['PterodactylPackage.package_fields.location_id'] = 'Location';
$lang['PterodactylPackage.package_fields.tooltip.location_id'] = 'The Location to automatically deploy servers to.';

$lang['PterodactylPackage.package_fields.dedicated_ip'] = 'Dedicated IP (optional)';
$lang['PterodactylPackage.package_fields.tooltip.dedicated_ip'] = 'Assign dedicated ip to the created servers.';

// TODO Should this be a service field instead?
$lang['PterodactylPackage.package_fields.port_range'] = 'Port Range';
$lang['PterodactylPackage.package_fields.tooltip.port_range'] = 'Port ranges seperated by comma to assign to the server (Example: 25565-25570,25580-25590).';

$lang['PterodactylPackage.package_fields.nest_id'] = 'Nest';
$lang['PterodactylPackage.package_fields.tooltip.nest_id'] = 'The Nest for the server to use.';

$lang['PterodactylPackage.package_fields.egg_id'] = 'Egg ID';
$lang['PterodactylPackage.package_fields.tooltip.egg_id'] = 'ID of the Egg for the server to use.';

$lang['PterodactylPackage.package_fields.pack_id'] = 'Pack ID (optional)';
$lang['PterodactylPackage.package_fields.tooltip.pack_id'] = 'ID of the Pack to install the server with.';

$lang['PterodactylPackage.package_fields.memory'] = 'Memory (MB)';
$lang['PterodactylPackage.package_fields.tooltip.memory'] = 'Amount of Memory to assign to the created servers.';

$lang['PterodactylPackage.package_fields.swap'] = 'Swap (MB)';
$lang['PterodactylPackage.package_fields.tooltip.swap'] = 'Amount of Swap to assign to the created servers.';

$lang['PterodactylPackage.package_fields.cpu'] = 'CPU Limit (%)';
$lang['PterodactylPackage.package_fields.tooltip.cpu'] = 'Amount of CPU to assign to the created servers.';

$lang['PterodactylPackage.package_fields.disk'] = 'Disk Space (MB)';
$lang['PterodactylPackage.package_fields.tooltip.disk'] = 'Amount of Disk Space to assign to the created servers.';

$lang['PterodactylPackage.package_fields.io'] = 'Block IO Weight';
$lang['PterodactylPackage.package_fields.tooltip.io'] = 'Block IO Adjustment number (10-1000).';

$lang['PterodactylPackage.package_fields.startup'] = 'Startup (optional)';
$lang['PterodactylPackage.package_fields.tooltip.startup'] = 'Custom startup command to assign to the created servers (e.g. java -Xms128M -Xmx 1024M -jar server.jar).';

$lang['PterodactylPackage.package_fields.image'] = 'Image (optional)';
$lang['PterodactylPackage.package_fields.tooltip.image'] = 'Custom Docker image to assign to the created servers (e.g. quay.io/pterodactyl/core:java-glibc).';

$lang['PterodactylPackage.package_fields.databases'] = 'Database Limit (optional)';
$lang['PterodactylPackage.package_fields.tooltip.databases'] = 'The total number of databases a user is allowed to created servers. Leave blank to allow unlimited.';

$lang['PterodactylPackage.package_fields.allocations'] = 'Allocation Limit (optional)';
$lang['PterodactylPackage.package_fields.tooltip.allocations'] = 'The total number of allocations a user is allowed to created servers. Leave blank to allow unlimited.';

$lang['PterodactylPackage.package_fields.backups'] = 'Backup Limit (optional)';
$lang['PterodactylPackage.package_fields.tooltip.backups'] = 'The total number of backups a user is allowed for the created servers. Leave blank to allow unlimited.';

$lang['PterodactylPackage.package_fields.optional'] = '%1$s (Optional)'; // %1$s is the name of the field
$lang['PterodactylPackage.package_fields.tooltip.display'] = 'Check to allow clients to modify this value during service add/edit. Leave unchecked if you plan to use a configurable option for this field.';
