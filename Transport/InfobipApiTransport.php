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
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class InfobipApiTransport extends AbstractApiTransport
{
    private const API_VERSION = '2';

    private $key;

    public function __construct(string $key, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->key = $key;

        parent::__construct($client, $dispatcher, $logger);
    }

    public function __toString()
    {
        return sprintf('infobip+api://%s', $this->getEndpoint());
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $formFields = [
            'from' => 'jean-baptiste.delhommeau@yousign.com@selfserviceib.com',
            'to' => 'jean-baptiste.delhommeau@yousign.com',
            'subject' => 'This is a sample email subject',
            'text' => 'This is a sample email message.',
            //'file_field' => DataPart::fromPath('/path/to/uploaded/file'),
        ];

        $formData = new FormDataPart($formFields);

        $headers = $formData->getPreparedHeaders()->toArray();
        $headers += [
            'Authorization' => 'App '.$this->key,
            'Content-Type' => 'multipart/form-data',
            'Accept' => 'application/json',
        ];

        $response = $this->client->request(
            'POST',
            sprintf('https://%s/email/%s/send', $this->getEndpoint(), self::API_VERSION),
            [
                'headers' => $headers,
                'body' => $formData->bodyToIterable(),
            ]
        );
    }

    private function getEndpoint(): ?string
    {
        return $this->host.($this->port ? ':'.$this->port : '');
    }
}
