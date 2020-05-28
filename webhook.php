<?php

require 'vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Onetoweb\Onetotrack\Authentication;

$apiSecret = 'api_secret';

// example handling webhooks using the symfony http kernel
// https://symfony.com/doc/current/components/http_kernel.html

$request = Request::createFromGlobals();
if(Authentication::authenticate($request->getContent(),  $apiSecret)) {
    
    $content = json_decode($request->getContent(), true);
    
    // return a response 200 on success
    
    $response = new Response();
    $response->setStatusCode(200);
    $response->send();
    
    
} else {
    
    // if a response other then 200 is returned the webhook will make 5 more attempts with increasing delay
    
    $response = new Response();
    $response->setStatusCode(400);
    $response->send();
}

// alternatively example using basic php

$body = file_get_contents('php://input');
if(Authentication::authenticate($body,  $apiSecret)) {
    
    $content = json_decode($body, true);
     
    // return a response 200 on success
    http_response_code(200);
    
} else {
    
    // if a response other then 200 is returned the webhook will make 5 more attempts with increasing delay
    http_response_code(400);
}