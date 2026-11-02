<?php

namespace Tests\Behat\PartialRunner\Filter;

use Behat\Gherkin\GherkinCompatibilityMode;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Parser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Base class for filter testing which sets up a Gherkin parser and feature fixtures.
 */
abstract class FilterTest extends TestCase
{
    protected function getParser(GherkinCompatibilityMode $mode = GherkinCompatibilityMode::LEGACY): Parser
    {
        return new Parser(
            new Lexer(
                new ArrayKeywords([
                    'en' => [
                        'feature'          => 'Feature',
                        'background'       => 'Background',
                        'scenario'         => 'Scenario',
                        'scenario_outline' => 'Scenario Outline|Scenario Template',
                        'examples'         => 'Examples|Scenarios',
                        'given'            => 'Given',
                        'when'             => 'When',
                        'then'             => 'Then',
                        'and'              => 'And',
                        'but'              => 'But',
                    ],
                ]),
            ),
            $mode,
        );
    }

    protected function getParsedFeature(
        string $gherkin,
        GherkinCompatibilityMode $mode = GherkinCompatibilityMode::LEGACY,
    ): FeatureNode {
        return $this->getParser($mode)->parse($gherkin)
            ?? throw new RuntimeException('Test fixture failed to parse.');
    }

    /**
     * A feature with four scenarios (two regular, one outline with three examples, one regular).
     * Slot order: Scenario#1, Scenario#2, act#1, act#2, act#3, Scenario#4.
     */
    protected function getStandardFeature(): string
    {
        return <<<'GHERKIN'
Feature: Long feature with outline
  In order to accomplish objective
  As a someone
  I have to be able to do something

  Scenario: Scenario#1
    Given initial step
    When action occurs
    Then outcomes should be visible

  Scenario: Scenario#2
    Given initial step
    And another initial step
    When action occurs
    Then outcomes should be visible

  Scenario Outline: Scenario#3
    When <action> occurs
    Then <outcome> should be visible

    Examples:
      | action | outcome |
      | act#1  | out#1   |
      | act#2  | out#2   |
      | act#3  | out#3   |

  Scenario: Scenario#4
    When an occurs
    Then the outcome should be visible
GHERKIN;
    }

    protected function getParsedStandardFeature(): FeatureNode
    {
        return $this->getParsedFeature($this->getStandardFeature());
    }

    /**
     * An outline with two tagged Examples tables.
     * Slot order: act#1, act#2 (in @fast), act#3, act#4 (in @slow).
     */
    protected function getParsedMultiExamplesFeature(): FeatureNode
    {
        return $this->getParsedFeature(
            <<<'GHERKIN'
Feature: Multi examples feature

  Scenario Outline: Multi examples outline
    When <action> occurs
    Then <outcome> should be visible

    @fast
    Examples:
      | action | outcome |
      | act#1  | out#1   |
      | act#2  | out#2   |

    @slow
    Examples:
      | action | outcome |
      | act#3  | out#3   |
      | act#4  | out#4   |
GHERKIN,
            GherkinCompatibilityMode::GHERKIN_32,
        );
    }

    /**
     * A feature with two rules, each containing its own background and/or scenarios.
     * Slot order: Scenario#1, Rule scenario#1, act#1, act#2, Scenario#2, Second rule scenario.
     * Note: Scenario#2 is parsed inside the first Rule (a gherkin Rule spans until the next Rule or EOF).
     */
    protected function getParsedRulesFeature(): FeatureNode
    {
        return $this->getParsedFeature(
            <<<'GHERKIN'
Feature: Feature with rules
  Background:
    Given feature background step

  Scenario: Scenario#1
    When action occurs
    Then outcomes should be visible

  Rule: Business rule
    Background:
      Given rule background step

    Scenario: Rule scenario#1
      When action occurs
      Then outcomes should be visible

    Scenario Outline: Rule outline
      When <action> occurs
      Then <outcome> should be visible

      Examples:
        | action | outcome |
        | act#1  | out#1   |
        | act#2  | out#2   |

  Scenario: Scenario#2
    When action occurs
    Then outcomes should be visible

  Rule: Second rule
    Scenario: Second rule scenario
      When action occurs
      Then outcomes should be visible
GHERKIN,
            GherkinCompatibilityMode::GHERKIN_32,
        );
    }
}
