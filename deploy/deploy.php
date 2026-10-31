<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

set('ssh_control_path', '/home/deployer/.ssh-control-%C');
set('github_repository', 'takahiro-akino1970/ltbs');


option('tag', null, InputOption::VALUE_REQUIRED, 'Release tag; defaults to the latest stable GitHub Release');

$hostsFile = __DIR__.'/hosts.yml';

if (is_file($hostsFile)) {
    import($hostsFile);
}

desc('Check SSH access and the remote Docker Compose version without deploying');
task('ssh:check', function (): void {
    run('whoami');
    run('docker compose version');
});

desc('Resolve a GitHub Release without pulling images or deploying');
task('release:check', function (): void {
    $tag = input()->getOption('tag');
    if ($tag !== null && (! is_string($tag) || ! preg_match('/^v[A-Za-z0-9_][A-Za-z0-9_.-]{0,126}$/D', $tag))) {
        throw new \RuntimeException('The tag must start with v and be a valid Docker image tag.');
    }

    $repository = get('github_repository');
    if (! is_string($repository) || ! preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~D', $repository)) {
        throw new \RuntimeException('Invalid github_repository.');
    }

    $endpoint = $tag === null ? 'latest' : 'tags/'.rawurlencode($tag);
    $url = escapeshellarg("https://api.github.com/repos/{$repository}/releases/{$endpoint}");
    $json = run(<<<'SH'
set -eu
token_file="$HOME/.ltbs-github-token"
if [ ! -f "$token_file" ] || [ ! -r "$token_file" ] || [ ! -O "$token_file" ]; then
    echo 'GitHub token file must exist, be readable and owned by the SSH user.' >&2
    exit 1
fi
case "$(stat -c '%a' "$token_file")" in
    400|600) ;;
    *) echo 'GitHub token file permissions must be 400 or 600.' >&2; exit 1 ;;
esac
token=$(cat "$token_file")
if [ -z "$token" ]; then
    echo 'GitHub token file is empty.' >&2
    exit 1
fi
printf 'header = "Authorization: Bearer %s"\n' "$token" |
    curl --config - --fail --silent --show-error --connect-timeout 15 --max-time 60 \
    --header 'Accept: application/vnd.github+json' \
    --header 'X-GitHub-Api-Version: 2022-11-28'
SH
        .' '.$url);

    $release = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($release)
        || ! isset($release['tag_name'], $release['draft'], $release['prerelease'])
        || ! is_string($release['tag_name'])
        || ! preg_match('/^v[A-Za-z0-9_][A-Za-z0-9_.-]{0,126}$/D', $release['tag_name'])
        || $release['draft'] !== false
        || ! is_bool($release['prerelease'])
        || ($tag === null && $release['prerelease'])
        || ($tag !== null && $tag !== $release['tag_name'])) {
        throw new \RuntimeException('GitHub returned an invalid or ineligible release.');
    }

    writeln('Release: '.$release['tag_name']);
    foreach (['app', 'web'] as $image) {
        writeln("Image: ghcr.io/{$repository}/{$image}:{$release['tag_name']}");
    }
    writeln('Release metadata checked only; images were not inspected, pulled or deployed.');
});
