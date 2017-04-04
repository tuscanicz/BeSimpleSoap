<?php

namespace Fixtures;

class DummyServiceHandlerWithIncomingLargeSwa
{
    /**
     * @param DummyServiceMethodWithIncomingLargeSwaRequest $request
     * @return DummyServiceMethodWithIncomingLargeSwaResponse
     */
    public function handle(DummyServiceMethodWithIncomingLargeSwaRequest $request)
    {
        if ($request->hasAttachments() === true) {
            foreach ($request->attachmentCollection->attachments as $attachment) {
                file_put_contents(
                    __DIR__.'/../../cache/attachment-server-request-'.$attachment->fileName,
                    $attachment->content
                );
            }
        }

        $response = new DummyServiceMethodWithIncomingLargeSwaResponse();
        $response->status = true;

        return $response;
    }
}
