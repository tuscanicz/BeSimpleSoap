<?php

namespace BeSimple\SoapClient;

class SoapClientNativeDataTransferObject
{
    public $request;
    public $location;
    public $action;
    public $version;

    public function __construct($request, $location, $action, $version)
    {
        $this->request = $request;
        $this->location = $location;
        $this->action = $action;
        $this->version = $version;
    }
}
