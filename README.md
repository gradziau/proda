# PRODA Authorisation API Library
![](https://laravel-og.beyondco.de/Proda.png?theme=light&packageManager=composer+require&packageName=gradziau%2Fproda&pattern=charlieBrown&style=style_2&description=PRODA+Authorisation+API+Library&md=1&showWatermark=1&fontSize=225px&images=folder-open&widths=450&heights=450)
This library manages the activation of devices on PRODA, the Department of Human Services authorisation API.

## Installation
1. For the moment, add this package as a repository to your ```composer.json``` file (not yet available on Packagist): 
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/gradziau/proda"
    }
]
```
2. Next, run this command to add the PRODA package to your project:
```
composer require gradzi-au/proda
```

3. Copy the config to your app's local config with the Publish command:
```
php artisan vendor:publish --provider="GradziAu\Proda\ServiceProvider"
```

## Storage
A ```Device``` is stored in the database using Laravel's Eloquent models/migrations.
An ```AccessToken``` is cached, currently for sixty minutes (3600 seconds), the default expiry from PRODA.

## Usage
### Activate a New Device
A device needs the following to be activated:
1. A client ID (from the vendor web interface)
2. An organisation ID (or "RA Number") from the PRODA web interface
3. A valid device name (added as a device via the PRODA web interface)
4. A "One Time Activation Code", when the device is added to the PRODA web interface

Then:
1. Create a new Device
2. Set the properties on the device
3. Activate

```
$device->activate();
```

### Obtain an Access Token for an Activated Device
```
$accessToken = $device->getAccessToken();
```

This access token can then be used for requests to "Relaying Parties" e.g. TCSI.

### Refresh the SSL Key for an already Activated Device
Device Keys stored with PRODA have an expiry date, and must be refreshed at regular intervals. To refresh the device key in your app:
```
$device->refresh();
```

### Command for Refreshing Expiring Device Keys
Device Keys stored with PRODA have an expiry date, and must be refreshed at regular intervals.
Setup a recurring job to run the following command to do this automatically:
```
php artisan proda:refresh-devices
```

### Command for Expiring Device Notifications
Sends an email notification using the email address defined in the config (proda.php):
```
notify_expiring_devices_notification => 'proda@example.com'
```
Setup a recurring job to run the following command:
```
php artisan proda:notify-expiring-devices
```

## Tests
The suite of tests has an additional composer dependency. Under tests/server you'll find an additional ```composer.json``` file.
This is setup so that a very basic [Lumen](https://lumen.laravel.com) app can be run to mock the server requests. There is a composer hook that also installs these packages when the base package is installed.
```
composer install
./vendor/bin/phpunit
```