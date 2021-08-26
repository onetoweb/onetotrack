<?php

namespace Onetoweb\Onetotrack;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Onetoweb\Onetotrack\Exception\{AuthenticationException, RequestException, AccountIdNotSetException};
use Psr\Http\Message\ResponseInterface;

/**
 * Onetotrack Api Client
 * 
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb B.V.
 * @link https://onetotrack.nujob.nl/api/doc/
 * @version 1.1.8
 */
class Client
{
    const BASE_URL = 'https://onetotrack.nujob.nl';
    
    /**
     * @var string
     */
    private $username;
    
    /**
     * @var string
     */
    private $password;
    
    /**
     * @var string
     */
    private $apiKey;
    
    /**
     * @var string
     */
    private $apiSecret;
    
    /**
     * @var Token
     */
    private $token;
    
    /**
     * @var GuzzleClient
     */
    private $client;
    
    /**
     * @var string
     */
    private $accountId;
    
    /**
     * @var ResponseInterface
     */
    private $lastResponse;
    
    /**
     * @param string $username
     * @param string $password
     * @param string $apiKey
     * @param string $apiSecret
     */
    public function __construct(string $username, string $password, string $apiKey, string $apiSecret)
    {
        $this->username = $username;
        $this->password = $password;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        
        $this->client = new GuzzleClient([
            'base_uri' => self::BASE_URL,
        ]);
    }
    
    /**
     * Get token
     * 
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
    }
    
    /**
     * @param string $accountId
     */
    public function setAccountId(string $accountId)
    {
        $this->accountId = $accountId;
    }
    
    /**
     * @throws AccountIdNotSetException if account id is not set 
     * 
     * @return string
     */
    private function getAccountId() : string
    {
        $trace = debug_backtrace();
        
        if ($this->accountId == null) {
            
            if(isset($trace[1]['function'])) {
                throw new AccountIdNotSetException("{$trace[1]['function']} requires account id to be set");
            } else {
                throw new AccountIdNotSetException('account id is not set');
            }
        }
        
        return $this->accountId;
    }
    
    /**
     * Get access token
     * 
     * @throws AuthenticationException
     */
    public function getAccessToken()
    {
        try {
            
            $options = [
                RequestOptions::HEADERS => [
                    'Cache-Control' => 'no-store, private',
                    'Connection' => 'close',
                    'Content-Type' => 'application/json',
                ],
                RequestOptions::JSON => [
                    'grant_type'    => 'password',
                    'username'      => $this->username,
                    'password'      => $this->password,
                    'client_id'     => $this->apiKey,
                    'client_secret' => $this->apiSecret,
                ]
            ];
            
            $result = $this->client->request('POST', '/oauth/v2/token', $options);
            
            $contents = $result->getBody()->getContents();
            
            $token = json_decode($contents, true);
            
            $this->token = new Token($token);
            
        } catch (GuzzleRequestException $guzzleRequestException) {
            
            // store last repsonse
            if ($guzzleRequestException->hasResponse()) {
                $this->lastResponse = $guzzleRequestException->getResponse();
            }
            
            $this->handleGuzzleRequestException($guzzleRequestException, function($message, $code, $previousException) {
                throw new AuthenticationException($message, $code, $previousException);
            });
            
        }
    }
    
    /**
     * Send request
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data = [] (optional)
     * @param array $query = [] (optional)
     *
     * @throws RequestException
     *
     * @return array
     */
    private function request(string $method, string $endpoint, array $data = null, array $query = [])
    {
        if ($this->getToken() == null or $this->getToken()->hasExpired()) {
            $this->getAccessToken();
        }
        
        try {
            
            $options = [
                RequestOptions::HEADERS => [
                    'Cache-Control' => 'no-cache',
                    'Connection' => 'close',
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->getToken()->getAccessToken()}"
                ],
            ];
            
            if(in_array($method, ['POST', 'PUT', 'PATCH']) and count($data) > 0) {
                $options[RequestOptions::JSON] = $data;
            }
            
            if (count($query) > 0) {
                $endpoint .= '?'.http_build_query($query);
            }
            
            $result = $this->client->request($method, $endpoint, $options);
            
            $contents = $result->getBody()->getContents();
            
            return json_decode($contents, true);
            
        } catch (GuzzleRequestException $guzzleRequestException) {
            
            // store last repsonse
            if ($guzzleRequestException->hasResponse()) {
                $this->lastResponse = $guzzleRequestException->getResponse();
            }
            
            $this->handleGuzzleRequestException($guzzleRequestException, function($message, $code, $previousException) {
                throw new RequestException($message, $code, $previousException);
            });
            
        }
    }
    
    /**
     * Get last response
     * 
     * @return ResponseInterface 
     */
    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }
    
    /**
     * Handle GuzzleRequestException
     * 
     * @param GuzzleRequestException $guzzleRequestException
     * @param callable $callback
     */
    private function handleGuzzleRequestException(GuzzleRequestException $guzzleRequestException, callable $callback)
    {
        if ($guzzleRequestException->hasResponse()) {
            
            $exception = json_decode($guzzleRequestException->getResponse()->getBody()->getContents(), true);
            
            if (isset($exception['errors']['children'])) {
                
                $errors = [];
                if ($exception['message'] != '') {
                    $errors['error'] = $exception['message'];
                }
                
                if (isset($exception['errors']['errors']) and count($exception['errors']['errors']) > 0) {
                    $errors['errors'] = $exception['errors']['errors'];
                }
                
                $createFormErrorMessage = function($form, $fieldPrefix = null) use (&$errors, &$createFormErrorMessage) {
                    
                    foreach ($form  as $field => $data) {
                        
                        if (isset($data['errors'])) {
                            
                            foreach ($data['errors'] as $errorMessage) {
                                
                                if ($fieldPrefix !== null) {
                                    $key = "$fieldPrefix.$field";
                                } else {
                                    $key = $field;
                                }
                                
                                $errors[$key][] = $errorMessage;
                            }
                        }
                        
                        if (isset($data['children'])) {
                            $createFormErrorMessage($data['children'], $field);
                        }
                    }
                    
                };
                
                $createFormErrorMessage($exception['errors']['children']);
                
                $message = json_encode($errors);
                $code = $guzzleRequestException->getCode();
                
            } elseif (isset($exception['message']) and isset($exception['code'])) {
                
                $message = $exception['message'];
                $code = $exception['code'];
                
            } elseif (isset($exception['error']) and isset($exception['error_description'])) {
                
                $message = implode(', ', [$exception['error'], $exception['error_description']]);
                $code = $guzzleRequestException->getCode();
                
            } else {
                
                $message = $guzzleRequestException->getMessage();
                $code = $guzzleRequestException->getCode();
                
            }
            
        } else {
            
            $message = $guzzleRequestException->getMessage();
            $code = $guzzleRequestException->getCode();
            
        }
        
        $callback($message, $code, $guzzleRequestException);
    }
    
    /**
     * Send a GET request
     *
     * @param string $endpoint
     * @param array $query = []
     *
     * @return array
     */
    public function get(string $endpoint, array $query = [])
    {
        return $this->request('GET', $endpoint, [], $query);
    }
    
    /**
     * Send a POST request
     *
     * @param string $endpoint
     * @param array $data
     * @param array $query = []
     *
     * @return array
     */
    public function post(string $endpoint, array $data, array $query = [])
    {
        return $this->request('POST', $endpoint, $data, $query);
    }
    
    /**
     * Send a PUT request
     *
     * @param string $endpoint
     * @param array $data
     * @param array $query = []
     *
     * @return array
     */
    public function put(string $endpoint, array $data, array $query = [])
    {
        return $this->request('PUT', $endpoint, $data, $query);
    }
    
    /**
     * Send a PATCH request
     *
     * @param string $endpoint
     * @param array $data
     * @param array $query = [] 
     *
     * @return array
     */
    public function patch(string $endpoint, array $data, array $query = [])
    {
        return $this->request('PATCH', $endpoint, $data, $query);
    }
    
    /**
     * Send a DELETE request
     *
     * @param string $endpoint
     * @param array $query = []
     *
     * @return array
     */
    public function delete(string $endpoint, array $query = [])
    {
        return $this->request('DELETE', $endpoint, [], $query);
    }
    
    /**
     * Get accounts
     *
     * @return array
     */
    public function getAccounts()
    {
        return $this->get('api/account');
    }
    
    /**
     * Create account
     *
     * @return array
     */
    public function createAccount(array $data)
    {
        return $this->post('api/account', $data);
    }
    
    /**
     * Delete account
     *
     * @return array
     */
    public function deleteAccount(string $id)
    {
        return $this->delete("api/account/$id");
    }
    
    /**
     * Get providers
     * 
     * @return array
     */
    public function getProviders()
    {
        return $this->get('api/provider');
    }
    
    /**
     * Get provider credentials
     * 
     * @return array
     */
    public function getProviderCredentials()
    {
        $accountId = $this->getAccountId();
        
        return $this->get("api/account/$accountId/provider/credential");
    }
    
    /**
     * Get provider credentials
     *
     * @return array
     */
    public function getProviderCredential(string $id)
    {
        $accountId = $this->getAccountId();
        
        return $this->get("api/account/$accountId/provider/credential/$id");
    }
    
    /**
     * Create provider credential
     * 
     * @param array $data
     * 
     * @return array
     */
    public function createProviderCredential(array $data)
    {
        $accountId = $this->getAccountId();
        
        return $this->post("api/account/$accountId/provider/credential", $data);
    }
    
    /**
     * Update provider credential
     *
     * @param string $id
     * @param array $data
     *
     * @return array
     */
    public function updateProviderCredential(string $id, array $data)
    {
        $accountId = $this->getAccountId();
        
        return $this->patch("api/account/$accountId/provider/credential/$id", $data);
    }
    
    /**
     * Delete provider credential
     * 
     * @param string $id
     * 
     * @return null
     */
    public function deleteProviderCredential(string $id)
    {
        $accountId = $this->getAccountId();
        
        return $this->delete("api/account/$accountId/provider/credential/$id");
    }
    
    /**
     * Get pickup locations
     *
     * @param array $data
     * @param array $query = [] (optional)
     *
     * @return array
     */
    public function getPickupLocations(array $data, array $query = [])
    {
        $accountId = $this->getAccountId();
        
        return $this->post("api/account/$accountId/pickup/location", $data, $query);
    }
    
    /**
     * Get parcels
     * 
     * @param array $query = [] (optional)
     * 
     * @return array
     */
    public function getParcels(array $query = [])
    {
        $accountId = $this->getAccountId();
        
        return $this->get("api/account/$accountId/parcel", $query);
    }
    
    /**
     * Get parcel
     *
     * @param string $id
     *
     * @return array
     */
    public function getParcel(string $id)
    {
        $accountId = $this->getAccountId();
        
        return $this->get("api/account/$accountId/parcel/$id");
    }
    
    /**
     * Create parcel
     * 
     * @param array $data
     *
     * @return array
     */
    public function createParcel(array $data)
    {
        $accountId = $this->getAccountId();
        
        return $this->post("api/account/$accountId/parcel", $data);
    }
    
    /**
     * Delete parcel
     * 
     * @param string $id
     *
     * @return null
     */
    public function deleteParcel(string $id)
    {
        $accountId = $this->getAccountId();
        
        return $this->delete("api/account/$accountId/parcel/$id");
    }
    
    /**
     * Get shipments
     *
     * @param array $query = [] (optional)
     *
     * @return array
     */
    public function getShipments(array $query = [])
    {
        $accountId = $this->getAccountId();
        
        return $this->get("api/account/$accountId/shipment", $query);
    }
    
    /**
     * Get shipment
     *
     * @param string $id
     *
     * @return array
     */
    public function getShipment(string $id)
    {
        $accountId = $this->getAccountId();
        
        return $this->get("api/account/$accountId/shipment/$id");
    }
    
    /**
     * Create shipment
     *
     * @param array $data
     * @param array $query
     *
     * @return array
     */
    public function createShipment(array $data, array $query = [])
    {
        $accountId = $this->getAccountId();
        
        return $this->post("api/account/$accountId/shipment", $data, $query);
    }
    
    /**
     * Delete shipment
     *
     * @param array $id
     *
     * @return array
     */
    public function deleteShipment(string $id)
    {
        $accountId = $this->getAccountId();
        
        return $this->delete("api/account/$accountId/shipment/$id");
    }
    
    /**
     * Get webhook events
     * 
     * @return array
     */
    public function getWebhookEvents()
    {
        return $this->get('api/webhook/events');
    }
    
    /**
     * Get webhooks
     * 
     * @return array
     */
    public function getWebhooks()
    {
        $accountId = $this->getAccountId();
        
        return $this->get("api/account/$accountId/webhook");
    }
    
    /**
     * Create webhook
     * 
     * @param array $data
     *
     * @return array
     */
    public function createWebhook(array $data)
    {
        $accountId = $this->getAccountId();
        
        return $this->post("api/account/$accountId/webhook", $data);
    }
    
    /**
     * Delete webhook
     * 
     * @param string $id
     *
     * @return null
     */
    public function deleteWebhook(string $id)
    {
        $accountId = $this->getAccountId();
        
        return $this->delete("api/account/$accountId/webhook/$id");
    }
}