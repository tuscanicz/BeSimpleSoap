<?php

namespace BeSimple\SoapCommon\SoapOptions\SoapFeatures;

use Exception;

class SoapFeatures
{
    const SINGLE_ELEMENT_ARRAYS = \SOAP_SINGLE_ELEMENT_ARRAYS;
    const WAIT_ONE_WAY_CALLS = \SOAP_WAIT_ONE_WAY_CALLS;
    const USE_XSI_ARRAY_TYPE = \SOAP_USE_XSI_ARRAY_TYPE;

    private $featuresSum;
    private $singleElementArrays = false;
    private $oneWayCallsOn = false;
    private $useXsiArrayType = false;

    /**
     * @param array $features array of SoapFeatures::FEATURE_NAME
     * @throws Exception
     */
    public function __construct(array $features)
    {
        $this->resolveFeatures($features);
    }

    public function isSingleElementArrays()
    {
        return $this->singleElementArrays;
    }

    public function isOneWayCallsOn()
    {
        return $this->oneWayCallsOn;
    }

    public function isUseXsiArrayType()
    {
        return $this->useXsiArrayType;
    }

    public function getFeaturesSum()
    {
        return $this->featuresSum;
    }

    private function resolveFeatures(array $features)
    {
        $featuresSum = 0;
        foreach ($features as $feature) {
            switch ($feature) {
                case self::SINGLE_ELEMENT_ARRAYS:
                    $this->singleElementArrays = true;
                    $featuresSum += $feature;
                    break;
                case self::WAIT_ONE_WAY_CALLS:
                    $this->oneWayCallsOn = true;
                    $featuresSum += $feature;
                    break;
                case self::USE_XSI_ARRAY_TYPE:
                    $this->useXsiArrayType = true;
                    $featuresSum += $feature;
                    break;
                default:
                    throw new Exception('Unknown SOAP feature: ' . $feature);
            }
        }
        $this->featuresSum = $featuresSum;
    }
}
