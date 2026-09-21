<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths([__DIR__])
	->withRootFiles()
	->withPhpSets(php74: true)
	->withSkip([__DIR__ . '/lang', __DIR__ . '/vendor']);
