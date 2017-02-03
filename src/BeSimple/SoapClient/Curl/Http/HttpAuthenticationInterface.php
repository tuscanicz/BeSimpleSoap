<?php

namespace BeSimple\SoapClient\Curl\Http;

interface HttpAuthenticationInterface
{
    const AUTHENTICATION_TYPE_ANY = CURLAUTH_ANY;
    const AUTHENTICATION_TYPE_BASIC = CURLAUTH_BASIC;
    const AUTHENTICATION_TYPE_DIGEST = CURLAUTH_DIGEST;

    /**
     * @return string choice from self::AUTHENTICATION_TYPE_ANY|self::AUTHENTICATION_TYPE_BASIC|self::AUTHENTICATION_TYPE_DIGEST
     */
    public function getAuthenticationType();
}
