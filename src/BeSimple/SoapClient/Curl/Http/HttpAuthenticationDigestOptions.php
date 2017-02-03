<?php

namespace BeSimple\SoapClient\Curl\Http;

class HttpAuthenticationDigestOptions implements HttpAuthenticationInterface
{
    public function getAuthenticationType()
    {
        return HttpAuthenticationInterface::AUTHENTICATION_TYPE_DIGEST;
    }
}
