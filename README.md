# Pterodactyl Module

[![Build Status](https://travis-ci.org/blesta/module-pterodactyl.svg?branch=master)](https://travis-ci.org/blesta/module-pterodactyl) [![Coverage Status](https://coveralls.io/repos/github/blesta/module-pterodactyl/badge.svg?branch=master)](https://coveralls.io/github/blesta/module-pterodactyl?branch=master)

This is a module for Blesta that integrates with [Pterodactyl](https://pterodactyl.com/).

## Install the Module

1. You can install the module via composer:

    ```
    composer require blesta/pterodactyl
    ```

2. OR upload the source code to a /components/modules/pterodactyl/ directory within
your Blesta installation path.

    For example:

    ```
    /var/www/html/blesta/components/modules/pterodactyl/
    ```

3. Log in to your admin Blesta account and navigate to
> Settings > Modules

4. Find the Pterodactyl module and click the "Install" button to install it

5. Add a server with your Pterodactyl credentials

6. You're done!

### Blesta Compatibility

|Blesta Version|Module Version|
|--------------|--------------|
|< v4.9.0|v1.3.0|
|>= v4.9.0|v1.4.0+|
|>= v5.0.0|v1.6.0+|
