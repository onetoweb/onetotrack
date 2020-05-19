<?php

namespace Onetoweb\Onetotrack;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Onetoweb\Onetotrack\Exception\AuthenticationException;
use Onetoweb\Onetotrack\Exception\RequestException;

/**
 * Onetotrack api Client
 * 
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb B.V.
 * @link https://onetotrack.nujob.nl/api/doc/
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
            
            $this->handleGuzzleRequestException($guzzleRequestException, function($message, $code, $previousException) {
                throw new AuthenticationException($message, $code, $previousException);
            });
            
        }
    }
    
    /**
     * Send request
     *
     * @param string $method = 'GET'
     * @param string $endpoint
     * @param array $data = null (optional)
     *
     * @throws RequestException
     *
     * @return array
     */
    private function request(string $method = 'GET', string $endpoint, array $data = null)
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
            
            if(in_array($method, ['POST', 'PUT'])) {
                $options[RequestOptions::JSON] = $data;
            }
            
            $result = $this->client->request($method, $endpoint, $options);
            
            $contents = $result->getBody()->getContents();
            
            return json_decode($contents, true);
            
        } catch (GuzzleRequestException $guzzleRequestException) {
            
            $this->handleGuzzleRequestException($guzzleRequestException, function($message, $code, $previousException) {
                throw new RequestException($message, $code, $previousException);
            });
            
        }
    }
    
    /**
     * @param GuzzleRequestException $guzzleRequestException
     * @param callable $callback
     */
    private function handleGuzzleRequestException(GuzzleRequestException $guzzleRequestException, callable $callback)
    {
        if ($guzzleRequestException->hasResponse()) {
            
            $exception = json_decode($guzzleRequestException->getResponse()->getBody()->getContents(), true);
            
            if (isset($exception['message']) and isset($exception['code'])) {
                
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
     *
     * @return array
     */
    private function get($endpoint)
    {
        return $this->request('GET', $endpoint);
    }
    
    /**
     * Send a POST request
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return array
     */
    private function post($endpoint, $data)
    {
        return $this->request('POST', $endpoint, $data);
    }
    
    /**
     * Send a PUT request
     *
     * @param string $endpoint
     * @param array $data
     *
     * @return array
     */
    private function put($endpoint, $data)
    {
        return $this->request('PUT', $endpoint, $data);
    }
    
    /**
     * Send a DELETE request
     *
     * @param string $endpoint
     *
     * @return array
     */
    private function delete($endpoint)
    {
        return $this->request('DELETE', $endpoint);
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
        return $this->get('api/provider/credential');
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
        return $this->post('api/provider/credential', $data);
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
        return $this->delete("api/provider/credential/$id");
    }
    
    /**
     * Get parcels
     * 
     * @return array
     */
    public function getParcels()
    {
        return $this->get('api/parcel');
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
        return $this->get("api/parcel/$id");
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
        return $this->post('api/parcel', $data);
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
        return $this->delete("api/parcel/$id");
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
        return $this->get('api/webhook');
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
        return $this->post('api/webhook', $data);
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
        return $this->delete("api/webhook/$id");
    }
}