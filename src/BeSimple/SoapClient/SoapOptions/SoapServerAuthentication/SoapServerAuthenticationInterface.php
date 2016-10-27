<?php

namespace BeSimple\SoapClient\SoapServerAuthentication;

interface SoapServerAuthenticationInterface
{
    /**
     * @return int
     */
    public function getAuthentication();

    /**
     * @return array
     */
    public function toArray();
}
