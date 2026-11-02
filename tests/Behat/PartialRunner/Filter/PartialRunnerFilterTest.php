<?php

namespace Tests\Behat\PartialRunner\Filter;

use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\RuleNode;
use InvalidArgumentException;
use Behat\PartialRunner\Filter\PartialRunnerFilter;
use PHPUnit\Framework\Attributes\DataProvider;

class PartialRunnerFilterTest extends FilterTest
{
    #[DataProvider('invalidWorkerConfigurationProvider')]
    public function testInvalidWorkerConfigurationIsRejected(int $countWorkers, int $workerNumber, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new PartialRunnerFilter($countWorkers, $workerNumber);
    }

    public static function invalidWorkerConfigurationProvider(): array
    {
        return [
            'zero workers' => [0, 0, '--count-workers must be at least 1, got 0.'],
            'negative workers' => [-2, 0, '--count-workers must be at least 1, got -2.'],
            'negative worker' => [2, -1, '--worker-number must be a non-negative integer, got -1.'],
            'negative worker with many workers' => [10, -10, '--worker-number must be a non-negative integer, got -10.'],
            'worker equals workers' => [2, 2, '--worker-number (2) must be less than --count-workers (2).'],
            'worker exceeds workers' => [1, 2, '--worker-number (2) must be less than --count-workers (1).'],
        ];
    }

    /**
     * isFeatureMatch is always false: this filter selects individual scenarios, not whole features, and the
     * constant false makes Gherkin::load() drop features left without scenarios.
     */
    public function testIsFeatureMatchIsAlwaysFalse(): void
    {
        $feature = $this->getParsedStandardFeature();

        self::assertFalse((new PartialRunnerFilter())->isFeatureMatch($feature));
        self::assertFalse((new PartialRunnerFilter(2, 1))->isFeatureMatch($feature));
        self::assertFalse((new PartialRunnerFilter(5, 2))->isFeatureMatch($feature));
    }

    /**
     * This tests that FeatureFilter works correctly with the default construction arguments for the filter.
     */
    public function testFeatureFilterDefaults(): void
    {
        $this->assertFilteredDistribution(1, 0, [
            ['title' => 'Scenario#1', 'rows' => null],
            ['title' => 'Scenario#2', 'rows' => null],
            ['title' => 'Scenario#3', 'rows' => [
                ['action' => 'act#1', 'outcome' => 'out#1'],
                ['action' => 'act#2', 'outcome' => 'out#2'],
                ['action' => 'act#3', 'outcome' => 'out#3'],
            ]],
            ['title' => 'Scenario#4', 'rows' => null],
        ]);
    }

    /**
     * This tests if FeatureFilter works properly when there are 2 test nodes.
     */
    public function testFeatureFilterNodes2(): void
    {
        $this->assertFilteredDistribution(2, 0, [
            ['title' => 'Scenario#1', 'rows' => null],
            ['title' => 'Scenario#3', 'rows' => [
                ['action' => 'act#1', 'outcome' => 'out#1'],
                ['action' => 'act#3', 'outcome' => 'out#3'],
            ]],
        ]);

        $this->assertFilteredDistribution(2, 1, [
            ['title' => 'Scenario#2', 'rows' => null],
            ['title' => 'Scenario#3', 'rows' => [
                ['action' => 'act#2', 'outcome' => 'out#2'],
            ]],
            ['title' => 'Scenario#4', 'rows' => null],
        ]);
    }

    /**
     * This tests if FeatureFilter works properly when there are 3 test nodes.
     */
    public function testFeatureFilterNodes3(): void
    {
        $this->assertFilteredDistribution(3, 0, [
            ['title' => 'Scenario#1', 'rows' => null],
            ['title' => 'Scenario#3', 'rows' => [
                ['action' => 'act#2', 'outcome' => 'out#2'],
            ]],
        ]);

        $this->assertFilteredDistribution(3, 1, [
            ['title' => 'Scenario#2', 'rows' => null],
            ['title' => 'Scenario#3', 'rows' => [
                ['action' => 'act#3', 'outcome' => 'out#3'],
            ]],
        ]);

        $this->assertFilteredDistribution(3, 2, [
            ['title' => 'Scenario#3', 'rows' => [
                ['action' => 'act#1', 'outcome' => 'out#1'],
            ]],
            ['title' => 'Scenario#4', 'rows' => null],
        ]);
    }

    /**
     * This tests if FeatureFilter works properly when there are 4 test nodes.
     */
    public function testFeatureFilterNodes4(): void
    {
        $this->assertFilteredDistribution(4, 0, [
            ['title' => 'Scenario#1', 'rows' => null],
            ['title' => 'Scenario#3', 'rows' => [
                ['action' => 'act#3', 'outcome' => 'out#3'],
            ]],
        ]);

        $this->assertFilteredDistribution(4, 1, [
            ['title' => 'Scenario#2', 'rows' => null],
            ['title' => 'Scenario#4', 'rows' => null],
        ]);

        $this->assertFilteredDistribution(4, 2, [
            ['title' => 'Scenario#3', 'rows' => [
                ['action' => 'act#1', 'outcome' => 'out#1'],
            ]],
        ]);

        $this->assertFilteredDistribution(4, 3, [
            ['title' => 'Scenario#3', 'rows' => [
                ['action' => 'act#2', 'outcome' => 'out#2'],
            ]],
        ]);
    }

    /**
     * The slot counter is deliberately not reset between features: a single filter instance processes the whole
     * suite, so the distribution is a global round-robin across the run.
     */
    public function testSlotCounterContinuesAcrossFeatures(): void
    {
        $filter = new PartialRunnerFilter(4, 0);

        $first = $filter->filterFeature($this->getParsedStandardFeature());
        $second = $filter->filterFeature($this->getParsedStandardFeature());

        $firstChildren = $first->getExecutableChildren();
        self::assertCount(2, $firstChildren);
        self::assertSame('Scenario#1', $firstChildren[0]->getTitle());
        self::assertInstanceOf(OutlineNode::class, $firstChildren[1]);
        self::assertSame([
            ['action' => 'act#3', 'outcome' => 'out#3'],
        ], $firstChildren[1]->getExampleTables()[0]->getColumnsHash());

        $secondChildren = $second->getExecutableChildren();
        self::assertCount(1, $secondChildren);
        self::assertInstanceOf(OutlineNode::class, $secondChildren[0]);
        self::assertSame([
            ['action' => 'act#1', 'outcome' => 'out#1'],
        ], $secondChildren[0]->getExampleTables()[0]->getColumnsHash());
    }

    /**
     * Outlines with multiple Examples tables keep each surviving table independently, preserving its tags.
     */
    public function testMultipleExampleTablesAreFilteredIndependently(): void
    {
        // Worker 0 keeps one row in each table.
        $worker0 = (new PartialRunnerFilter(2, 0))->filterFeature($this->getParsedMultiExamplesFeature());
        $outline0 = $worker0->getExecutableChildren()[0];
        self::assertInstanceOf(OutlineNode::class, $outline0);
        $tables0 = $outline0->getExampleTables();
        self::assertCount(2, $tables0);
        self::assertSame(['@fast'], $tables0[0]->getTags());
        self::assertSame([
            ['action' => 'act#1', 'outcome' => 'out#1'],
        ], $tables0[0]->getColumnsHash());
        self::assertSame(['@slow'], $tables0[1]->getTags());
        self::assertSame([
            ['action' => 'act#3', 'outcome' => 'out#3'],
        ], $tables0[1]->getColumnsHash());

        // Worker 1 keeps the other rows.
        $worker1 = (new PartialRunnerFilter(2, 1))->filterFeature($this->getParsedMultiExamplesFeature());
        $outline1 = $worker1->getExecutableChildren()[0];
        self::assertInstanceOf(OutlineNode::class, $outline1);
        $tables1 = $outline1->getExampleTables();
        self::assertCount(2, $tables1);
        self::assertSame(['@fast'], $tables1[0]->getTags());
        self::assertSame([
            ['action' => 'act#2', 'outcome' => 'out#2'],
        ], $tables1[0]->getColumnsHash());
        self::assertSame(['@slow'], $tables1[1]->getTags());
        self::assertSame([
            ['action' => 'act#4', 'outcome' => 'out#4'],
        ], $tables1[1]->getColumnsHash());

        // A worker with no slot anywhere in the outline gets the whole outline dropped.
        $worker4 = (new PartialRunnerFilter(5, 4))->filterFeature($this->getParsedMultiExamplesFeature());
        self::assertSame([], $worker4->getExecutableChildren());
    }

    /**
     * Rules are allocated recursively: a rule whose scenarios all belong to other workers is dropped, and a
     * surviving rule keeps its background. The feature background is always preserved.
     */
    public function testRulesAreAllocatedRecursively(): void
    {
        // Worker 0 keeps Scenario#1, the outline row act#1 and Scenario#2 (which is inside the first rule per the
        // gherkin spec); the second rule has no slots for this worker and is dropped.
        $worker0 = (new PartialRunnerFilter(2, 0))->filterFeature($this->getParsedRulesFeature());
        $children0 = $worker0->getExecutableChildren();
        self::assertCount(2, $children0);
        self::assertSame('Scenario#1', $children0[0]->getTitle());
        self::assertInstanceOf(RuleNode::class, $children0[1]);
        self::assertSame('Business rule', $children0[1]->getTitle());
        $ruleChildren0 = $children0[1]->getChildren();
        self::assertCount(3, $ruleChildren0);
        self::assertInstanceOf(BackgroundNode::class, $ruleChildren0[0]);
        self::assertInstanceOf(OutlineNode::class, $ruleChildren0[1]);
        self::assertSame([
            ['action' => 'act#1', 'outcome' => 'out#1'],
        ], $ruleChildren0[1]->getExampleTables()[0]->getColumnsHash());
        self::assertSame('Scenario#2', $ruleChildren0[2]->getTitle());

        // Worker 1 keeps the rule scenario and the outline row act#2 in the first rule, and the scenario of the
        // second rule.
        $worker1 = (new PartialRunnerFilter(2, 1))->filterFeature($this->getParsedRulesFeature());
        $children1 = $worker1->getExecutableChildren();
        self::assertCount(2, $children1);
        self::assertInstanceOf(RuleNode::class, $children1[0]);
        self::assertSame('Business rule', $children1[0]->getTitle());
        $ruleChildren1 = $children1[0]->getChildren();
        self::assertCount(3, $ruleChildren1);
        self::assertInstanceOf(BackgroundNode::class, $ruleChildren1[0]);
        self::assertSame('Rule scenario#1', $ruleChildren1[1]->getTitle());
        self::assertInstanceOf(OutlineNode::class, $ruleChildren1[2]);
        self::assertSame([
            ['action' => 'act#2', 'outcome' => 'out#2'],
        ], $ruleChildren1[2]->getExampleTables()[0]->getColumnsHash());
        self::assertInstanceOf(RuleNode::class, $children1[1]);
        self::assertSame('Second rule', $children1[1]->getTitle());
        $ruleChildren1b = $children1[1]->getChildren();
        self::assertCount(1, $ruleChildren1b);
        self::assertSame('Second rule scenario', $ruleChildren1b[0]->getTitle());

        self::assertNotNull($worker0->getBackground());
        self::assertNotNull($worker1->getBackground());

        // Worker 0 of 4 keeps only Scenario#1 and Scenario#2: the second rule is dropped entirely, and the first
        // rule survives with only its background and Scenario#2.
        $worker0of4 = (new PartialRunnerFilter(4, 0))->filterFeature($this->getParsedRulesFeature());
        $children0of4 = $worker0of4->getExecutableChildren();
        self::assertCount(2, $children0of4);
        self::assertSame('Scenario#1', $children0of4[0]->getTitle());
        self::assertInstanceOf(RuleNode::class, $children0of4[1]);
        self::assertSame('Business rule', $children0of4[1]->getTitle());
        $ruleChildren0of4 = $children0of4[1]->getChildren();
        self::assertCount(2, $ruleChildren0of4);
        self::assertInstanceOf(BackgroundNode::class, $ruleChildren0of4[0]);
        self::assertSame('Scenario#2', $ruleChildren0of4[1]->getTitle());
    }

    /**
     * A feature where no slot belongs to this worker keeps its metadata but has no scenarios left; dropping it is
     * Gherkin::load()'s job (via the isFeatureMatch contract).
     */
    public function testFeatureWithNoSurvivingScenarios(): void
    {
        $feature = (new PartialRunnerFilter(7, 6))->filterFeature($this->getParsedStandardFeature());

        self::assertSame([], $feature->getExecutableChildren());
        self::assertFalse($feature->hasScenarios());
        self::assertSame('Long feature with outline', $feature->getTitle());
    }

    /**
     * @param list<array{title: string, rows: ?list<array<string, string>>}> $expected
     */
    private function assertFilteredDistribution(int $countWorkers, int $workerNumber, array $expected): void
    {
        $feature = (new PartialRunnerFilter($countWorkers, $workerNumber))->filterFeature($this->getParsedStandardFeature());
        $children = $feature->getExecutableChildren();

        self::assertCount(count($expected), $children);

        foreach ($expected as $index => $expectation) {
            self::assertSame($expectation['title'], $children[$index]->getTitle());

            if ($expectation['rows'] !== null) {
                self::assertInstanceOf(OutlineNode::class, $children[$index]);
                self::assertSame($expectation['rows'], $children[$index]->getExampleTables()[0]->getColumnsHash());
            }
        }
    }
}
