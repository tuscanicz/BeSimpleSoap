<?php

namespace Fixtures;

use BeSimple\SoapServerAndSoapClientCommunicationTest;
use Fixtures\Attachment\Attachment;
use Fixtures\Attachment\AttachmentCollection;

class DummyServiceHandlerWithOutgoingLargeSwa
{
    /**
     * @param DummyServiceMethodWithOutgoingLargeSwaRequest $request
     * @return DummyServiceMethodWithOutgoingLargeSwaResponse
     */
    public function handle(DummyServiceMethodWithOutgoingLargeSwaRequest $request)
    {
        $response = new DummyServiceMethodWithOutgoingLargeSwaResponse();
        $response->status = true;

        $response->attachmentCollection = new AttachmentCollection([
            new Attachment('filename.txt', 'text/plain', 'plaintext file'),
            new Attachment('filename.html', 'text/html', '<html><body>Hello world</body></html>'),
            new Attachment(
                'filename.docx',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                file_get_contents(SoapServerAndSoapClientCommunicationTest::LARGE_SWA_FILE)
            ),
        ]);

        return $response;
    }
}
