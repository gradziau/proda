<?php

return [

    'organisation_id' => env('PRODA_ORGANISATION_ID'),
    'device_name' => env('PRODA_DEVICE_NAME'),
    'client_id' => env('PRODA_CLIENT_ID'),

    'urls' => [
        /**
         * The URL for activating a 'B2G Device'
         * e.g. 'https://test.5.rsp.humanservices.gov.au/piaweb/api/b2b/%s/devices/%s/jwk'
         * First parameter is the API Version Number (e.g. 'v1')
         * Second parameter is the Device Name
         * HTTP PUT
         */
        'activate_device' => env('PRODA_URL_ACTIVATE_DEVICE'),

        /**
         * The URL for refreshing a public RSA key for a 'B2G Device'
         * First parameter is the API Version Number (e.g. 'v1')
         * Second parameter is the Organisation ID
         * Third parameter is the Device Name
         * HTTP PUT
         */
        'refresh_device_key' => env('PRODA_URL_REFRESH_DEVICE'),

        /**
         * The URL for generating an access token
         * HTTP POST
         */
        'authorisation_service_request' => env('PRODA_URL_AUTHORISATION_REQUEST'),
    ],

    /**
     * This is the location where the private key is stored. Appended to the 'storage/app' folder.
     */
    'private_key_location' => env('PRODA_PRIVATE_KEY_LOCATION', 'proda/private.pem'),

];
