<?php

namespace BeSimple\SoapClient\SoapServerProxy;

class SoapServerProxy
{
    const PROXY_AUTHENTICATION_TYPE_NONE = null;
    const PROXY_AUTHENTICATION_TYPE_BASIC = \CURLAUTH_BASIC;
    const PROXY_AUTHENTICATION_TYPE_NTLM = \CURLAUTH_NTLM;

    private $host;
    private $port;
    private $login;
    private $password;
    private $authenticationType;

    /**
     * @param string $host
     * @param int $port
     * @param string $login = null
     * @param string $password = null
     * @param int $authenticationType = null|SoapServerProxy::PROXY_AUTHENTICATION_TYPE_BASIC|SoapServerProxy::PROXY_AUTHENTICATION_TYPE_NTLM
     */
    public function __construct($host, $port, $login = null, $password = null, $authenticationType = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->authenticationType = $authenticationType;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function hasCredentials()
    {
        return $this->login !== null;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function hasAuthenticationType()
    {
        return $this->authenticationType !== self::PROXY_AUTHENTICATION_TYPE_NONE;
    }

    public function getAuthenticationType()
    {
        return $this->authenticationType;
    }

    public function toArray()
    {
        $proxyAsArray = [
            'proxy_host' => $this->getHost(),
            'proxy_port' => $this->getPort(),
        ];
        if ($this->hasCredentials()) {
            $proxyAsArray['proxy_login'] = $this->getLogin();
            $proxyAsArray['proxy_password'] = $this->getPassword();
        }
        if ($this->hasAuthenticationType()) {
            $proxyAsArray['proxy_auth'] = $this->getAuthenticationType();
        }

        return $proxyAsArray;
    }
}
