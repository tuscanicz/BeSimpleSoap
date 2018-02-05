<?php

const FIXTURES_DIR = __DIR__.'/Fixtures';
const CACHE_DIR = __DIR__.'/../cache';

if (isset($_GET['wsdl'])) {
    header('Content-type: text/xml');
    echo file_get_contents(FIXTURES_DIR.'/Message/Response/soapCallWithSwaAttachmentsOnResponse.wsdl');
    exit;
}

header('Content-type: multipart/related; type="application/soap+xml"; charset=utf-8; boundary=Part_13_58e3bc35f3743.58e3bc35f376f; start="<part-424dbe68-e2da-450f-9a82-cc3e82742503@response.info>"');
echo file_get_contents(FIXTURES_DIR.'/Message/Response/soapCallWithSwaAttachmentsOnResponse.response.message');
