<?php

const FIXTURES_DIR = __DIR__ . '/Fixtures';

if (isset($_GET['wsdl'])) {
    header('Content-type: text/xml');
    echo file_get_contents(FIXTURES_DIR.'/DummyService.wsdl');
    exit;
}
$contentTypeFromCache = __DIR__.'/../cache/content-type-soap-server-response.xml';
$multiPartMessageFromCache = __DIR__.'/../cache/multipart-message-soap-server-response.xml';

if (file_exists($contentTypeFromCache) === false || file_exists($multiPartMessageFromCache) === false) {
    $soapServer = new \SoapServer(FIXTURES_DIR.'/DummyService.wsdl');
    $soapServer->fault(
        911,
        'Cannot load data from cache: run soap server testHandleRequestWithLargeSwaResponse to get the data.'
    );
}

header('Content-type: '.file_get_contents($contentTypeFromCache));
echo file_get_contents($multiPartMessageFromCache);
