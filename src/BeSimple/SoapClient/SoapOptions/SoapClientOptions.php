<?php

namespace BeSimple\SoapClient\SoapOptions;

use BeSimple\SoapClient\Curl\CurlOptions;
use BeSimple\SoapClient\SoapServerAuthentication\SoapServerAuthenticationBasic;
use BeSimple\SoapClient\SoapServerAuthentication\SoapServerAuthenticationDigest;
use BeSimple\SoapClient\SoapServerAuthentication\SoapServerAuthenticationInterface;
use BeSimple\SoapClient\SoapServerProxy\SoapServerProxy;

class SoapClientOptions
{
    const SOAP_CLIENT_TRACE_ON = true;
    const SOAP_CLIENT_TRACE_OFF = false;
    const SOAP_CLIENT_EXCEPTIONS_ON = true;
    const SOAP_CLIENT_EXCEPTIONS_OFF = false;
    const SOAP_CLIENT_COMPRESSION_NONE = CurlOptions::SOAP_COMPRESSION_NONE;
    const SOAP_CLIENT_COMPRESSION_GZIP = CurlOptions::SOAP_COMPRESSION_GZIP;
    const SOAP_CLIENT_COMPRESSION_DEFLATE = CurlOptions::SOAP_COMPRESSION_DEFLATE;

    private $trace;
    private $exceptions;
    private $userAgent;
    private $compression;
    private $authentication;
    private $proxy;

    /**
     * @param bool $trace = SoapClientOptions::SOAP_CLIENT_TRACE_ON|SoapClientOptions::SOAP_CLIENT_TRACE_OFF
     * @param bool $exceptions = SoapClientOptions::SOAP_CLIENT_EXCEPTIONS_ON|SoapClientOptions::SOAP_CLIENT_EXCEPTIONS_OFF
     * @param string $userAgent
     * @param int $compression = SoapClientOptions::SOAP_CLIENT_COMPRESSION_NONE|SoapClientOptions::SOAP_CLIENT_COMPRESSION_GZIP|SoapClientOptions::SOAP_CLIENT_COMPRESSION_DEFLATE
     * @param SoapServerAuthenticationInterface $authentication = null
     * @param SoapServerProxy $proxy = null
     */
    public function __construct($trace, $exceptions, $userAgent, $compression = null, SoapServerAuthenticationInterface $authentication = null, SoapServerProxy $proxy = null)
    {
        $this->trace = $trace;
        $this->exceptions = $exceptions;
        $this->userAgent = $userAgent;
        $this->compression = $compression;
        $this->authentication = $authentication;
        $this->proxy = $proxy;
    }

    public function getTrace()
    {
        return $this->trace;
    }

    public function getExceptions()
    {
        return $this->exceptions;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function hasCompression()
    {
        return $this->compression !== self::SOAP_CLIENT_COMPRESSION_NONE;
    }

    public function getCompression()
    {
        return $this->compression;
    }

    public function hasAuthentication()
    {
        return $this->authentication !== null;
    }

    public function hasAuthenticationBasic()
    {
        return $this->hasAuthentication() && $this->getAuthentication() instanceof SoapServerAuthenticationBasic;
    }

    public function hasAuthenticationDigest()
    {
        return $this->hasAuthentication() && $this->getAuthentication() instanceof SoapServerAuthenticationDigest;
    }

    public function hasProxy()
    {
        return $this->proxy !== null;
    }

    public function getAuthentication()
    {
        return $this->authentication;
    }

    public function getProxy()
    {
        return $this->proxy;
    }

    public function toArray()
    {
        $optionsAsArray = [
            'trace' => $this->getTrace(),
            'exceptions' => $this->getExceptions(),
            'user_agent' => $this->getUserAgent(),
        ];
        if ($this->hasCompression()) {
            $optionsAsArray['compression'] = $this->getCompression();
        }
        if ($this->hasAuthentication()) {
            $optionsAsArray += $this->getAuthentication()->toArray();
        }
        if ($this->hasProxy()) {
            $optionsAsArray += $this->getProxy()->toArray();
        }

        return $optionsAsArray;
    }
}
