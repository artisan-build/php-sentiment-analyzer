<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
    ])
    ->withPhpSets(
        php83: true
    )
    ->withSets([
        // Apply all PHP version upgrades up to PHP 8.3
        LevelSetList::UP_TO_PHP_83,

        // Code quality improvements
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,

        // Modern PHP practices
        SetList::PRIVATIZATION,
        SetList::NAMING,
    ])
    ->withParallel()
    ->withCache(__DIR__.'/var/cache/rector');
