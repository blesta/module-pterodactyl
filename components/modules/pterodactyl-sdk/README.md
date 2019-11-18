# Pterodactyl SDK

## THIS IS CURRENTLY UNDER DEVELOPMENT.

This is an SDK that integrates with APIs from [Pterodactyl](https://pterodactyl.com/) [Docs](https://dashflo.net/docs/api/pterodactyl/v1/#introduction).

## Installation
You can install the SDK via composer:

```
composer require blesta/pterodactyl-sdk
```

## Usage

### Initializing the API

```
$useSsl = true;
$api = new \Blesta\PterodactylSDK\PterodactylApi('API_KEY', 'PANEL_URL', $useSsl);
```
One thing to note is that the Pterodactyl Panel has two API's:  The application API and the account API.
The account API/key should be used to access the Client requestor, while the application API/key should be used for all other requestors.

### Loading a Requestor
Requestor definition: A class that the SDK uses to access API endpoints of a particular type (e.g. Servers, Nests, or Locations).

The SDK utilizes PHP's __get() magic method so requestors can just be accessed as a property of the loaded api.  For example:

```
$clientRequestor = $api->Client;
```

### Calling an Endpoint

All endpoints exist as a method on a requestor.  For example, the application/locations/ endpoint would be called as follows:

```
$locationResponse = $api->Locations->getAll();
```
Or to call the same endpoint but with post parameters to add a new location, it would look like this:

```
$locationResponse = $api->Locations->add($parameters);
```

### Using a response object

The response object is meant to make it easier to Access the returned data and recognize when you have encountered errors.  It has the following methods:

```
$locationResponse->raw(); // Useful for logging the full response from Pterodactyl
$locationResponse->response(); // The json decoded data returned
$locationResponse->headers(); // An array of http headers returned
$locationResponse->status(); // The status code returned
$locationResponse->errors(); // An array of the errors returned
```

The most used methods are typically response() and errors()

```
$errors = $locationResponse->errors();
if (empty($errors)) {
    $locations = $locationResponse->response();
    ...
    ...
    ...
}
```