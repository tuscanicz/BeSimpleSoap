<?php

namespace BeSimple\SoapClient\SoapServerAuthentication;

class SoapServerAuthenticationDigest implements SoapServerAuthenticationInterface
{
    private $localCert;
    private $passPhrase;

    /**
     * @param string $localCert
     * @param string $passPhrase = null
     */
    public function __construct($localCert, $passPhrase = null)
    {
        $this->localCert = $localCert;
        $this->passPhrase = $passPhrase;
    }

    public function getLocalCert()
    {
        return $this->localCert;
    }

    public function hasPassPhrase()
    {
        return $this->passPhrase !== null;
    }

    public function getPassPhrase()
    {
        return $this->passPhrase;
    }

    public function getAuthentication()
    {
        return \SOAP_AUTHENTICATION_DIGEST;
    }

    public function toArray()
    {
        $authenticationAsArray = [
            'authentication' => $this->getAuthentication(),
            'local_cert' => $this->getLocalCert()
        ];
        if ($this->hasPassPhrase()) {
            $authenticationAsArray['passphrase'] = $this->getPassPhrase();
        }

        return $authenticationAsArray;
    }
}
