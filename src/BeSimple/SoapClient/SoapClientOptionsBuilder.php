<?php

/*
 * This file is part of the BeSimpleSoapBundle.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapClient;

use BeSimple\SoapClient\Curl\CurlOptions;
use BeSimple\SoapClient\SoapOptions\SoapClientOptions;
use BeSimple\SoapClient\SoapServerAuthentication\SoapServerAuthenticationInterface;

/**
 * Provides a SoapClient instance.
 *
 * @author Francis Besset <francis.besset@gmail.com>
 * @author Christian Kerl <christian-kerl@web.de>
 * @author Petr Bechyně <mail@petrbechyne.com>
 */
class SoapClientOptionsBuilder
{
    public static function createWithDefaults()
    {
        return new SoapClientOptions(
            SoapClientOptions::SOAP_CLIENT_TRACE_ON,
            SoapClientOptions::SOAP_CLIENT_EXCEPTIONS_ON,
            CurlOptions::DEFAULT_USER_AGENT,
            SoapClientOptions::SOAP_CLIENT_COMPRESSION_NONE
        );
    }

    public static function createWithTracing()
    {
        return new SoapClientOptions(
            SoapClientOptions::SOAP_CLIENT_TRACE_ON,
            SoapClientOptions::SOAP_CLIENT_EXCEPTIONS_ON,
            CurlOptions::DEFAULT_USER_AGENT,
            SoapClientOptions::SOAP_CLIENT_COMPRESSION_NONE
        );
    }

    public static function createWithEndpointLocation($endpointLocation)
    {
        return new SoapClientOptions(
            SoapClientOptions::SOAP_CLIENT_TRACE_ON,
            SoapClientOptions::SOAP_CLIENT_EXCEPTIONS_ON,
            CurlOptions::DEFAULT_USER_AGENT,
            SoapClientOptions::SOAP_CLIENT_COMPRESSION_NONE,
            SoapClientOptions::SOAP_CLIENT_AUTHENTICATION_NONE,
            SoapClientOptions::SOAP_CLIENT_PROXY_NONE,
            $endpointLocation
        );
    }

    public static function createWithAuthentication(SoapServerAuthenticationInterface $authentication)
    {
        return new SoapClientOptions(
            SoapClientOptions::SOAP_CLIENT_TRACE_ON,
            SoapClientOptions::SOAP_CLIENT_EXCEPTIONS_ON,
            CurlOptions::DEFAULT_USER_AGENT,
            SoapClientOptions::SOAP_CLIENT_COMPRESSION_NONE,
            $authentication
        );
    }

    public static function createWithAuthenticationAndEndpointLocation($endpointLocation, SoapServerAuthenticationInterface $authentication)
    {
        return new SoapClientOptions(
            SoapClientOptions::SOAP_CLIENT_TRACE_ON,
            SoapClientOptions::SOAP_CLIENT_EXCEPTIONS_ON,
            CurlOptions::DEFAULT_USER_AGENT,
            SoapClientOptions::SOAP_CLIENT_COMPRESSION_NONE,
            $authentication,
            SoapClientOptions::SOAP_CLIENT_PROXY_NONE,
            $endpointLocation
        );
    }

    /**
     * @param SoapServerAuthenticationInterface $authentication
     * @param bool $resolveRemoteIncludes
     * @return SoapClientOptions
     */
    public static function createWithAuthenticationAndResolveRemoteIncludes(
        SoapServerAuthenticationInterface $authentication,
        $resolveRemoteIncludes
    ) {
        return new SoapClientOptions(
            SoapClientOptions::SOAP_CLIENT_TRACE_ON,
            SoapClientOptions::SOAP_CLIENT_EXCEPTIONS_ON,
            CurlOptions::DEFAULT_USER_AGENT,
            SoapClientOptions::SOAP_CLIENT_COMPRESSION_NONE,
            $authentication,
            SoapClientOptions::SOAP_CLIENT_PROXY_NONE,
            SoapClientOptions::SOAP_CLIENT_ENDPOINT_LOCATION_NONE,
            $resolveRemoteIncludes
        );
    }
}
