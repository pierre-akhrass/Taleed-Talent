param(
    [Parameter(Position = 0)]
    [ValidateSet('init', 'up', 'down', 'status', 'test', 'build', 'reset-test', 'logs', 'help')]
    [string] $Action = 'help'
)

$ErrorActionPreference = 'Stop'
$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
Push-Location $repositoryRoot

function Invoke-Compose {
    param([string[]] $ComposeArguments)

    & docker compose -f compose.dev.yaml @ComposeArguments
    if ($LASTEXITCODE -ne 0) {
        throw "docker compose failed with exit code $LASTEXITCODE."
    }
}

try {
    switch ($Action) {
        'init' {
            $environmentFile = Join-Path $repositoryRoot 'backend/.env'
            if (-not (Test-Path $environmentFile)) {
                Copy-Item (Join-Path $repositoryRoot 'backend/.env.example') $environmentFile
            }
            $appKeySetting = Get-Content $environmentFile | Where-Object { $_ -match '^APP_KEY\s*=' } | Select-Object -First 1
            $appKeyValue = if ($appKeySetting) { ($appKeySetting -split '=', 2)[1].Trim().Trim('"').Trim("'") } else { '' }

            Invoke-Compose @('up', '-d', 'mysql', 'mailpit')
            if ([string]::IsNullOrWhiteSpace($appKeyValue)) {
                Invoke-Compose @('run', '--rm', 'backend', 'php', 'artisan', 'key:generate', '--force')
            }
            Invoke-Compose @('up', '-d', '--build')
            Invoke-Compose @('exec', '-T', 'backend', 'php', 'artisan', 'migrate', '--force')
        }
        'up' {
            Invoke-Compose @('up', '-d', '--build')
        }
        'down' {
            Invoke-Compose @('down')
        }
        'status' {
            Invoke-Compose @('ps')
        }
        'test' {
            Invoke-Compose @('exec', '-T', 'vite', 'npm', 'run', 'test:domain')
            Invoke-Compose @('exec', '-T', 'vite', 'npm', 'run', 'check')
            Invoke-Compose @('exec', '-T', 'mysql', 'mysql', '-uroot', '-proot-local-only', '-e', 'CREATE DATABASE IF NOT EXISTS talent_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci')
            Invoke-Compose @('exec', '-T', 'mysql', 'mysql', '-uroot', '-proot-local-only', '-e', "GRANT ALL PRIVILEGES ON talent_test.* TO 'talent'@'%' ")
            Invoke-Compose @('exec', '-e', 'APP_ENV=testing', '-e', 'DB_CONNECTION=mysql', '-e', 'DB_DATABASE=talent_test', '-T', 'backend', 'php', 'artisan', 'test')
        }
        'build' {
            Invoke-Compose @('exec', '-T', 'vite', 'npm', 'run', 'build:backend')
        }
        'reset-test' {
            Write-Warning 'This deletes only the reserved synthetic-test database talent_test. It does not touch talent.'
            Invoke-Compose @('exec', '-T', 'mysql', 'mysql', '-uroot', '-proot-local-only', '-e', "DROP DATABASE IF EXISTS talent_test; CREATE DATABASE talent_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci; GRANT ALL PRIVILEGES ON talent_test.* TO 'talent'@'%';")
        }
        'logs' {
            Invoke-Compose @('logs', '--tail=100')
        }
        'help' {
            Write-Output 'Usage: .\scripts\dev\dev.ps1 <init|up|down|status|test|build|reset-test|logs>'
            Write-Output 'init creates a local key only when missing and applies pending local migrations; down preserves all Compose volumes.'
            Write-Output 'test uses the reserved synthetic-only MySQL schema talent_test, never the app database talent.'
            Write-Output 'reset-test deletes/recreates only talent_test; it never resets talent.'
            Write-Output 'This script does not reset data or modify Windows hosts/certificate trust.'
        }
    }
}
finally {
    Pop-Location
}