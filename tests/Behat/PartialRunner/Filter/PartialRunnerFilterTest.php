<?php

namespace Tests\Behat\PartialRunner\Filter;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\ScenarioNode;
use InvalidArgumentException;
use Behat\PartialRunner\Filter\PartialRunnerFilter;

class PartialRunnerFilterTest extends FilterTest
{
    /**
     * This test is for making sure that invalid arguments for construction properly except.
     */
    public function testParallelWorkerFilter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Received bad arguments for ($countWorkers, $workerNumber): (10, -10).');
        new PartialRunnerFilter(10, -10);

        /***************************
         *   Invalid Arguments    *
         **************************/
        $this->expectException(InvalidArgumentException::class);
        new PartialRunnerFilter(1, -1);

        $this->expectException(InvalidArgumentException::class);
        new PartialRunnerFilter(0, 0);

        $this->expectException(InvalidArgumentException::class);
        new PartialRunnerFilter(-1, -1);

        $this->expectException(InvalidArgumentException::class);
        new PartialRunnerFilter(1, 2);
    }

    /**
     * This test makes sure that isFeatureMatch is always false, regardless of the construct arguments on the filter.
     */
    public function testIsFeatureMatch(): void
    {
        $feature = new FeatureNode(null, null, [], null, [], null, null, null, 1);

        $filter = new PartialRunnerFilter();
        self::assertFalse($filter->isFeatureMatch($feature));

        $filter = new PartialRunnerFilter(2, 1);
        self::assertFalse($filter->isFeatureMatch($feature));

        $filter = new PartialRunnerFilter(5, 2);
        self::assertFalse($filter->isFeatureMatch($feature));
    }

    /**
     * This test makes sure that isScenarioMatch is always true, regardless of the construct arguments on the filter.
     */
    public function testIsScenarioMatch(): void
    {
        $scenario = new ScenarioNode(null, [], [], null, 2);

        $filter = new PartialRunnerFilter();
        self::assertTrue($filter->isScenarioMatch($scenario));

        $filter = new PartialRunnerFilter(2, 1);
        self::assertTrue($filter->isScenarioMatch($scenario));

        $filter = new PartialRunnerFilter(5, 2);
        self::assertTrue($filter->isScenarioMatch($scenario));
    }

    /**
     * This tests that FeatureFilter works correctly with the default construction arguments for the filter.
     */
    public function testFeatureFilterDefaults(): void
    {
        $filter = new PartialRunnerFilter();
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(4, $scenarios);
        self::assertSame('Scenario#1', $scenarios[0]->getTitle());
        self::assertSame('Scenario#2', $scenarios[1]->getTitle());
        self::assertSame('Scenario#3', $scenarios[2]->getTitle());

        self::assertInstanceOf(OutlineNode::class, $scenarios[2]);
        self::assertTrue($scenarios[2]->hasExamples());
        self::assertSame([
            ['action' => 'act#1', 'outcome' => 'out#1'],
            ['action' => 'act#2', 'outcome' => 'out#2'],
            ['action' => 'act#3', 'outcome' => 'out#3'],
        ], $scenarios[2]->getExampleTable()->getColumnsHash());

        self::assertSame('Scenario#4', $scenarios[3]->getTitle());
    }

    /**
     * This tests that FeatureFilter works properly when there are 2 test nodes.
     */
    public function testFeatureFilterNodes2(): void
    {
        /*****************
         *    Node 1    *
         ****************/
        $filter = new PartialRunnerFilter(2, 0);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(2, $scenarios);
        self::assertSame('Scenario#1', $scenarios[0]->getTitle());
        self::assertSame('Scenario#3', $scenarios[1]->getTitle());

        self::assertInstanceOf(OutlineNode::class, $scenarios[1]);
        self::assertTrue($scenarios[1]->hasExamples());
        self::assertSame([
            ['action' => 'act#1', 'outcome' => 'out#1'],
            ['action' => 'act#3', 'outcome' => 'out#3'],
        ], $scenarios[1]->getExampleTable()->getColumnsHash());

        /*****************
         *    Node 2    *
         ****************/
        $filter = new PartialRunnerFilter(2, 1);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(3, $scenarios);
        self::assertSame('Scenario#2', $scenarios[0]->getTitle());
        self::assertSame('Scenario#3', $scenarios[1]->getTitle());

        self::assertInstanceOf(OutlineNode::class, $scenarios[1]);
        self::assertTrue($scenarios[1]->hasExamples());
        self::assertSame([
            ['action' => 'act#2', 'outcome' => 'out#2'],
        ], $scenarios[1]->getExampleTable()->getColumnsHash());

        self::assertSame('Scenario#4', $scenarios[2]->getTitle());
    }

    /**
     * This tests if FeatureFilter works properly when there are 3 test nodes.
     */
    public function testFeatureFilterNodes3(): void
    {
        /*****************
         *    Node 1    *
         ****************/
        $filter = new PartialRunnerFilter(3, 0);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(2, $scenarios);
        self::assertSame('Scenario#1', $scenarios[0]->getTitle());
        self::assertSame('Scenario#3', $scenarios[1]->getTitle());

        self::assertInstanceOf(OutlineNode::class, $scenarios[1]);
        self::assertTrue($scenarios[1]->hasExamples());
        self::assertSame([
            ['action' => 'act#2', 'outcome' => 'out#2'],
        ], $scenarios[1]->getExampleTable()->getColumnsHash());

        /*****************
         *    Node 2    *
         ****************/
        $filter = new PartialRunnerFilter(3, 1);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(2, $scenarios);
        self::assertSame('Scenario#2', $scenarios[0]->getTitle());
        self::assertSame('Scenario#3', $scenarios[1]->getTitle());

        self::assertInstanceOf(OutlineNode::class, $scenarios[1]);
        self::assertTrue($scenarios[1]->hasExamples());
        self::assertSame([
            ['action' => 'act#3', 'outcome' => 'out#3'],
        ], $scenarios[1]->getExampleTable()->getColumnsHash());

        /*****************
         *    Node 3    *
         ****************/
        $filter = new PartialRunnerFilter(3, 2);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(2, $scenarios);
        self::assertSame('Scenario#3', $scenarios[0]->getTitle());

        self::assertInstanceOf(OutlineNode::class, $scenarios[0]);
        self::assertTrue($scenarios[0]->hasExamples());
        self::assertSame([
            ['action' => 'act#1', 'outcome' => 'out#1'],
        ], $scenarios[0]->getExampleTable()->getColumnsHash());

        self::assertSame('Scenario#4', $scenarios[1]->getTitle());
    }

    /**
     * This tests if FeatureFilter works properly when there are 4 test nodes.
     */
    public function testFeatureFilterNodes4(): void
    {
        /*****************
         *    Node 1    *
         ****************/
        $filter = new PartialRunnerFilter(4, 0);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(2, $scenarios);
        self::assertSame('Scenario#1', $scenarios[0]->getTitle());
        self::assertSame('Scenario#3', $scenarios[1]->getTitle());

        self::assertInstanceOf(OutlineNode::class, $scenarios[1]);
        self::assertTrue($scenarios[1]->hasExamples());
        self::assertSame([
            ['action' => 'act#3', 'outcome' => 'out#3'],
        ], $scenarios[1]->getExampleTable()->getColumnsHash());

        /*****************
         *    Node 2   *
         ****************/
        $filter = new PartialRunnerFilter(4, 1);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(2, $scenarios);
        self::assertSame('Scenario#2', $scenarios[0]->getTitle());
        self::assertSame('Scenario#4', $scenarios[1]->getTitle());

        /*****************
         *    Node 3    *
         ****************/
        $filter = new PartialRunnerFilter(4, 2);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(1, $scenarios);
        self::assertSame('Scenario#3', $scenarios[0]->getTitle());

        self::assertInstanceOf(OutlineNode::class, $scenarios[0]);
        self::assertTrue($scenarios[0]->hasExamples());
        self::assertSame([
            ['action' => 'act#1', 'outcome' => 'out#1'],
        ], $scenarios[0]->getExampleTable()->getColumnsHash());

        /*****************
         *    Node 4    *
         ****************/
        $filter = new PartialRunnerFilter(4, 3);
        $feature = $filter->filterFeature($this->getParsedFeature());
        $scenarios = $feature->getScenarios();

        self::assertCount(1, $scenarios);
        self::assertSame('Scenario#3', $scenarios[0]->getTitle());

        self::assertInstanceOf(OutlineNode::class, $scenarios[0]);
        self::assertTrue($scenarios[0]->hasExamples());
        self::assertSame([
            ['action' => 'act#2', 'outcome' => 'out#2'],
        ], $scenarios[0]->getExampleTable()->getColumnsHash());
    }
}
