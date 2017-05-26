<?php

const FIXTURES_DIR = __DIR__.'/Fixtures';

$soapServer = new \SoapServer(FIXTURES_DIR.'/DummyService.wsdl');
$soapServer->fault(
    911,
    'This is a dummy SoapFault.'
);
