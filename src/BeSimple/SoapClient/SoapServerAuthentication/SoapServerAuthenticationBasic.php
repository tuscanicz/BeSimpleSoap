<?php

namespace BeSimple\SoapClient\SoapServerAuthentication;

class SoapServerAuthenticationBasic implements SoapServerAuthenticationInterface
{
    private $login;
    private $password;

    public function __construct($login, $password)
    {
        $this->login = $login;
        $this->password = $password;
    }

    public function getAuthentication()
    {
        return \SOAP_AUTHENTICATION_BASIC;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function toArray()
    {
        return [
            'authentication' => $this->getAuthentication(),
            'login' => $this->getLogin(),
            'password' => $this->getPassword(),
        ];
    }
}
