# Behat Partial Runner

The Partial Runner is a Behat extension which runs a subset of scenarios to parallelize Behat across multiple nodes.

This is a fork from the original author of extension [Anton Serdyuk](https://github.com/anton-siardziuk/behat-partial-runner) with improvements made by [TaysirTayyab](https://github.com/TaysirTayyab/behat-partial-runner).

Where as [shvetsgroup/ParallelRunner](https://github.com/shvetsgroup/ParallelRunner) is an excellent tool for parallelizing Behat on a _single_ machine, it unfortunately does not handle parallelizing Behat _across multiple_ machines. The Behat Partial runner fills this gap.

It is _very_ useful for CI services which offer parallelization such as CircleCI and TravisCI.

## Requirements

- PHP 8.1 or higher
- Behat ^3.0

## Installation

```bash
composer require --dev m00t/behat-partial-runner
```

Then add the extension to your `behat.yml` file.

```yaml
default:
  extensions:
    Behat\PartialRunner\ServiceContainer\PartialRunnerExtension: ~
```

## Usage

Once configured, the parallelization can be invoked using the `--count-workers` and `--worker-number` options.

```bash
vendor/bin/behat --worker-number=0 --count-workers=2
vendor/bin/behat --worker-number=1 --count-workers=2
```

**Note:** The `--worker-number` expects a 0-indexed node index.

### CircleCI

To integrate with CircleCI, add the following to your `.circleci/config.yml` file.

```yaml
tests:
    override: vendor/bin/behat --worker-number=$CIRCLE_NODE_INDEX --count-workers=$CIRCLE_NODE_TOTAL:
        parallel: true
```

### TravisCI

To integrate with TravisCI, add the following to your `.travis.yml` file.

```yaml
script:
    - vendor/bin/behat --worker-number=$CI_NODE_INDEX --count-workers=$CI_NODE_TOTAL
env:
    global:
        - CI_NODE_TOTAL=2
    matrix:
        - CI_NODE_INDEX=0
        - CI_NODE_INDEX=1
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
