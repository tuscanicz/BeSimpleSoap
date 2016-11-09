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

use BeSimple\SoapBundle\Soap\SoapAttachment;
use BeSimple\SoapCommon\AttachmentsHandlerInterface;
use BeSimple\SoapCommon\SoapKernel;
use BeSimple\SoapCommon\SoapOptions\SoapOptions;
use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapCommon\SoapRequestFactory;
use BeSimple\SoapCommon\Storage\RequestHandlerAttachmentsStorage;
use BeSimple\SoapServer\SoapOptions\SoapServerOptions;
use BeSimple\SoapCommon\Converter\MtomTypeConverter;
use BeSimple\SoapCommon\Converter\SwaTypeConverter;
use Exception;
use InvalidArgumentException;

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
    protected $soapServerOptions;
    protected $soapOptions;

    /**
     * Constructor.
     *
     * @param SoapServerOptions $soapServerOptions
     * @param SoapOptions $soapOptions
     */
    public function __construct(SoapServerOptions $soapServerOptions, SoapOptions $soapOptions)
    {
        $this->validateSoapConfigs($soapServerOptions, $soapOptions);

        $this->soapVersion = $soapOptions->getSoapVersion();
        $this->soapServerOptions = $soapServerOptions;
        $this->soapOptions = $soapOptions;

        parent::__construct(
            $soapOptions->getWsdlFile(),
            $soapServerOptions->toArray() + $soapOptions->toArray()
        );
    }

    /**
     * Custom handle method to be able to modify the SOAP messages.
     *
     * @deprecated
     * @param string $request Request string
     * @return string
     */
    public function handle($request = null)
    {
        throw new Exception(
            'The handle method is now deprecated, because it accesses $_SERVER, $_POST. Use handleRequest instead'
        );
    }

    /**
     * Custom handle method to be able to modify the SOAP messages.
     *
     * @param string $requestUrl
     * @param string $soapAction
     * @param string $requestContentType
     * @param string $requestContent = null
     * @return SoapRequest
     */
    public function createRequest($requestUrl, $soapAction, $requestContentType, $requestContent = null)
    {
        $soapRequest = SoapRequestFactory::create(
            $requestUrl,
            $soapAction,
            $this->soapVersion,
            $requestContentType,
            $requestContent
        );
        $soapKernel = new SoapKernel();
        if ($this->soapOptions->hasAttachments()) {
            $soapRequest = $soapKernel->filterRequest(
                $soapRequest,
                $this->getAttachmentFilters(),
                $this->soapOptions->getAttachmentType()
            );
        }

        return $soapRequest;
    }

    public function handleRequest(SoapRequest $soapRequest)
    {
        try {

            return $this->handleSoapRequest($soapRequest);

        } catch (\SoapFault $fault) {
            $this->fault($fault->faultcode, $fault->faultstring);

            return self::SOAP_SERVER_REQUEST_FAILED;
        }
    }

    public function handleWsdlRequest(SoapRequest $soapRequest)
    {
        ob_start();
        parent::handle();
        $nativeSoapServerResponse = ob_get_clean();

        return SoapResponseFactory::create(
            $nativeSoapServerResponse,
            $soapRequest->getLocation(),
            $soapRequest->getAction(),
            $soapRequest->getVersion()
        );
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
        /** @var AttachmentsHandlerInterface $handler */
        $handler = $this->soapServerOptions->getHandler();

        if ($this->soapOptions->hasAttachments()) {
            $this->injectAttachmentStorage($handler, $soapRequest, $this->soapOptions->getAttachmentType());
        }

        ob_start();
        parent::handle($soapRequest->getContent());
        $nativeSoapServerResponse = ob_get_clean();

        $attachments = [];
        if ($this->soapOptions->hasAttachments()) {
            $attachments = $handler->getAttachmentStorage()->getAttachments();
        }

        // Remove headers added by SoapServer::handle() method
        header_remove('Content-Length');
        header_remove('Content-Type');

        return $this->createResponse(
            $soapRequest->getLocation(),
            $soapRequest->getAction(),
            $soapRequest->getVersion(),
            $nativeSoapServerResponse,
            $attachments
        );
    }

    /**
     * @param string $requestLocation
     * @param string $soapAction
     * @param string $soapVersion
     * @param string|null $responseContent
     * @param SoapAttachment[] $attachments
     * @return SoapResponse
     */
    private function createResponse($requestLocation, $soapAction, $soapVersion, $responseContent = null, $attachments = [])
    {
        $soapResponse = SoapResponseFactory::create(
            $responseContent,
            $requestLocation,
            $soapAction,
            $soapVersion,
            $attachments
        );
        $soapKernel = new SoapKernel();
        if ($this->soapOptions->hasAttachments()) {
            $soapResponse = $soapKernel->filterResponse(
                $soapResponse,
                $this->getAttachmentFilters(),
                $this->soapOptions->getAttachmentType()
            );
        }

        return $soapResponse;
    }

    private function injectAttachmentStorage(AttachmentsHandlerInterface $handler, SoapRequest $soapRequest, $attachmentType)
    {
        $attachments = [];
        if ($soapRequest->hasAttachments()) {
            foreach ($soapRequest->getAttachments() as $attachment) {
                $attachments[] = new SoapAttachment(
                    $attachment->getHeader('Content-Disposition', 'filename'),
                    $attachmentType,
                    $attachment->getContent()
                );
            }
        }
        $handler->addAttachmentStorage(new RequestHandlerAttachmentsStorage($attachments));
    }

    /**
     * Legacy code: TypeConverters should be resolved in SoapServer::__construct()
     * To be removed if all tests pass
     *
     * @deprecated
     * @param SoapOptions $soapOptions
     * @return SoapOptions
     */
    private function configureTypeConverters(SoapOptions $soapOptions)
    {
        if ($soapOptions->getAttachmentType() !== SoapOptions::SOAP_ATTACHMENTS_TYPE_BASE64) {
            if ($soapOptions->getAttachmentType() === SoapOptions::SOAP_ATTACHMENTS_TYPE_SWA) {
                $soapOptions->getTypeConverterCollection()->add(new SwaTypeConverter());
            } elseif ($soapOptions->getAttachmentType() === SoapOptions::SOAP_ATTACHMENTS_TYPE_MTOM) {
                $soapOptions->getTypeConverterCollection()->add(new MtomTypeConverter());
            } else {
                throw new InvalidArgumentException('Unresolved SOAP_ATTACHMENTS_TYPE: ' . $soapOptions->getAttachmentType());
            }
        }

        return $soapOptions;
    }

    private function getAttachmentFilters()
    {
        $filters = [];
        if ($this->soapOptions->getAttachmentType() !== SoapOptions::SOAP_ATTACHMENTS_TYPE_BASE64) {
            $filters[] = new MimeFilter();
        }
        if ($this->soapOptions->getAttachmentType() === SoapOptions::SOAP_ATTACHMENTS_TYPE_MTOM) {
            $filters[] = new XmlMimeFilter();
        }

        return $filters;
    }

    private function validateSoapConfigs(SoapServerOptions $soapServerOptions, SoapOptions $soapOptions)
    {
        if ($soapOptions->hasAttachments()) {
            if (!$soapServerOptions->getHandlerInstance() instanceof AttachmentsHandlerInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        '%s::handlerObject or handlerClass (instance of %s given) must implement %s in order to handle with attachments',
                        SoapServerOptions::class,
                        get_class($soapServerOptions->getHandlerInstance()),
                        AttachmentsHandlerInterface::class
                    )
                );
            }
        }
    }
}
