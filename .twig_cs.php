<?php

declare(strict_types=1);

use FriendsOfTwig\Twigcs\Config\Config;
use FriendsOfTwig\Twigcs\Finder\TemplateFinder;
use FriendsOfTwig\Twigcs\Ruleset\Official;

return Config::create()
  ->setName('crypto-custom')
  ->setSeverity('warning')
  ->setReporter('console')
  ->setRuleSet(Official::class)
  ->addFinder(TemplateFinder::create()->in(__DIR__ . '/web/modules/custom'))
  ->addFinder(TemplateFinder::create()->in(__DIR__ . '/web/themes/custom'));
