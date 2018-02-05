<?php

const FIXTURES_DIR = __DIR__.'/Fixtures';
const CACHE_DIR = __DIR__.'/../cache';

if (isset($_GET['wsdl'])) {
    header('Content-type: text/xml');
    echo file_get_contents(FIXTURES_DIR.'/Message/Response/soapCallWithSwaAttachmentsOnResponse.wsdl');
    exit;
}

header('Content-type: text/xml');
echo file_get_contents(FIXTURES_DIR.'/Message/Response/soapCallWithSwaAttachmentsOnResponse.response.message');
