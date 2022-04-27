<?php
declare(strict_types=1);

namespace Symfony\Component\Mailer\Bridge\Infobip\Bundle;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Mailer\Bridge\Infobip\Transport\InfobipTransportFactory;

/**
 * Config copied from vendor/symfony/framework-bundle/Resources/config/mailer_transports.php
 */
final class InfobipMailerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $definition = new ChildDefinition('mailer.transport_factory.abstract');
        $definition->setClass(InfobipTransportFactory::class);
        $definition->addTag('mailer.transport_factory');
        $container->setDefinition('mailer.transport_factory.infobip', $definition);
    }
}
