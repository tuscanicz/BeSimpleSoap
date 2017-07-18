<?php

namespace BeSimple\SoapClient\Xml;

use BeSimple\SoapClient\Curl\Curl;
use BeSimple\SoapClient\Curl\CurlOptionsBuilder;
use BeSimple\SoapCommon\Cache;
use BeSimple\SoapCommon\Helper;
use DOMDocument;
use DOMXPath;
use PHPUnit_Framework_TestCase;

class XmlDomDocumentImportReplacerTest extends PHPUnit_Framework_TestCase
{
    const NO_PARENT_FILE_PATH = null;

    /** @var XmlDomDocumentImportReplacer */
    private $xmlDomDocumentImportReplacer;

    public function setUp()
    {
        $this->xmlDomDocumentImportReplacer = new XmlDomDocumentImportReplacer();
    }

    /**
     * @param string $xmlSource
     * @param Curl $curl
     * @param string $schemaPrefix
     * @param string $schemaUrl
     * @param string $locationAttributeName
     * @param string|null $parentFilePath
     * @param string|null $assertImportXmlSource
     * @dataProvider provideXmlDocumentData
     */
    public function testUpdateXmlDocument(
        $xmlSource,
        Curl $curl,
        $schemaPrefix,
        $schemaUrl,
        $locationAttributeName,
        $parentFilePath = null,
        $assertImportXmlSource = null
    ) {
        $wsdl = new DOMDocument();
        $wsdl->loadXML($xmlSource);

        $this->xmlDomDocumentImportReplacer->updateXmlDocument(
            $curl,
            Cache::TYPE_NONE,
            new DOMXPath($wsdl),
            $schemaPrefix,
            $schemaUrl,
            $locationAttributeName,
            $parentFilePath
        );
        $wsdlSource = $wsdl->saveHTML();

        self::assertContains(
            $assertImportXmlSource,
            $wsdlSource
        );
    }

    public function provideXmlDocumentData()
    {
        return [
            'wsdlWithoutParentPath' => [
                file_get_contents(__DIR__.'/testUpdateXmlDocument.wsdl'),
                new Curl(CurlOptionsBuilder::buildDefault()),
                Helper::PFX_WSDL,
                Helper::NS_WSDL,
                'location',
                self::NO_PARENT_FILE_PATH,
                '<xs:include schemaLocation="../Schemas/Common/Document1.xsd"></xs:include>'
            ],
            'schemaWithoutParentPath' => [
                file_get_contents(__DIR__.'/testUpdateXmlDocument.wsdl'),
                new Curl(CurlOptionsBuilder::buildDefault()),
                Helper::PFX_XML_SCHEMA,
                Helper::NS_XML_SCHEMA,
                'schemaLocation',
                self::NO_PARENT_FILE_PATH,
                '<xs:include schemaLocation="../Schemas/Common/Document1.xsd"></xs:include>'
            ],
            'wsdlWithParentPath' => [
                file_get_contents(__DIR__.'/testUpdateXmlDocument.wsdl'),
                new Curl(CurlOptionsBuilder::buildDefault()),
                Helper::PFX_WSDL,
                Helper::NS_WSDL,
                'location',
                'http://endpoint-location.ltd:8080/endpoint/',
                '<xs:include schemaLocation="../Schemas/Common/Document1.xsd"></xs:include>'
            ],
            'schemaWithParentPath' => [
                file_get_contents(__DIR__.'/testUpdateXmlDocument.wsdl'),
                new Curl(CurlOptionsBuilder::buildDefault()),
                Helper::PFX_XML_SCHEMA,
                Helper::NS_XML_SCHEMA,
                'schemaLocation',
                'http://endpoint-location.ltd:8080/endpoint/',
                '<xs:include schemaLocation="http://endpoint-location.ltd:8080/Schemas/Common/Document1.xsd"></xs:include>'
            ],
        ];
    }
}
