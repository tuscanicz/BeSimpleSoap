<?php

namespace BeSimple\SoapCommon\SoapOptions;

use BeSimple\SoapCommon\Cache;
use BeSimple\SoapCommon\ClassMap;
use BeSimple\SoapCommon\Converter\TypeConverterCollection;
use BeSimple\SoapCommon\Helper;
use BeSimple\SoapCommon\SoapOptions\SoapFeatures\SoapFeatures;

class SoapOptions
{
    const SOAP_VERSION_1_1 = \SOAP_1_1;
    const SOAP_VERSION_1_2 = \SOAP_1_2;
    const SOAP_ENCODING_UTF8 = 'UTF-8';
    const SOAP_SINGLE_ELEMENT_ARRAYS_OFF = 0;
    const SOAP_CACHE_TYPE_NONE = Cache::TYPE_NONE;
    const SOAP_CACHE_TYPE_DISK = Cache::TYPE_DISK;
    const SOAP_CACHE_TYPE_MEMORY = Cache::TYPE_MEMORY;
    const SOAP_CACHE_TYPE_DISK_MEMORY = Cache::TYPE_DISK_MEMORY;
    const SOAP_ATTACHMENTS_OFF = null;
    const SOAP_ATTACHMENTS_TYPE_BASE64 = Helper::ATTACHMENTS_TYPE_BASE64;
    const SOAP_ATTACHMENTS_TYPE_MTOM = Helper::ATTACHMENTS_TYPE_MTOM;
    const SOAP_ATTACHMENTS_TYPE_SWA = Helper::ATTACHMENTS_TYPE_SWA;

    protected $soapVersion;
    protected $encoding;
    protected $soapFeatures;
    protected $wsdlFile;
    protected $wsdlCacheType;
    protected $wsdlCacheDir;
    protected $classMap;
    protected $typeConverterCollection;
    protected $attachmentType;

    /**
     * @param SoapOptions::SOAP_VERSION_1_1|SoapOptions::SOAP_VERSION_1_2 $soapVersion
     * @param string $encoding = SoapOptions::SOAP_ENCODING_UTF8
     * @param SoapFeatures $features
     * @param string $wsdlFile
     * @param string $wsdlCacheType = SoapOptions::SOAP_CACHE_TYPE_NONE|SoapOptions::SOAP_CACHE_TYPE_MEMORY|SoapOptions::SOAP_CACHE_TYPE_DISK|SoapOptions::SOAP_CACHE_TYPE_DISK_MEMORY
     * @param string $wsdlCacheDir = null
     * @param ClassMap $classMap
     * @param TypeConverterCollection $typeConverterCollection
     * @param string $attachmentType = SoapOptions::SOAP_ATTACHMENTS_OFF|SoapOptions::SOAP_ATTACHMENTS_TYPE_SWA|SoapOptions::ATTACHMENTS_TYPE_MTOM|SoapOptions::ATTACHMENTS_TYPE_BASE64
     */
    public function __construct(
        $soapVersion,
        $encoding,
        SoapFeatures $features,
        $wsdlFile,
        $wsdlCacheType,
        $wsdlCacheDir = null,
        ClassMap $classMap,
        TypeConverterCollection $typeConverterCollection,
        $attachmentType = null
    ) {
        $this->soapVersion = $soapVersion;
        $this->encoding = $encoding;
        $this->soapFeatures = $features;
        $this->wsdlFile = $wsdlFile;
        $this->wsdlCacheType = $wsdlCacheType;
        $this->classMap = $classMap;
        $this->typeConverterCollection = $typeConverterCollection;
        $this->attachmentType = $attachmentType;
    }

    public function getSoapVersion()
    {
        return $this->soapVersion;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function getWsdlFile()
    {
        return $this->wsdlFile;
    }

    public function hasWsdlCacheDir()
    {
        return $this->wsdlCacheDir !== null;
    }

    public function getWsdlCacheDir()
    {
        return $this->wsdlCacheDir;
    }

    public function getWsdlCacheType()
    {
        return $this->wsdlCacheType;
    }

    public function hasAttachments()
    {
        return $this->attachmentType !== self::SOAP_ATTACHMENTS_OFF;
    }

    public function getAttachmentType()
    {
        return $this->attachmentType;
    }

    public function getSoapFeatures()
    {
        return $this->soapFeatures;
    }

    public function getClassMap()
    {
        return $this->classMap;
    }

    public function getTypeConverterCollection()
    {
        return $this->typeConverterCollection;
    }

    public function toArray()
    {
        $optionsAsArray = [
            'soap_version' => $this->getSoapVersion(),
            'encoding' => $this->getEncoding(),
            'features' => $this->getSoapFeatures(),
            'wsdl' => $this->getWsdlFile(),
            'cache_wsdl' => $this->getWsdlCacheType(),
            'classmap' => $this->getClassMap()->getAll(),
            'typemap' => $this->getTypeConverterCollection()->getTypemap(),
        ];
        if ($this->hasWsdlCacheDir()) {
            $optionsAsArray['wsdl_cache_dir'] = $this->getWsdlCacheDir();
        }

        return $optionsAsArray;
    }
}
