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
    const SOAP_CLIENT_AUTHENTICATION_NONE = null;
    const SOAP_CLIENT_PROXY_NONE = null;
    const SOAP_CLIENT_ENDPOINT_LOCATION_NONE = null;
    const SOAP_CLIENT_RESOLVE_REMOTE_INCLUDES_ON = true;
    const SOAP_CLIENT_RESOLVE_REMOTE_INCLUDES_OFF = false;

    private $trace;
    private $exceptions;
    private $userAgent;
    private $compression;
    private $authentication;
    private $proxy;
    private $location;
    private $resolveRemoteIncludes;

    /**
     * @param bool $trace = self::SOAP_CLIENT_TRACE_ON|self::SOAP_CLIENT_TRACE_OFF
     * @param bool $exceptions = self::SOAP_CLIENT_EXCEPTIONS_ON|self::SOAP_CLIENT_EXCEPTIONS_OFF
     * @param string $userAgent
     * @param int|null $compression = self::SOAP_CLIENT_COMPRESSION_NONE|self::SOAP_CLIENT_COMPRESSION_GZIP|self::SOAP_CLIENT_COMPRESSION_DEFLATE
     * @param SoapServerAuthenticationInterface|null $authentication
     * @param SoapServerProxy|null $proxy
     * @param string|null $location
     * @param bool $resolveRemoteIncludes = self::SOAP_CLIENT_RESOLVE_REMOTE_INCLUDES_ON|self::SOAP_CLIENT_RESOLVE_REMOTE_INCLUDES_OFF
     */
    public function __construct(
        $trace,
        $exceptions,
        $userAgent,
        $compression = null,
        SoapServerAuthenticationInterface $authentication = null,
        SoapServerProxy $proxy = null,
        $location = null,
        $resolveRemoteIncludes = false
    ) {
        $this->trace = $trace;
        $this->exceptions = $exceptions;
        $this->userAgent = $userAgent;
        $this->compression = $compression;
        $this->authentication = $authentication;
        $this->proxy = $proxy;
        $this->location = $location;
        $this->resolveRemoteIncludes = $resolveRemoteIncludes;
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

    public function hasLocation()
    {
        return $this->location !== null;
    }

    public function getAuthentication()
    {
        return $this->authentication;
    }

    public function getProxy()
    {
        return $this->proxy;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function isResolveRemoteIncludes()
    {
        return $this->resolveRemoteIncludes;
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
        if ($this->hasLocation()) {
            $optionsAsArray['location'] = $this->getLocation();
        }

        return $optionsAsArray;
    }
}
