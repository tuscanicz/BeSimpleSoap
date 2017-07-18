<?php

namespace BeSimple\SoapClient\Xml;

use PHPUnit_Framework_TestCase;

class RemoteFileResolverTest extends PHPUnit_Framework_TestCase
{
    const FILE_IS_REMOTE = true;
    const FILE_IS_NOT_REMOTE = false;

    /** @var RemoteFileResolver */
    private $remoteFileResolver;

    public function setUp()
    {
        $this->remoteFileResolver = new RemoteFileResolver();
    }

    /**
     * @param string $wsdlPath
     * @param bool $assertIsRemoteFile
     * @dataProvider provideWsdlPaths
     */
    public function testIsRemoteFile($wsdlPath, $assertIsRemoteFile)
    {
        $isRemoteFile = $this->remoteFileResolver->isRemoteFile($wsdlPath);

        self::assertEquals($assertIsRemoteFile, $isRemoteFile);
    }

    public function provideWsdlPaths()
    {
        return [
            ['http://endpoint.tld/path/to/wsdl.wsdl', self::FILE_IS_REMOTE],
            ['http://endpoint.tld:1944/path/to/wsdl.wsdl', self::FILE_IS_REMOTE],
            ['path/to/wsdl.wsdl', self::FILE_IS_NOT_REMOTE],
            ['../../path/to/wsdl.wsdl', self::FILE_IS_NOT_REMOTE],
            ['/path/to/wsdl.wsdl', self::FILE_IS_NOT_REMOTE],
        ];
    }
}
