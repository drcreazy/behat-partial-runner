# Behat Partial Runner

The Partial Runner is a Behat extension which runs a subset of scenarios to parallelize Behat across multiple nodes.

This is a fork from the original author of extension [Anton Serdyuk](https://github.com/anton-siardziuk/behat-partial-runner) with improvements made by [TaysirTayyab](https://github.com/TaysirTayyab/behat-partial-runner).

Where as [shvetsgroup/ParallelRunner](https://github.com/shvetsgroup/ParallelRunner) is an excellent tool for parallelizing Behat on a _single_ machine, it unfortunately does not handle parallelizing Behat _across multiple_ machines. The Behat Partial runner fills this gap.

It is _very_ useful for CI services which offer parallelization such as CircleCI and GitHub Actions.

## Requirements

- PHP 8.2 or higher
- Behat ^3.27 or ~4.0

## Installation

```bash
composer require --dev m00t/behat-partial-runner
```

### Behat 3

Add the extension to your `behat.yml` file.

```yaml
default:
  extensions:
    Behat\PartialRunner\ServiceContainer\PartialRunnerExtension: ~
```

### Behat 4

Behat 4 dropped YAML configuration; register the extension in your `behat.php` file instead, using the config builder API.

```php
<?php

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;

return (new Config())
    ->withProfile((new Profile('default'))
        ->withSuite((new Suite('default'))
            ->withContexts(FeatureContext::class))
        ->withExtension(new Extension(
            Behat\PartialRunner\ServiceContainer\PartialRunnerExtension::class,
        )));
```

## Usage

Once configured, the parallelization can be invoked using the `--count-workers` and `--worker-number` options.

```bash
vendor/bin/behat --worker-number=0 --count-workers=2
vendor/bin/behat --worker-number=1 --count-workers=2
```

**Note:** The `--worker-number` expects a 0-indexed node index.

### How it works

Each scenario counts as one slot, and each data row of a scenario outline's `Examples` table counts as one slot. Worker N runs the slots whose position `p` satisfies `p % count-workers === N`, so worker 0 always runs the first slot of the suite. The counter is shared across the whole run, giving a global round-robin distribution across all workers.

### CircleCI

To integrate with CircleCI, add the following to your `.circleci/config.yml` file.

```yaml
tests:
    override: vendor/bin/behat --worker-number=$CIRCLE_NODE_INDEX --count-workers=$CIRCLE_NODE_TOTAL:
        parallel: true
```

### GitHub Actions

To integrate with GitHub Actions, add the following to your `.github/workflows/behat.yml` file.

```yaml
jobs:
  behat:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        worker-number: [0, 1]
    steps:
      - uses: actions/checkout@v4
      - run: composer install
      - run: vendor/bin/behat --worker-number=${{ matrix.worker-number }} --count-workers=2
```

## Development

### Setup

```bash
composer install
```

### Testing

```bash
vendor/bin/phpunit
```
