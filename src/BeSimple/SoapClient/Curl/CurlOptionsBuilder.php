<?php

namespace BeSimple\SoapClient\Curl;

use BeSimple\SoapClient\Curl\Http\HttpAuthenticationDigestOptions;
use BeSimple\SoapClient\Curl\Http\SslCertificateOptions;
use BeSimple\SoapClient\SoapOptions\SoapClientOptions;
use BeSimple\SoapClient\SoapServerAuthentication\SoapServerAuthenticationBasic;
use BeSimple\SoapClient\SoapServerAuthentication\SoapServerAuthenticationDigest;
use BeSimple\SoapClient\Curl\Http\HttpAuthenticationBasicOptions;
use Exception;

class CurlOptionsBuilder
{
    const DEFAULT_MAX_REDIRECTS = 10;
    const DEFAULT_CONNECTION_TIMEOUT = 120;

    public static function buildDefault()
    {
        return new CurlOptions(
            CurlOptions::DEFAULT_USER_AGENT,
            self::DEFAULT_MAX_REDIRECTS,
            CurlOptions::SOAP_COMPRESSION_NONE,
            self::DEFAULT_CONNECTION_TIMEOUT
        );
    }

    public static function buildForSoapClient(SoapClientOptions $soapClientOptions)
    {
        return new CurlOptions(
            $soapClientOptions->getUserAgent(),
            self::DEFAULT_MAX_REDIRECTS,
            $soapClientOptions->getCompression(),
            self::DEFAULT_CONNECTION_TIMEOUT,
            $soapClientOptions->getProxy(),
            self::getHttpAuthOptions($soapClientOptions),
            self::getSslCertificateOptions($soapClientOptions)
        );
    }

    private static function getHttpAuthOptions(SoapClientOptions $soapClientOptions)
    {
        if ($soapClientOptions->hasAuthentication()) {
            if ($soapClientOptions->hasAuthenticationBasic()) {
                /** @var SoapServerAuthenticationBasic $basicAuthentication */
                $basicAuthentication = $soapClientOptions->getAuthentication();

                return new HttpAuthenticationBasicOptions(
                    $basicAuthentication->getLogin(),
                    $basicAuthentication->getPassword()
                );

            }
            if ($soapClientOptions->hasAuthenticationDigest()) {

                return new HttpAuthenticationDigestOptions();

            }

            throw new Exception('Unresolved authentication type: '.get_class($soapClientOptions->getAuthentication()));
        }

        return null;
    }

    private static function getSslCertificateOptions(SoapClientOptions $soapClientOptions)
    {
        if ($soapClientOptions->hasAuthenticationDigest()) {
            /** @var SoapServerAuthenticationDigest $digestAuthentication */
            $digestAuthentication = $soapClientOptions->getAuthentication();

            return new SslCertificateOptions(
                $digestAuthentication->getLocalCert(),
                $digestAuthentication->getPassPhrase()
            );
        }

        return null;
    }
}
