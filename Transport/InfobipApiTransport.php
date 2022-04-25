<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer\Bridge\Infobip\Transport;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class InfobipApiTransport extends AbstractApiTransport
{
    private const API_VERSION = '2';

    private string $key;

    public function __construct(string $key, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->key = $key;

        parent::__construct($client, $dispatcher, $logger);
    }

    public function __toString(): string
    {
        return sprintf('infobip+api://%s', $this->getEndpoint());
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $formData = $this->getFormData($email, $envelope);

        $headers = $formData->getPreparedHeaders()->toArray();
        $headers[] = sprintf('Authorization: App %s', $this->key);
        $headers[] = 'Accept: application/json';

        $response = $this->client->request(
            'POST',
            sprintf('https://%s/email/%s/send', $this->getEndpoint(), self::API_VERSION),
            [
                'headers' => $headers,
                'body' => $formData->bodyToIterable(),
            ]
        );

        try {
            $statusCode = $response->getStatusCode();
            $result = $response->toArray(false);
        } catch (DecodingExceptionInterface $e) {
            throw new HttpTransportException('Unable to send an email: '.$response->getContent(false).sprintf(' (code %d).', $statusCode), $response);
        } catch (TransportExceptionInterface $e) {
            throw new HttpTransportException('Could not reach the remote Infobip server.', $response, 0, $e);
        }

        if (200 !== $statusCode) {
            throw new HttpTransportException('Unable to send an email: '.$result['requestError']['serviceException']['text'].sprintf(' (code %d).', $statusCode), $response);
        }

        $sentMessage->setMessageId($result['messages'][0]['messageId']);

        return $response;
    }

    private function getEndpoint(): ?string
    {
        return $this->host.($this->port ? ':'.$this->port : '');
    }

    private function getFormData(Email $email, Envelope $envelope): FormDataPart
    {
        $message = [
            ['from' => $envelope->getSender()->toString()],
            ['subject' => $email->getSubject()],
        ];

        $this->addAddresses($message,'to', $this->getRecipients($email, $envelope));

        if ($email->getCc()) {
            $this->addAddresses($message, 'cc', $email->getCc());
        }

        if ($email->getBcc()) {
            $this->addAddresses($message, 'bcc', $email->getBcc());
        }

        if ($email->getReplyTo()) {
            $this->addAddresses($message, 'replyto', $email->getReplyTo());
        }

        if ($email->getTextBody()) {
            $message[] = ['text' => $email->getTextBody()];
        }

        if ($email->getHtmlBody()) {
            $message[] = ['HTML' => $email->getHtmlBody()];
        }

        $this->prepareAttachments($message, $email);

        return new FormDataPart($message);
    }

    private function prepareAttachments(array &$message, Email $email): void
    {
        foreach ($email->getAttachments() as $attachment)
        {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');

            $dataPart = new DataPart($attachment->getBody(), $filename, $attachment->getMediaType().'/'.$attachment->getMediaSubtype());

            if ('inline' === $headers->getHeaderBody('Content-Disposition')) {
                $message[] = ['inlineImage' => $dataPart];
            } else {
                $message[] = ['attachment' => $dataPart];
            }
        }
    }

    private function addAddresses(array &$message, string $property, array $addresses): void
    {
        foreach ($addresses as $address) {
            $message[] = [$property => $address->toString()];
        }
    }
}
