<?php
declare(strict_types=1);

namespace Symfony\Component\Mailer\Bridge\Infobip\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The bundle is a temporary solution.
 * It will not be needed anymore once integrated directly into Symfony codebase
 */
final class InfobipMailerBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new InfobipMailerExtension();
    }
}
