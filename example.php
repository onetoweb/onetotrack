<?php

require 'vendor/autoload.php';

use Onetoweb\Onetotrack\Client;

$username = 'username';
$password = 'password';
$apiKey = 'api_key';
$apiSecret = 'api_secret';

$client = new Client($username, $password, $apiKey, $apiSecret);

// create account
$account = $client->createAccount([
    'name' => 'account name'
]);

// set account id (required for all parcel, provider​/credential & webhook calls)
$client->setAccountId($account['id']);

// get accounts
$accounts = $client->getAccounts();

// get providers
$providers = $client->getProviders();
$provider = $providers[0];

// create provider credentials
$providerCredential = $client->createProviderCredential([
    'provider' => $provider['id'],
    'api_key' => 'test_api_key',
    'api_secret' => 'test_api_secret',
    'username' => 'username',
    'password' => 'password',
    'params' => [
        'foo' => 'bar'
    ]
]);

// update provider credentials
$providerCredentials = $client->updateProviderCredential($providerCredential['id'], $providerCredential);

// get provider credentials
$providerCredentials = $client->getProviderCredentials();

// create parcel
$parcel = $client->createParcel([
    'provider_credential' => $providerCredential['id'],
    'tracking_id' => 'S10000000000000',
    'postalcode' => '1000AA',
]);

// get parcel
$parcel = $client->getParcel($parcel['id']);

// get parcels
$parcels = $client->getParcels([
    'page' => 1,
    'order_by' => [
        'created_at' => 'desc',
    ],
    'filter' => [
        'delivered' => true,
    ],
]);

// create shipment 
$shipment = $client->createShipment([
    'provider_credential' => $providerCredential['id'],
    'weight' => 10,
    'length' => 10,
    'width' => 10,
    'height' => 10,
    'reference' => 'reference',
    'carrier' => 'carrier',
    'type' => 'type',
    'reciever' => [
        'name1' => 'name 1', // used as address contact where applicable
        'name2' => 'name 2', // used for company name where applicable
        'name3' => 'name 3',
        'street' => 'street',
        'number' => '1',
        'number_extension' => '',
        'postalcode' => '1000AA',
        'city' => 'city',
        'country' => 'NL',
        'email' => 'info@example.com',
        'phone' => '0123456789',
    ],
    'sender' => [
        'name1' => 'name 1', // used as address contact where applicable
        'name2' => 'name 2', // used for company name where applicable
        'name3' => 'name 3',
        'street' => 'street',
        'number' => '1',
        'number_extension' => '',
        'postalcode' => '1000AA',
        'city' => 'city',
        'country' => 'NL',
        'email' => 'info@example.com',
        'phone' => '0123456789',
    ],
], [
    'create_tracker' => true,
]);

// get shipments
$shipments = $client->getShipments([
    'page' => 1,
    'order_by' => [
        'created_at' => 'desc',
    ],
    'filter' => [
        'created' => true,
    ],
]);

// get shipment
$shipment = $client->getShipment($shipment['id']);

// get webhook events
$webhookEvents = $client->getWebhookEvents();

// create webhooks
$webhook = $client->createWebhook([
    'event' => 'on_parcel_change',
    'callback' => 'https://www.example.com/'
]);

// get webhooks
$webhooks = $client->getWebhooks();

// delete account
// $client->deleteAccount($account['id']);

// delete parcel
// $client->deleteParcel($parcel['id']);

// delete shipment
// $client->deleteShipment($shipment['id']);

// delete provider credential
// $client->deleteProviderCredential($providerCredential['id']);

// delete webhook
// $client->deleteWebhook($webhook['id']);