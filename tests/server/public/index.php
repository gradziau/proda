<?php
/**
 *
 * This is heavily inspired by Adam Wathan's test server for Zttp: https://github.com/kitetail/zttp
 *
 * Changes by:
 *      Daniel Wood
 *      wood.danielg@gmail.com
 *      https://github.com/gradzi-au
 *
 * The idea is to keep all the server functions within this one file, hence raw functions over classes
 *
 */

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;

require_once __DIR__ . '/../vendor/autoload.php';

class Device
{
    public $name;
    public $otacActive;
    public $otac;
    public $active;
    public $key;
    public $clientId;
    public $organisationId;
    public $keyExpiry;
    public $deviceExpiry;
    public $accessToken;

    public function __construct($attributes)
    {
        $this->updateAttributes($attributes);
    }

    public static function fromToken($token)
    {
        $deviceName = static::getDeviceNameFromWebToken($token);
        return Device::fromStore($deviceName);
    }

    public static function getDeviceNameFromWebToken($token)
    {
        return Configuration::forUnsecuredSigner()
            ->parser()
            ->parse($token)
            ->claims()
            ->get('sub');
    }

    public static function fromStore($deviceName)
    {
        $deviceData = json_decode(Illuminate\Support\Facades\Cache::get($deviceName));
        return new static($deviceData);
    }

    protected function updateAttributes($attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
        return $this;
    }

    public function store()
    {
        Illuminate\Support\Facades\Cache::put($this->name, json_encode($this), 60);
    }

    public function activate($publicKey, $organisationId, $clientId)
    {
        $this->otac = null;
        $this->otacActive = null;
        $this->deviceExpiry = Carbon\Carbon::now()->addMonths(6)->format('Y-m-d h:m:s');
        $this->organisationId = $organisationId;
        $this->clientId = $clientId;
        $this->activateKey($publicKey);
    }

    public function refresh($publicKey)
    {
        $this->activateKey($publicKey);
    }

    private function activateKey($publicKey)
    {
        $this->keyExpiry = Carbon\Carbon::now()->addDays(11)->format('Y-m-d h:m:s');
        $this->active = true;
        $this->key = $publicKey;
    }
}

launchApp();

function launchApp()
{
    $app = setupApp();
    setupRoutes($app->router);
    $app->run();
}

function setupApp()
{
    $app = new Laravel\Lumen\Application(
        realpath(__DIR__ . '/../')
    );

    $app->withFacades();

    $app['config']->set('app.debug', true);

    return $app;
}

function setupRoutes($router)
{
    // Basic route to determine if the server is live and functioning correctly
    $router->get('/get', function () {
        return 'success';
    });

    // Create a 'one time activation code' for use with device activation
    $router->get('/otac/{deviceName}', function ($deviceName) {
        return getOneTimeActivationCode($deviceName);
    });

    // Retrieve the device from storage
    $router->get('/retrieve/{deviceName}', function ($deviceName) {
        return retrieveDevice($deviceName);
    });

    // Create an EXPIRED 'one time activation code' for use with device activation
    $router->get('/expiredotac/{deviceName}', function ($deviceName) {
        return getExpiredOneTimeActivationCode($deviceName);
    });

    // Get Authorisation Access Token Request
    $router->post('/mga/sps/oauth/oauth20/token', function (Request $request) {
        return getAuthorisationAccessToken($request);
    });

    // Activate Device Request
    $router->put('/piaweb/api/b2b/{version}/devices/{deviceName}/jwk', function (Request $request, $version, $deviceName) {
        return activateDeviceRequest($request, $version, $deviceName);
    });

    // Refresh Device Request
    $router->put('piaweb/api/b2b/{version}/orgs/{organisationId}/devices/{deviceName}/jwk',
        function (Request $request, $version, $organisationId, $deviceName) {
            return refreshDeviceRequest($request, $version, $organisationId, $deviceName);
        }
    );
}

function getOneTimeActivationCode($deviceName)
{
    return getActivationCodeForDevice($deviceName, true);
}

function retrieveDevice($deviceName)
{
    return response()->json([
        'code' => Device::fromStore($deviceName)
    ]);
}

function getExpiredOneTimeActivationCode($deviceName)
{
    return getActivationCodeForDevice($deviceName, false);
}

function getActivationCodeForDevice($deviceName, $active)
{
    $activationCode = Str::random();
    (new Device([
        'name' => $deviceName,
        'otac' => $activationCode,
        'otacActive' => $active,
    ]))->store();
    return $activationCode;
}

function getAuthorisationAccessToken(Request $request)
{
    $device = Device::fromToken($request->assertion);

    $response = checkForAuthorisationAccessTokenErrors($request, $device);
    if ($response) {
        return $response;
    }

    $device->accessToken = Str::random(40) . '.' . Str::random(40) . '.' . Str::random(40);
    $device->store();

    return buildAccessTokenSuccessResponse($device);
}

function checkForAuthorisationAccessTokenErrors(Request $request, $device)
{
    if ($request->client_id != $device->clientId) {
        return buildAccessTokenErrorResponse('mapping_error', 'Token was not valid (client id incorrect)');
    }

    if ($request->grant_type != 'urn:ietf:params:oauth:grant_type:jwt_bearer') {
        return buildAccessTokenErrorResponse('mapping_error', 'Token was not valid');
    }

    return null;
}

function buildAccessTokenErrorResponse($error, $errorDescription)
{
    return response()->json([
        'error' => $error,
        'error_description' => $errorDescription,
    ], 400);
}

function buildAccessTokenSuccessResponse($device)
{
    return response()->json([
        'token_type' => 'bearer',
        'access_token' => $device->accessToken,
        'expires_in' => 3600,
        'key_expiry' => $device->keyExpiry,
        'device_expiry' => $device->deviceExpiry,
        'scope' => '',
    ]);
}

function activateDeviceRequest(Request $request, $version, $deviceName)
{
    $device = Device::fromStore($deviceName);

    $response = checkForDeviceActivationErrors($request, $version, $device, $publicKey);
    if ($response) {
        return $response;
    }

    $device->activate($publicKey, $request->orgId, $request->header('dhs-productId'));
    $device->store();

    return buildActivateDeviceSuccessResponse($device);
}

function checkForDeviceActivationErrors(Request $request, $version, $device, &$publicKey)
{
    if ($version != 'v1') {
        return buildActivateDeviceErrorResponse($request, 'VER', 'Version number incorrect.', 400);
    };

    if (!$device) {
        return buildActivateDeviceErrorResponse($request, 'DE.4', 'Device not found.', 404);
    };

    if (!$device->otacActive) {
        return buildActivateDeviceErrorResponse($request, 'DE.7', 'Invalid OTAC provided.', 500);
    };

    if ($device->otac != $request->otac) {
        return buildActivateDeviceErrorResponse($request, 'DE.7', 'Invalid OTAC provided.', 500);
    }

    if (!array_keys_exist([
        'dhs-auditid',
        'dhs-auditIdType',
        'dhs-subjectId',
        'dhs-subjectIdType',
        'dhs-messageId',
        'dhs-correlationId',
        'dhs-productId',
    ], $request->headers->all())) {
        return buildActivateDeviceErrorResponse($request, 'HEADERS', 'Missing headers.', 400);
    };

    if (!$request->has('orgId')) {
        return buildActivateDeviceErrorResponse($request, 'DE.2', 'Organisation not found', 404);
    }

    if (!validJsonWebKey($request->key, $device, $publicKey)) {
        return buildActivateDeviceErrorResponse($request, 'JWK.1', 'Parse Exception', 400);
    }

    return null;
}

function refreshDeviceRequest(Request $request, $version, $organisationId, $deviceName)
{
    $device = Device::fromStore($deviceName);

    $response = checkForDeviceRefreshErrors($request, $version, $organisationId, $device, $publicKey);
    if ($response) {
        return $response;
    }

    $device->refresh($publicKey);
    $device->store();

    return buildActivateDeviceSuccessResponse($device);
}

function checkForDeviceRefreshErrors(Request $request, $version, $organisationId, $device, &$publicKey)
{
    if ($version != 'v1') {
        return buildActivateDeviceErrorResponse($request, 'VER', 'Version number incorrect.', 400);
    };

    if (!$device) {
        return buildActivateDeviceErrorResponse($request, 'DE.4', 'Device not found.', 404);
    };

    if ($device->organisationId != $organisationId) {
        return buildActivateDeviceErrorResponse($request, 'DE.2', 'Organisation not found', 404);
    }

    $token = trim(Str::after($request->header('Authorization'), 'Bearer '));
    if ($token != $device->accessToken) {
        return buildActivateDeviceErrorResponse($request, '111', 'Input error (invalid access token)', 400);
    }

    if (!validJsonWebKey($request->all(), $device, $publicKey)) {
        return buildActivateDeviceErrorResponse($request, 'JWK.1', 'Parse Exception ' . json_encode($request->all()), 400);
    }

    if ($publicKey == $device->key) {
        return buildActivateDeviceErrorResponse($request, 'JWK.9', 'Key in History', 400);
    }

    return null;
}

function validJsonWebKey($key, $device, &$publicKey)
{
    return (($key) &&
        (array_keys_exist(['kty', 'alg', 'use', 'kid', 'n', 'e'], $key)) &&
        ($key['kty'] == 'RSA') &&
        ($key['alg'] == 'RS256') &&
        ($key['use'] == 'sig') &&
        ($key['kid'] == $device->name) &&
        ($key['e'] == 'AQAB') &&
        (jsonWebKeyIsConvertable($key, $publicKey)));
}

function jsonWebKeyIsConvertable($key, &$publicKey)
{
    try {
        $publicKey = (new CoderCat\JWKToPEM\JWKConverter)->toPEM($key);
        $validKey = true;
    } catch (\Exception $e) {
        $validKey = false;
    }
    return $validKey;
}

function buildActivateDeviceErrorResponse(Request $request, $errorCode, $errorMessage, $httpStatusCode)
{
    return response()->json([
        'errors' => [
            'code' => $errorCode,
            'message' => $errorMessage,
        ],
        'reference' => 'N/A',
        'status' => $httpStatusCode,
        'url' => 'PUT ' . $request->url(),
    ], 400);
}

function buildActivateDeviceSuccessResponse($device)
{
    return response()->json([
        'orgId' => $device->organisationId,
        'deviceName' => $device->name,
        'deviceStatus' => '[ACTIVE]',
        'keyStatus' => '[ACTIVE]',
        'keyExpiry' => $device->keyExpiry,
        'deviceExpiry' => $device->deviceExpiry,
    ]);
}

function array_keys_exist(array $keysToFind, array $arrayToSearch)
{
    return !array_diff_ukey(array_flip($keysToFind), $arrayToSearch, 'strcasecmp');
}
