<?php

namespace Fixtures;

use BeSimple\SoapBundle\Soap\SoapAttachment;
use BeSimple\SoapCommon\AttachmentsHandlerInterface;
use BeSimple\SoapCommon\Storage\RequestHandlerAttachmentsStorage;
use Fixtures\Attachment\Attachment;
use Fixtures\Attachment\AttachmentCollection;
use ReflectionClass;

class DummyService implements AttachmentsHandlerInterface
{
    /** @var RequestHandlerAttachmentsStorage */
    private $requestHandlerAttachmentsStorage;

    public function addAttachmentStorage(RequestHandlerAttachmentsStorage $requestHandlerAttachmentsStorage)
    {
        $this->requestHandlerAttachmentsStorage = $requestHandlerAttachmentsStorage;
    }

    public function getAttachmentStorage()
    {
        return $this->requestHandlerAttachmentsStorage;
    }

    /**
     * @return string[]
     */
    public function getClassMap()
    {
        return [
            'DummyServiceResponse' => DummyServiceResponse::class,
            'DummyServiceResponseWithAttachments' => DummyServiceResponseWithAttachments::class,
            'DummyServiceRequest' => DummyServiceRequest::class,
            'DummyServiceRequestWithAttachments' => DummyServiceRequestWithAttachments::class,
        ];
    }

    /**
     * @exclude
     * @return string
     */
    public function getWsdlPath()
    {
        $class = new ReflectionClass(static::class);

        return __DIR__.DIRECTORY_SEPARATOR.$class->getShortName().'.wsdl';
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return 'http://my.test/soap/dummyService';
    }

    /**
     * @param DummyServiceRequest $dummyServiceRequest
     * @return DummyServiceResponse
     */
    public function dummyServiceMethod(DummyServiceRequest $dummyServiceRequest)
    {
        $dummyServiceHandler = new DummyServiceHandler();

        return $dummyServiceHandler->handle($dummyServiceRequest);
    }

    /**
     * @param DummyServiceRequestWithAttachments $dummyServiceRequestWithAttachments
     * @return DummyServiceResponseWithAttachments
     */
    public function dummyServiceMethodWithAttachments(DummyServiceRequestWithAttachments $dummyServiceRequestWithAttachments)
    {
        if ($dummyServiceRequestWithAttachments->hasAttachments() === true) {
            $attachmentStorage = $this->getAttachmentStorage();
            $attachments = [];
            foreach ($attachmentStorage->getAttachments() as $soapAttachment) {
                $attachments[] = new Attachment(
                    $soapAttachment->getId(),
                    $soapAttachment->getType(),
                    $soapAttachment->getContent()
                );
            }
            $dummyServiceRequestWithAttachments->attachmentCollection = new AttachmentCollection($attachments);
        }

        $dummyServiceHandlerWithAttachments = new DummyServiceHandlerWithAttachments();
        $dummyServiceResponseWithAttachments = $dummyServiceHandlerWithAttachments->handle($dummyServiceRequestWithAttachments);

        if ($dummyServiceResponseWithAttachments->hasAttachments() === true) {
            $soapAttachments = [];
            foreach ($dummyServiceResponseWithAttachments->attachmentCollection->attachments as $attachment) {
                $soapAttachments[] = new SoapAttachment(
                    $attachment->fileName,
                    $attachment->contentType,
                    $attachment->content
                );
            }
            $this->addAttachmentStorage(new RequestHandlerAttachmentsStorage($soapAttachments));
        }

        return $dummyServiceResponseWithAttachments;
    }
}
