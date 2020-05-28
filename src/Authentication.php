<?php

namespace Onetoweb\Onetotrack;

class Authentication
{
    public static function authenticate($content, string $apiSecret)
    {
        $matches = [];
        $matched = preg_match('/^{"content":(.*),"authentication":"(.*)"}$/', $content, $matches);
        
        if ($matched === 1 and isset($matches[1]) and isset($matches[2])) {
            return $matches[2] === hash_hmac('sha256', $matches[1], $apiSecret);
        }
        
        return false;
    }
}