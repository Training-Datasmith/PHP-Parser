<?php

declare(strict_types=1);

/**
 * Cluster pin check for PHP-Parser: no index-pinned org packages (PHPUnit GAP -> Packagist).
 * Verifies installed.php + installed.json stay consistent and demos fail on wrong SHAs.
 */

$root = dirname(__DIR__);
$installedPhp = $root . '/vendor/composer/installed.php';
$installedJson = $root . '/vendor/composer/installed.json';

/** @var array<string, array{alias?: string, reference?: string}> */
$expected = [];

/**
 * @return list<string>
 */
function assert_cluster_pins(array $expected, array $versions, array $jsonPackages): array
{
    $errors = [];

    $installedByName = [];
    foreach ($jsonPackages as $package) {
        if (!isset($package['name'], $package['source']['reference'])) {
            continue;
        }
        $installedByName[$package['name']] = strtolower((string) $package['source']['reference']);
    }

    foreach ($expected as $name => $want) {
        if (!isset($versions[$name])) {
            $errors[] = sprintf('check-cluster-pins: %s is not installed.', $name);
            continue;
        }

        $pkg = $versions[$name];
        if (isset($want['alias'])) {
            $aliases = $pkg['aliases'] ?? [];
            if (!in_array($want['alias'], $aliases, true)) {
                $errors[] = sprintf(
                    'check-cluster-pins: %s alias mismatch (expected %s in aliases %s).',
                    $name,
                    $want['alias'],
                    json_encode($aliases)
                );
            }
        }

        $reference = $pkg['reference'] ?? null;
        if (!is_string($reference) || $reference === '') {
            $errors[] = sprintf('check-cluster-pins: %s has no reference in installed.php.', $name);
        } elseif (isset($want['reference']) && !hash_equals($want['reference'], $reference)) {
            $errors[] = sprintf(
                'check-cluster-pins: %s installed.php reference mismatch (expected %s, got %s).',
                $name,
                $want['reference'],
                $reference
            );
        }

        if (!isset($installedByName[$name])) {
            $errors[] = sprintf('check-cluster-pins: %s missing from installed.json with source.reference.', $name);
            continue;
        }

        if (isset($want['reference']) && !hash_equals(strtolower($want['reference']), $installedByName[$name])) {
            $errors[] = sprintf(
                'check-cluster-pins: %s installed.json source.reference mismatch (expected %s, got %s).',
                $name,
                $want['reference'],
                $installedByName[$name]
            );
        }
    }

    return $errors;
}

if (!is_readable($installedPhp) || !is_readable($installedJson)) {
    fwrite(STDERR, "check-cluster-pins: run composer update first (vendor/composer missing).\n");
    exit(1);
}

/** @var array{versions: array<string, array{reference?: string, aliases?: list<string>}>} $installed */
$installed = require $installedPhp;
$versions = $installed['versions'] ?? [];

$rawJson = file_get_contents($installedJson);
if ($rawJson === false) {
    fwrite(STDERR, "check-cluster-pins: could not read installed.json.\n");
    exit(1);
}

/** @var array{packages?: list<array<string, mixed>>, 'packages-dev'?: list<array<string, mixed>>} $data */
$data = json_decode($rawJson, true);
if (!is_array($data)) {
    fwrite(STDERR, "check-cluster-pins: invalid installed.json.\n");
    exit(1);
}

$jsonPackages = array_merge($data['packages'] ?? [], $data['packages-dev'] ?? []);

$errors = assert_cluster_pins($expected, $versions, $jsonPackages);
if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . "\n");
    }
    exit(1);
}

// Wrong-SHA demo: installed.php reference (mutate first dev package if any expected, else phpunit)
$demoVersions = $versions;
if (isset($demoVersions['phpunit/phpunit'])) {
    $demoVersions['phpunit/phpunit']['reference'] = str_repeat('0', 40);
    $demoPhpErrors = assert_cluster_pins(['phpunit/phpunit' => ['reference' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa']], $demoVersions, $jsonPackages);
    $phpDemoFailed = false;
    foreach ($demoPhpErrors as $error) {
        if (stripos($error, 'installed.php') !== false && stripos($error, 'reference') !== false) {
            $phpDemoFailed = true;
            break;
        }
    }
    if (!$phpDemoFailed) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo for installed.php did not fail as expected.\n");
        exit(1);
    }
} else {
  // Empty expected map: demo using synthetic package entry.
    $demoVersions['__demo_pkg__'] = ['reference' => str_repeat('0', 40), 'version' => '1.0.0', 'pretty_version' => '1.0.0'];
    $demoPhpErrors = assert_cluster_pins(['__demo_pkg__' => ['reference' => str_repeat('f', 40)]], $demoVersions, $jsonPackages);
    if ($demoPhpErrors === []) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo for installed.php did not fail as expected.\n");
        exit(1);
    }
}

// Wrong-SHA demo: installed.json source.reference
$mutatedJson = $jsonPackages;
foreach ($mutatedJson as $i => $package) {
    if (($package['name'] ?? '') === 'phpunit/phpunit') {
        $mutatedJson[$i]['source']['reference'] = str_repeat('f', 40);
        break;
    }
}
$demoJsonErrors = assert_cluster_pins(
    isset($versions['phpunit/phpunit']) ? ['phpunit/phpunit' => ['reference' => str_repeat('a', 40)]] : [],
    $versions,
    $mutatedJson
);
if (isset($versions['phpunit/phpunit'])) {
    $jsonDemoFailed = false;
    foreach ($demoJsonErrors as $error) {
        if (stripos($error, 'installed.json') !== false && stripos($error, 'source.reference') !== false) {
            $jsonDemoFailed = true;
            break;
        }
    }
    if (!$jsonDemoFailed) {
        fwrite(STDERR, "check-cluster-pins: wrong-SHA demo for installed.json did not fail as expected.\n");
        exit(1);
    }
}

// Packagist PHPUnit guard (GAP: not index-pinned)
if (!isset($versions['phpunit/phpunit'])) {
    fwrite(STDERR, "check-cluster-pins: phpunit/phpunit must be installed from Packagist (^9.6.33).\n");
    exit(1);
}
$phpunitRef = $versions['phpunit/phpunit']['reference'] ?? '';
if (!is_string($phpunitRef) || strlen($phpunitRef) !== 40) {
    fwrite(STDERR, "check-cluster-pins: phpunit/phpunit missing valid installed.php reference.\n");
    exit(1);
}

fwrite(STDOUT, "check-cluster-pins: index org packages satisfied (0 pinned); Packagist PHPUnit guard passed.\n");
exit(0);
