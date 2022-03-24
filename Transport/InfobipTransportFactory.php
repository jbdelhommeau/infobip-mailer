<?php

namespace Symfony\Component\Mailer\Bridge\Mailjet\Transport;


use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class InfobipTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $schema = $dsn->getScheme();
        $user = $this->getUser($dsn);
        $password = $this->getPassword($dsn);
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();

        if (null === $host) {
            throw new IncompleteDsnException('Infobip mailer DSN must contain a host.');
        }

        if ('infobip+api' === $schema) {
            $transport = (new InfobipApiTransport($user, $password, $this->client, $this->dispatcher, $this->logger))->setHost($host);
        }

        throw new UnsupportedSchemeException($dsn, 'infobip', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['infobip', 'infobip+api'];
    }
}
