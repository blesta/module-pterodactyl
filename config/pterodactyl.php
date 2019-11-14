<?php
// Polling interval for how often to refresh page content after an action is performed on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Pterodactyl.page_refresh_rate_fast', '5000');

// Polling interval for how often to refresh page content on pages that support it
// Note: Set to number of milliseconds (1000 = 1 second)
Configure::set('Pterodactyl.page_refresh_rate', '30000');
