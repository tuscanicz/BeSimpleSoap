<?php

/*
 * This file is part of the BeSimpleSoapClient.
 *
 * (c) Christian Kerl <christian-kerl@web.de>
 * (c) Francis Besset <francis.besset@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace BeSimple\SoapServer;

use BeSimple\SoapCommon\Helper;
use BeSimple\SoapCommon\Mime\MultiPart as MimeMultiPart;
use BeSimple\SoapCommon\Mime\Parser as MimeParser;
use BeSimple\SoapCommon\Mime\Part as MimePart;
use BeSimple\SoapCommon\Mime\Part;
use BeSimple\SoapCommon\SoapRequest;
use BeSimple\SoapCommon\SoapRequestFilter;
use BeSimple\SoapCommon\SoapResponse;
use BeSimple\SoapCommon\SoapResponseFilter;

/**
 * MIME filter.
 *
 * @author Andreas Schamberger <mail@andreass.net>
 */
class MimeFilter implements SoapRequestFilter, SoapResponseFilter
{
    public function filterRequest(SoapRequest $request, $attachmentType)
    {
        $multiPartMessage = MimeParser::parseMimeMessage(
            $request->getContent(),
            ['Content-Type' => trim($request->getContentType())]
        );
        $soapPart = $multiPartMessage->getMainPart();
        $attachments = $multiPartMessage->getAttachments();

        $request->setContent($this->sanitizePhpExceptionOnHrefs($soapPart));
        $request->setContentType($soapPart->getHeader('Content-Type'));
        if (count($attachments) > 0) {
            $request->setAttachments($attachments);
        }

        return $request;
    }

    public function filterResponse(SoapResponse $response, $attachmentType)
    {
        $attachmentsToSend = $response->getAttachments();
        if (count($attachmentsToSend) > 0) {
            $multipart = new MimeMultiPart('Part_' . rand(10, 15) . '_' . uniqid() . '.' . uniqid());
            $soapPart = new MimePart($response->getContent(), 'text/xml', 'utf-8', MimePart::ENCODING_EIGHT_BIT);
            $soapVersion = $response->getVersion();

            if ($soapVersion === SOAP_1_1 && $attachmentType === Helper::ATTACHMENTS_TYPE_MTOM) {
                $multipart->setHeader('Content-Type', 'type', 'application/xop+xml');
                $multipart->setHeader('Content-Type', 'start-info', 'text/xml');
                $soapPart->setHeader('Content-Type', 'application/xop+xml');
                $soapPart->setHeader('Content-Type', 'type', 'text/xml');
            } elseif ($soapVersion === SOAP_1_2) {
                $multipart->setHeader('Content-Type', 'type', 'application/soap+xml');
                $soapPart->setHeader('Content-Type', 'application/soap+xml');
            }

            $multipart->addPart($soapPart, true);
            foreach ($attachmentsToSend as $cid => $attachment) {
                $multipart->addPart($attachment, false);
            }
            $response->setContent($multipart->getMimeMessage());

            $headers = $multipart->getHeadersForHttp();
            list(, $contentType) = explode(': ', $headers[0]);

            $response->setContentType($contentType);
        }

        return $response;
    }

    private function sanitizePhpExceptionOnHrefs(Part $soapPart)
    {
        // convert href -> myhref for external references as PHP throws exception in this case
        // http://svn.php.net/viewvc/php/php-src/branches/PHP_5_4/ext/soap/php_encoding.c?view=markup#l3436
        return preg_replace('/href=(?!#)/', 'myhref=', $soapPart->getContent());
    }
}
