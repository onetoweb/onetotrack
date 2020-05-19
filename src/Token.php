<?php

namespace Onetoweb\Onetotrack;

/**
 * Onetotrack Token
 */
class Token
{
    /**
     * @var string
     */
    private $accessToken;
    
    /**
     * @var string
     */
    private $refreshToken;
    
    /**
     * @var string
     */
    private $tokenType;
    
    /**
     * @var string
     */
    private $scope;
    
    /**
     * @var string
     */
    private $expiresAt;
    
    /**
     * @param string $token
     */
    public function __construct($token)
    {
        $this->accessToken = $token['access_token'];
        $this->refreshToken = $token['refresh_token'];
        $this->tokenType = $token['token_type'];
        $this->scope = $token['scope'];
        
        # set expires at
        $this->expiresAt = new \DateTime();
        $this->expiresAt->setTimestamp($this->expiresAt->getTimestamp() + $token['expires_in'] + 300);
    }
    
    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
    
    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }
    
    /**
     * @return string
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }
    
    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }
    
    /**
     * @return \DateTime 
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }
    
    /**
     *  @return \DateInterval
     */
    public function getExpiresIn()
    {
        return $this->expiresAt->diff(new \DateTime());
    }
    
    /**
     * @return bool
     */
    public function hasExpired()
    {
        return (bool) ($this->expiresAt < new \DateTime());
    }
    
    /**
     * @return string
     */
    public function serialize()
    {
        return serialize([
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'scope' => $this->scope,
            'expires_in' => $this->getExpiresIn()
        ]);
    }
    
    
}