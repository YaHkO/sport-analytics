<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}
