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
    'provider'   => $provider['id'],
    'api_key'    => 'test_api_key',
    'api_secret' => 'test_api_secret',
]);

// get provider credentials
$providerCredentials = $client->getProviderCredentials();

// create parcel
$parcel = $client->createParcel([
    'provider_credential'   => $providerCredential['id'],
    'tracking_id'           => 'S10000000000000',
    'postalcode'            => '1000AA',
]);

// get parcel
$parcel = $client->getParcel($parcel['id']);

// get parcels
$parcels = $client->getParcels();

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
// $this->deleteAccount($account['id']);

// delete parcel
// $client->deleteParcel($parcel['id']);

// delete provider credential
// $client->deleteProviderCredential($providerCredential['id']);

// delete webhook
// $client->deleteWebhook($webhook['id']);