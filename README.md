# PRODA Authorisation API Library
This library manages the activation of devices on PRODA, the Department of Human Services authorisation API.

## Installation
1. Install via composer/repository
2. Publish resources via the ServiceProvider

## Config
Certain values are autoloaded from the config file (i.e. clientId and OrganisationId)

## Storage
Device uses Laravel's Eloquent models/migrations to store in a database

## Activate a New Device
A device needs the following to be activated:
1. A client ID (from the vendor web interface)
2. An organisation ID (or "RA Number") from the PRODA web interface
3. A valid device name (added as a device via the PRODA web interface)
4. A "One Time Activation Code", when the device is added to the PRODA web interface

Then:
1. Create a new Device
2. Set the properties on the device
3. Activate

(see tests for documentation for now)

```
$device->activate();
```

## Obtain an Access Token for an Activated Device
```
$accessToken = $device->getAccessToken();
```

This access token can then be used for requests to "Relaying Parties" e.g. TCSI.

## Refresh the SSL Key for an already Activated Device
```
$device->refresh();
```

## Command for refreshing expiring device keys

## Command for expiring device notifications
