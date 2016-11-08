<?php

namespace BeSimple\SoapCommon;

use BeSimple\SoapCommon\Storage\RequestHandlerAttachmentsStorage;

/** @todo: PBe - refactor this interface + usages -> inconsistent - adding storage, getting items - WTF APi? */
interface AttachmentsHandler
{
    public function addAttachmentStorage(RequestHandlerAttachmentsStorage $requestHandlerAttachmentsStorage);
    public function getAttachmentsFromStorage();
}
