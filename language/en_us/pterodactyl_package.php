<?php
// Errors
$lang['PterodactylPackage.!error.meta[server_name].format'] = 'Please set a name for the server.';
$lang['PterodactylPackage.!error.meta[location_id].format'] = '';
$lang['PterodactylPackage.!error.meta[dedicated_ip].format'] = '';
$lang['PterodactylPackage.!error.meta[port_range].format'] = '';
$lang['PterodactylPackage.!error.meta[nest_id].format'] = '';
$lang['PterodactylPackage.!error.meta[egg_id].format'] = '';
$lang['PterodactylPackage.!error.meta[pack_id].format'] = '';
$lang['PterodactylPackage.!error.meta[memory].format'] = '';
$lang['PterodactylPackage.!error.meta[swap].format'] = '';
$lang['PterodactylPackage.!error.meta[cpu].format'] = '';
$lang['PterodactylPackage.!error.meta[disk].format'] = '';
$lang['PterodactylPackage.!error.meta[io].format'] = '';
$lang['PterodactylPackage.!error.meta[startup].format'] = '';
$lang['PterodactylPackage.!error.meta[image].format'] = '';
$lang['PterodactylPackage.!error.meta[databases].format'] = '';


// Package fields
$lang['PterodactylPackage.package_fields.location_id'] = 'Location ID';
$lang['PterodactylPackage.package_fields.tooltip.location_id'] = 'ID of the Location to automatically deploy to.';

$lang['PterodactylPackage.package_fields.io'] = 'Block IO Weight';
$lang['PterodactylPackage.package_fields.tooltip.io'] = 'Block IO Adjustment number (10-1000)';

$lang['PterodactylPackage.package_fields.port_range'] = 'Port Range (optional)';
$lang['PterodactylPackage.package_fields.tooltip.port_range'] = 'Port ranges seperated by comma to assign to the server (Example: 25565-25570,25580-25590)';

$lang['PterodactylPackage.package_fields.dedicated_ip'] = 'Dedicated IP (optional)';
$lang['PterodactylPackage.package_fields.tooltip.dedicated_ip'] = 'Assign dedicated ip to the server';

$lang['PterodactylPackage.package_fields.nest_id'] = 'Nest ID';
$lang['PterodactylPackage.package_fields.tooltip.nest_id'] = 'ID of the Nest for the server to use';

$lang['PterodactylPackage.package_fields.startup'] = 'Startup (optional)';
$lang['PterodactylPackage.package_fields.tooltip.startup'] = 'Custom startup command to assign to the created server.';

$lang['PterodactylPackage.package_fields.image'] = 'Image (optional)';
$lang['PterodactylPackage.package_fields.tooltip.image'] = 'Custom Docker image to assign to the created server.';

$lang['PterodactylPackage.package_fields.pack_id'] = 'Pack ID (optional)';
$lang['PterodactylPackage.package_fields.tooltip.pack_id'] = 'ID of the Pack to install the server with';

$lang['PterodactylPackage.package_fields.egg_id'] = 'Egg ID';
$lang['PterodactylPackage.package_fields.tooltip.egg_id'] = 'ID of the Egg for the server to use';

$lang['PterodactylPackage.package_fields.databases'] = 'Databases (optional)';
$lang['PterodactylPackage.package_fields.tooltip.databases'] = 'Client will be able to create this amount of databases for their server';

$lang['PterodactylPackage.package_fields.server_name'] = 'Server Name (optional)';
$lang['PterodactylPackage.package_fields.tooltip.server_name'] = 'The name of the server as shown on the panel.';

$lang['PterodactylPackage.package_fields.memory'] = 'Memory (MB)';
$lang['PterodactylPackage.package_fields.tooltip.memory'] = 'Amount of Memory to assign to the created server.';

$lang['PterodactylPackage.package_fields.swap'] = 'Swap (MB)';
$lang['PterodactylPackage.package_fields.tooltip.swap'] = 'Amount of Swap to assign to the created server.';

$lang['PterodactylPackage.package_fields.cpu'] = 'CPU Limit (%)';
$lang['PterodactylPackage.package_fields.tooltip.cpu'] = 'Amount of CPU to assign to the created server.';

$lang['PterodactylPackage.package_fields.disk'] = 'Disk Space (MB)';
$lang['PterodactylPackage.package_fields.tooltip.disk'] = 'Amount of Disk Space to assign to the created server.';

$lang['PterodactylPackage.package_fields.jarfile'] = 'JAR File';
$lang['PterodactylPackage.package_fields.tooltip.jarfile'] = 'Leave blank to use the default JAR file.';
$lang['PterodactylPackage.package_fields.jardir'] = 'Look for JARs in the following directory';
$lang['PterodactylPackage.package_fields.tooltip.jardir'] = 'If not using the daemon JAR directory, Pterodactyl should be running in multi-user mode.';
$lang['PterodactylPackage.package_fields.jardir_daemon'] = 'Daemon JAR directory';
$lang['PterodactylPackage.package_fields.jardir_server'] = 'Server JAR directory';
$lang['PterodactylPackage.package_fields.jardir_server_base'] = 'Server base directory';
$lang['PterodactylPackage.package_fields.user_jar'] = 'Owner Selectable JAR';
$lang['PterodactylPackage.package_fields.user_name'] = 'Owner Can Set Name';
$lang['PterodactylPackage.package_fields.tooltip.user_name'] = "Sets whether the owner can define or change the server's name.";
$lang['PterodactylPackage.package_fields.user_schedule'] = 'Owner Can Schedule Tasks';
$lang['PterodactylPackage.package_fields.tooltip.user_schedule'] = 'Sets whether the owner can create scheduled tasks and change the autosave setting.';
$lang['PterodactylPackage.package_fields.user_ftp'] = 'Owner Can Manage FTP';
$lang['PterodactylPackage.package_fields.tooltip.user_ftp'] = 'Sets whether the owner can give FTP access to other users.';
$lang['PterodactylPackage.package_fields.user_visibility'] = 'Owner Can Set Visibility';
$lang['PterodactylPackage.package_fields.tooltip.user_visibility'] = 'Sets whether the owner can change the server visibility and the Default Role.';
$lang['PterodactylPackage.package_fields.default_level'] = 'Default Role';
$lang['PterodactylPackage.package_fields.tooltip.default_level'] = 'Select which role players will be assigned when they first connect. Use No Access for white-listing.';
$lang['PterodactylPackage.package_fields.default_level_0'] = 'No Access';
$lang['PterodactylPackage.package_fields.default_level_10'] = 'Guest';
$lang['PterodactylPackage.package_fields.default_level_20'] = 'User';
$lang['PterodactylPackage.package_fields.default_level_30'] = 'Moderator';
$lang['PterodactylPackage.package_fields.autostart'] = 'Start Server';
$lang['PterodactylPackage.package_fields.tooltip.autostart'] = 'Automatically starts the server when Pterodactyl restarts.';
$lang['PterodactylPackage.package_fields.create_ftp'] = 'Create FTP Account';
$lang['PterodactylPackage.package_fields.tooltip.create_ftp'] = 'Automatically creates an FTP account.';
$lang['PterodactylPackage.package_fields.server_visibility'] = 'Server Visibility';
$lang['PterodactylPackage.package_fields.tooltip.server_visibility'] = 'Sets the visibility of the server in the Pterodactyl server list.';
$lang['PterodactylPackage.package_fields.server_visibility_0'] = 'Owner only';
$lang['PterodactylPackage.package_fields.server_visibility_1'] = 'By Default Role';
$lang['PterodactylPackage.package_fields.server_visibility_2'] = 'Users with Roles only';