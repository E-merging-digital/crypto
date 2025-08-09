<?php

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Drupal\DrupalExtension\Context\MinkContext;

/**
 * Defines Behat step definitions for end-to-end tests.
 *
 * This context extends MinkContext to leverage DrupalExtension and Mink
 * utilities (browser interactions, sessions, and assertions).
 */
final class FeatureContext extends MinkContext implements Context {
  // Add custom step definitions here as needed.
}
