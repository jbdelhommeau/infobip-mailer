<?php

namespace Symfony\Component\Mailer\Bridge\Infobip\Transport;

use Symfony\Component\Mailer\Exception\IncompleteDsnException;
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
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();

        if (null === $host) {
            throw new IncompleteDsnException('Infobip mailer DSN must contain a host.');
        }

        if ('infobip+api' === $schema) {
            return (new InfobipApiTransport($user, $this->client, $this->dispatcher, $this->logger))
                ->setHost($host)
            ;
        }

        throw new UnsupportedSchemeException($dsn, 'infobip', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['infobip', 'infobip+api'];
    }
}
