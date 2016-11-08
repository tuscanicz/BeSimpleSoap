<?php

namespace BeSimple\SoapCommon;

use BeSimple\SoapCommon\Storage\RequestHandlerAttachmentsStorage;

interface AttachmentsHandlerInterface
{
    public function addAttachmentStorage(RequestHandlerAttachmentsStorage $requestHandlerAttachmentsStorage);

    /**
     * @return RequestHandlerAttachmentsStorage
     */
    public function getAttachmentStorage();
}
