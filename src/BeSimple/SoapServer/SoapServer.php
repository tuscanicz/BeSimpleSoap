<?php

/*
 * This file is part of the BeSimpleSoapServer.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapServer;

use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapServer\SoapOptions\SoapServerOptions;
use BeSimple\SoapCommon\Converter\MtomTypeConverter;
use BeSimple\SoapCommon\Converter\SwaTypeConverter;
use Exception;

/**
 * Extended SoapServer that allows adding filters for SwA, MTOM, ... .
 *
 * @author Andreas Schamberger <mail@andreass.net>
 * @author Christian Kerl <christian-kerl@web.de>
 * @author Petr Bechyně <petr.bechyne@vodafone.com>
 */
class SoapServer extends \SoapServer
{
    const SOAP_SERVER_REQUEST_FAILED = false;

    protected $soapVersion;
    protected $soapKernel;

    /**
     * Constructor.
     *
     * @param SoapServerOptions $soapServerOptions
     * @param SoapOptions $soapOptions
     */
    public function __construct(SoapServerOptions $soapServerOptions, SoapOptions $soapOptions)
    {
        if ($soapOptions->hasAttachments()) {
            $soapOptions = $this->configureMime($soapOptions);
        }

        $this->soapKernel = new SoapKernel();
        $this->soapVersion = $soapOptions->getSoapVersion();

        parent::__construct(
            $soapOptions->getWsdlFile(),
            $soapServerOptions->toArray() + $soapOptions->toArray()
        );
    }

    /**
     * Custom handle method to be able to modify the SOAP messages.
     *
     * @param string $request Request string
     * @return string
     */
    public function handle($request = null)
    {
        $soapRequest = SoapRequestFactory::create($request, $this->soapVersion);

        try {
            $soapResponse = $this->handleSoapRequest($soapRequest);
        } catch (\SoapFault $fault) {
            $this->fault($fault->faultcode, $fault->faultstring);

            return self::SOAP_SERVER_REQUEST_FAILED;
        }

        return $soapResponse->getResponseContent();
    }

    /**
     * Runs the currently registered request filters on the request, calls the
     * necessary functions (through the parent's class handle()) and runs the
     * response filters.
     *
     * @param SoapRequest $soapRequest SOAP request object
     *
     * @return SoapResponse
     */
    private function handleSoapRequest(SoapRequest $soapRequest)
    {
        // run SoapKernel on SoapRequest
        $this->soapKernel->filterRequest($soapRequest);

        ob_start();
        parent::handle($soapRequest->getContent());
        $response = ob_get_clean();

        // Remove headers added by SoapServer::handle() method
        header_remove('Content-Length');
        header_remove('Content-Type');

        // wrap response data in SoapResponse object
        $soapResponse = SoapResponse::create(
            $response,
            $soapRequest->getLocation(),
            $soapRequest->getAction(),
            $soapRequest->getVersion()
        );

        // run SoapKernel on SoapResponse
        $this->soapKernel->filterResponse($soapResponse);

        return $soapResponse;
    }

    /**
     * Get SoapKernel instance.
     *
     * @return \BeSimple\SoapServer\SoapKernel
     */
    public function getSoapKernel()
    {
        return $this->soapKernel;
    }

    private function configureMime(SoapOptions $soapOptions)
    {
        if ($soapOptions->getAttachmentType() !== SoapOptions::SOAP_ATTACHMENTS_TYPE_BASE64) {
            $mimeFilter = new MimeFilter($soapOptions->getAttachmentType());
            $this->soapKernel->registerFilter($mimeFilter);
            if ($soapOptions->getAttachmentType() === SoapOptions::SOAP_ATTACHMENTS_TYPE_SWA) {
                $converter = new SwaTypeConverter();
                $converter->setKernel($this->soapKernel);
                $soapOptions->getTypeConverterCollection()->add($converter);
            } elseif ($soapOptions->getAttachmentType() === SoapOptions::SOAP_ATTACHMENTS_TYPE_MTOM) {
                $this->soapKernel->registerFilter(new XmlMimeFilter($soapOptions->getAttachmentType()));
                $converter = new MtomTypeConverter();
                $converter->setKernel($this->soapKernel);
                $soapOptions->getTypeConverterCollection()->add($converter);
            } else {
                throw new Exception('Unresolved SOAP_ATTACHMENTS_TYPE: ' . $soapOptions->getAttachmentType());
            }
        }

        return $soapOptions;
    }
}
