<?php

namespace Behat\PartialRunner\Filter;

use Behat\Gherkin\Filter\FeatureFilterInterface;
use Behat\Gherkin\Node\ExampleTableNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\RuleNode;
use Behat\Gherkin\Node\ScenarioInterface;
use InvalidArgumentException;

/**
 * A scenario filter which filters on individual scenarios and outlines. Useful for separating tests onto multiple
 * worker nodes.
 *
 * Allocation model: every runnable "slot" consumes one position from a shared, monotonically increasing counter.
 * A regular scenario is one slot; each data row of an outline Examples table is one slot; header rows and
 * backgrounds are free. Worker N keeps the slots whose position p satisfies p % countWorkers === N, so worker 0
 * always keeps the first slot of the run.
 *
 * The counter is shared across every feature filtered by this instance: in a Behat run a single filter instance
 * processes the whole suite, giving a global round-robin distribution. It is deliberately not reset between
 * features.
 *
 * Rule nodes are recursed into; a rule whose scenarios are all allocated elsewhere is dropped (its background is
 * kept when at least one scenario survives). Outlines with multiple Examples tables keep each surviving table
 * independently (with its tags, name and description) and are dropped when no table survives. Note: a 0-row
 * Examples table is dropped, where historically it was kept empty — the net runtime effect is identical (no
 * examples would run).
 */
final class PartialRunnerFilter implements FeatureFilterInterface
{
    /** Position of the next slot to be allocated. */
    private int $nextSlot = 0;

    /**
     * @param int $countWorkers The total number of workers
     * @param int $workerNumber The current worker (0-indexed)
     */
    public function __construct(
        private readonly int $countWorkers = 1,
        private readonly int $workerNumber = 0,
    ) {
        if ($countWorkers <= 0) {
            throw new InvalidArgumentException(sprintf('--count-workers must be at least 1, got %d.', $countWorkers));
        }

        if ($workerNumber < 0) {
            throw new InvalidArgumentException(sprintf('--worker-number must be a non-negative integer, got %d.', $workerNumber));
        }

        if ($workerNumber >= $countWorkers) {
            throw new InvalidArgumentException(sprintf('--worker-number (%d) must be less than --count-workers (%d).', $workerNumber, $countWorkers));
        }
    }

    /**
     * This filter never matches a whole feature: it only selects individual scenarios and example rows. Returning
     * false makes Gherkin::load() drop features left without scenarios.
     */
    public function isFeatureMatch(FeatureNode $feature): bool
    {
        return false;
    }

    public function filterFeature(FeatureNode $feature): FeatureNode
    {
        $children = [];

        foreach ($feature->getExecutableChildren() as $child) {
            $filtered = $child instanceof RuleNode ? $this->filterRule($child) : $this->filterScenario($child);

            if ($filtered !== null) {
                $children[] = $filtered;
            }
        }

        return $feature->withScenarios($children);
    }

    /** @return RuleNode|null null when no scenario inside the rule belongs to this worker */
    private function filterRule(RuleNode $rule): ?RuleNode
    {
        $children = [];

        foreach ($rule->getExecutableChildren() as $scenario) {
            $filtered = $this->filterScenario($scenario);

            if ($filtered !== null) {
                $children[] = $filtered;
            }
        }

        if ($children === []) {
            return null;
        }

        if ($rule->hasBackground()) {
            array_unshift($children, $rule->getBackground());
        }

        return $rule->withChildren($children);
    }

    /** @return ScenarioInterface|null null when this worker has no slot in the scenario */
    private function filterScenario(ScenarioInterface $scenario): ?ScenarioInterface
    {
        // Outlines without Examples tables are treated as a single slot, like any scenario.
        if (!$scenario instanceof OutlineNode || !$scenario->hasExamples()) {
            return $this->isNextSlotMine() ? $scenario : null;
        }

        $tables = [];

        foreach ($scenario->getExampleTables() as $table) {
            $filteredTable = $this->filterExampleTable($table);

            if ($filteredTable !== null) {
                $tables[] = $filteredTable;
            }
        }

        return $tables === [] ? null : $scenario->withTables($tables);
    }

    /** @return ExampleTableNode|null null when no data row of this table belongs to this worker */
    private function filterExampleTable(ExampleTableNode $table): ?ExampleTableNode
    {
        $rows = [];

        foreach ($table->getTable() as $line => $row) {
            // The first row is the header; it is kept for free. Every data row is one slot.
            if ($rows === [] || $this->isNextSlotMine()) {
                $rows[$line] = $row;
            }
        }

        return count($rows) > 1 ? $table->withTable($rows) : null;
    }

    /**
     * Consumes the next slot position and returns whether it belongs to this worker.
     * Position p belongs to worker N iff p % countWorkers === N.
     */
    private function isNextSlotMine(): bool
    {
        return $this->nextSlot++ % $this->countWorkers === $this->workerNumber;
    }
}
