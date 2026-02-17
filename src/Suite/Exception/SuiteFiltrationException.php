<?php

declare(strict_types=1);

namespace SyliusLabs\SuiteTagsExtension\Suite\Exception;

use Behat\Testwork\Exception\TestworkException;

final class SuiteFiltrationException extends \InvalidArgumentException implements TestworkException
{
    public function __construct(string $message, \Throwable|null $previousException = null)
    {
        parent::__construct($message, 0, $previousException);
    }
}
