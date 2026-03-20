<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Tests\SyliusLabs\SuiteTagsExtension\Behat\Context\TestContext;

return (new Config())
    ->withProfile((new Profile('default'))
        ->withSuite((new Suite('default'))
            ->withContexts(TestContext::class)
        )
    )
;
