<?php

declare(strict_types=1);

/*
 * Configuration file for twigcs.
 * Limite l’analyse aux templates custom (modules + thèmes) et évite vendor / core.
 */

return Twigcs\Config\Config::create()
  ->setName('crypto-custom')
  ->setSeverity('error') // ou 'warning' si tu veux moins strict
  ->setReporter('console')
  ->setRuleSet(Twigcs\Ruleset\Official::class)
  // Trouve les templates uniquement dans les modules et thèmes custom.
  ->addFinder(Twigcs\Finder\TemplateFinder::create()->in(__DIR__ . '/web/modules/custom'))
  ->addFinder(Twigcs\Finder\TemplateFinder::create()->in(__DIR__ . '/web/themes/custom'));
