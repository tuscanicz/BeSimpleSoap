<?php

namespace BeSimple\SoapClient\Curl;

use BeSimple\SoapClient\Curl\Http\HttpAuthenticationDigestOptions;
use BeSimple\SoapClient\Curl\Http\HttpAuthenticationInterface;
use BeSimple\SoapClient\Curl\Http\HttpAuthenticationBasicOptions;
use BeSimple\SoapClient\Curl\Http\SslCertificateOptions;
use BeSimple\SoapClient\SoapServerProxy\SoapServerProxy;

class CurlOptions
{
    const DEFAULT_USER_AGENT = 'PhpBeSimpleSoap';
    const SOAP_COMPRESSION_NONE = null;
    const SOAP_COMPRESSION_GZIP = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP;
    const SOAP_COMPRESSION_DEFLATE = SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_DEFLATE;

    private $userAgent;
    private $followLocationMaxRedirects;
    private $soapCompression;
    private $connectionTimeout;
    private $proxy;
    private $httpAuthentication;
    private $sslCertificateOptions;

    /**
     * @param string $userAgent
     * @param int $followLocationMaxRedirects
     * @param CurlOptions::SOAP_COMPRESSION_NONE|CurlOptions::SOAP_COMPRESSION_GZIP|CurlOptions::SOAP_COMPRESSION_DEFLATE $soapCompression
     * @param int $connectionTimeout
     * @param SoapServerProxy|null $proxy
     * @param HttpAuthenticationInterface|null $httpAuthentication
     * @param SslCertificateOptions|null $sslCertificateOptions
     */
    public function __construct(
        $userAgent,
        $followLocationMaxRedirects,
        $soapCompression,
        $connectionTimeout,
        SoapServerProxy $proxy = null,
        HttpAuthenticationInterface $httpAuthentication = null,
        SslCertificateOptions $sslCertificateOptions = null
    ) {
        $this->userAgent = $userAgent;
        $this->followLocationMaxRedirects = $followLocationMaxRedirects;
        $this->soapCompression = $soapCompression;
        $this->connectionTimeout = $connectionTimeout;
        $this->proxy = $proxy;
        $this->httpAuthentication = $httpAuthentication;
        $this->sslCertificateOptions = $sslCertificateOptions;
    }

    public function getUserAgent()
    {
        return $this->userAgent;
    }

    public function getFollowLocationMaxRedirects()
    {
        return $this->followLocationMaxRedirects;
    }

    public function getSoapCompression()
    {
        return $this->soapCompression;
    }

    public function getConnectionTimeout()
    {
        return $this->connectionTimeout;
    }

    public function getProxy()
    {
        return $this->proxy;
    }

    public function getHttpAuthentication()
    {
        return $this->httpAuthentication;
    }

    public function getSslCertificateOptions()
    {
        return $this->sslCertificateOptions;
    }

    public function hasProxy()
    {
        return $this->proxy !== null;
    }

    public function hasHttpAuthentication()
    {
        return $this->httpAuthentication !== null;
    }

    public function hasSslCertificateOptions()
    {
        return $this->sslCertificateOptions !== null;
    }

    public function hasHttpAuthenticationBasic()
    {
        if ($this->hasHttpAuthentication()) {
            if ($this->getHttpAuthentication() instanceof HttpAuthenticationBasicOptions) {

                return true;
            }
        }

        return false;
    }

    public function hasHttpAuthenticationDigest()
    {
        if ($this->hasHttpAuthentication()) {
            if ($this->getHttpAuthentication() instanceof HttpAuthenticationDigestOptions) {

                return true;
            }
        }

        return false;
    }
}
