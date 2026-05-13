<?php

namespace Tests\Behat\PartialRunner\Filter;

use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use Behat\Gherkin\Node\FeatureNode;
use PHPUnit\Framework\TestCase;

/**
 * Base class for filter testing which sets up a Gherkin Feature with several scenarios and a parser.
 */
abstract class FilterTest extends TestCase
{
    protected function getParser(): Parser
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
        );
    }

    protected function getGherkinFeature(): string
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

    protected function getParsedFeature(): ?FeatureNode
    {
        return $this->getParser()->parse($this->getGherkinFeature());
    }
}
