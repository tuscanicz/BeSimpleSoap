<?php

namespace BeSimple\SoapClient\Curl\Http;

class HttpAuthenticationBasicOptions implements HttpAuthenticationInterface
{
    private $username;
    private $password;

    /**
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getAuthenticationType()
    {
        return HttpAuthenticationInterface::AUTHENTICATION_TYPE_BASIC;
    }
}
