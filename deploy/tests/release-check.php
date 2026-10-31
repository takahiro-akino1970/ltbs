<?php

namespace Symfony\Component\Console\Input {
    class InputOption
    {
        public const VALUE_REQUIRED = 2;
    }
}

namespace Deployer {

require __DIR__.'/../vendor/autoload.php';

$settings = [];
$tasks = [];
$output = [];

function set($key, $value): void { global $settings; $settings[$key] = $value; }
function get($key) { global $settings; return $settings[$key]; }
function option($name, $shortcut, $mode, $description): void {}
function import($file): void {}
function desc($description): void {}
function task($name, $callback): void { global $tasks; $tasks[$name] = $callback; }
function input() { global $arguments; return $arguments; }
function writeln($message): void { global $output; $output[] = $message; }
function run($command): string { global $response, $lastCommand; $lastCommand = $command; return $response; }

require __DIR__.'/../deploy.php';

$cases = [
    [null, ['tag_name' => 'v1.2.3', 'draft' => false, 'prerelease' => false], true],
    ['v1.2.3-test.1', ['tag_name' => 'v1.2.3-test.1', 'draft' => false, 'prerelease' => true], true],
    [null, ['tag_name' => 'v1.2.3', 'draft' => true, 'prerelease' => false], false],
    [null, ['tag_name' => 'v1.2.3-test.1', 'draft' => false, 'prerelease' => true], false],
    ['v1.2.3', ['tag_name' => 'v1.2.4', 'draft' => false, 'prerelease' => false], false],
    ['vbad;command', [], false],
    [null, [], false],
];

foreach ($cases as [$tag, $metadata, $expected]) {
    $arguments = new class($tag) {
        public function __construct(private ?string $tag) {}
        public function getOption(string $name): ?string { return $this->tag; }
    };
    $response = json_encode($metadata, JSON_THROW_ON_ERROR);
    $output = [];
    $success = true;
    try {
        $tasks['release:check']();
    } catch (\RuntimeException $exception) {
        $success = false;
    }
    if ($success !== $expected) {
        throw new \RuntimeException('Unexpected result for '.var_export($tag, true));
    }
    if ($success) {
        $endpoint = $tag === null ? '/latest' : '/tags/'.$tag;
        if (! str_contains($lastCommand, $endpoint) || count($output) !== 4
            || ! str_contains($output[1], '/app:'.$metadata['tag_name'])
            || ! str_contains($output[2], '/web:'.$metadata['tag_name'])) {
            throw new \RuntimeException('Unexpected endpoint or output.');
        }
    }
}

echo "Release resolution: 7 cases passed (no SSH connection).\n";
}
