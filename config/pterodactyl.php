<?php
// Polling interval for how often to refresh page content after an action is performed on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Pterodactyl.page_refresh_rate_fast', '5000');

// Polling interval for how often to refresh page content on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Pterodactyl.page_refresh_rate', '30000');

// Email templates
Configure::set('Pterodactyl.email_templates', [
    'en_us' => [
        'lang' => 'en_us',
        'text' => 'Thank you for ordering your Minecraft Server!

Server Name: {service.server_name}
Server Username: {service.server_username}
Server Password: {service.server_password}
Server IP and Port: {service.server_ip}:{service.server_port}

Log into your account to start and manage your Minecraft Server! Be sure to start your Minecraft server for the first time from within Pterodactyl Panel so that you can agree to the Mojang EULA.',
        'html' => '<p>Thank you for ordering your Minecraft Server!</p>
<p>Server Name: {service.server_name}<br />Server Username: {service.server_username}<br />Server Password: {service.server_password}<br />Server IP and Port: {service.server_ip}:{service.server_port}</p>
<p>Log into your account to start and manage your Minecraft Server! Be sure to start your Minecraft server for the first time from within Pterodactyl Panel so that you can agree to the Mojang EULA.</p>'
    ]
]);
