param(
    [Parameter(Mandatory = $true)][ValidatePattern('^sha256:[a-f0-9]{64}$')][string]$Image,
    [ValidateSet('php', 'browser')][string]$Suite = 'php'
)
$ErrorActionPreference = 'Stop'
$repo = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../..'))
$parent = Join-Path $repo '.modulaciones-sandbox'
$run = Join-Path $parent ([guid]::NewGuid().ToString('N'))
$source = Join-Path $run 'scratch/source'
New-Item -ItemType Directory -Path $source -Force | Out-Null
try {
    # Parent-side snapshot: no environment files, production caches, logs, git, credentials or uploads.
    $dirs = @('app', 'config', 'routes', 'resources', 'database', 'tests', 'vendor', 'node_modules')
    $files = @('artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json', 'phpunit.modulaciones.xml')
    $bytes = 0L
    foreach ($dir in $dirs) {
        $root = Join-Path $repo $dir
        if (!(Test-Path -LiteralPath $root)) { throw "Missing offline dependency/source directory: $dir" }
        foreach ($item in Get-ChildItem -LiteralPath $root -Recurse -Force) {
            if ($item.Attributes -band [IO.FileAttributes]::ReparsePoint) { throw "Snapshot cannot include links: $dir" }
            if (!$item.PSIsContainer) { $bytes += $item.Length }
        }
        if ($bytes -gt 2147483648) { throw 'Snapshot exceeds 2 GiB limit.' }
        Copy-Item -LiteralPath $root -Destination $source -Recurse
    }
    foreach ($file in $files) {
        $from = Join-Path $repo $file
        if (Test-Path -LiteralPath $from) { Copy-Item -LiteralPath $from -Destination $source }
    }
    New-Item -ItemType Directory -Path (Join-Path $source 'bootstrap') | Out-Null
    Copy-Item -LiteralPath (Join-Path $repo 'bootstrap/app.php') -Destination (Join-Path $source 'bootstrap/app.php')
    $command = if ($Suite -eq 'php') {
        'mkdir -p /scratch/home /scratch/tmp /scratch/views && php vendor/phpunit/phpunit/phpunit -c phpunit.modulaciones.xml'
    } else {
        'mkdir -p /scratch/home /scratch/tmp && node node_modules/playwright/cli.js test --config tests/modulaciones/playwright.config.cjs'
    }
    # GNU timeout bounds the entire process tree; no pulls or ambient target environment.
    & docker run --rm --pull never --read-only --network none --ipc none --user 10001:10001 `
        --cap-drop ALL --security-opt no-new-privileges --memory 768m --memory-swap 768m --cpus 1 --pids-limit 64 `
        --ulimit cpu=90 --ulimit fsize=67108864 --ulimit nofile=256 `
        --tmpfs '/scratch:rw,nosuid,size=268435456,mode=1777' `
        --mount "type=bind,source=$source,target=/source,readonly" --workdir /source --entrypoint /usr/bin/env $Image `
        -i 'PATH=/usr/local/bin:/usr/bin:/bin' 'HOME=/scratch/home' 'TMPDIR=/scratch/tmp' 'TMP=/scratch/tmp' 'TEMP=/scratch/tmp' `
        'XDG_CACHE_HOME=/scratch/cache' 'PLAYWRIGHT_BROWSERS_PATH=/opt/playwright' `
        'APP_ENV=testing' 'APP_CONFIG_CACHE=/scratch/config.php' 'APP_SERVICES_CACHE=/scratch/services.php' 'APP_PACKAGES_CACHE=/scratch/packages.php' `
        /usr/bin/timeout -k 2 90 /bin/sh -c $command
    if ($LASTEXITCODE -ne 0) { throw "Isolated suite failed with exit code $LASTEXITCODE" }
} finally {
    $resolved = [IO.Path]::GetFullPath($run)
    $expected = [IO.Path]::GetFullPath($parent) + [IO.Path]::DirectorySeparatorChar
    if (!$resolved.StartsWith($expected, [StringComparison]::OrdinalIgnoreCase)) { throw 'Cleanup path escaped the sandbox directory.' }
    Remove-Item -LiteralPath $resolved -Recurse -Force
}
