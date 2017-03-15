<?php

namespace Fixtures;

use Fixtures\Attachment\Attachment;
use Fixtures\Attachment\AttachmentCollection;

class DummyServiceHandlerWithAttachments
{
    /**
     * @param DummyServiceRequestWithAttachments $request
     * @return DummyServiceResponseWithAttachments
     */
    public function handle(DummyServiceRequestWithAttachments $request)
    {
        $response = new DummyServiceResponseWithAttachments();
        $response->status = true;
        if ($request->includeAttachments === true) {
            if ($request->hasAttachments() === true) {
                $attachments = [];
                foreach ($request->attachmentCollection->attachments as $attachment) {
                    $attachments[] = new Attachment($attachment->fileName, $attachment->contentType, $attachment->content);
                }
                $response->attachmentCollection = new AttachmentCollection($attachments);
            }
        }

        return $response;
    }
}
