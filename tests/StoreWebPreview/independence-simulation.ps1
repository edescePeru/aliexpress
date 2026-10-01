param([Parameter(Mandatory=$true)][string]$OutputDirectory)
$ErrorActionPreference = 'Stop'
$auditRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '../..')).Path
Set-Location -LiteralPath $auditRoot
$auditSource = Join-Path $auditRoot 'project-catalog'
$auditDisabled = Join-Path $auditRoot 'project-catalog.__disabled'
if ((Split-Path -Parent $auditSource) -ne $auditRoot -or (Split-Path -Parent $auditDisabled) -ne $auditRoot) { throw 'Paths outside project' }
if (!(Test-Path -LiteralPath $auditSource) -or (Test-Path -LiteralPath $auditDisabled)) { throw 'Unexpected reference paths' }
if (!(Test-Path -LiteralPath $OutputDirectory)) { throw 'Use an existing local output directory' }
function Get-ReferenceHashes {
    @(Get-ChildItem -LiteralPath $auditSource -Recurse -File | ForEach-Object {
        @{path=$_.FullName.Substring($auditSource.Length);hash=(Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash}
    })
}
$auditBefore = Get-ReferenceHashes
$auditStarted = (Get-Date).ToString('o')
$auditPass = $false
$env:STORE_WEB_LIVE_QA='1'
$env:STORE_WEB_QA_OUTPUT=Join-Path $OutputDirectory 'independence-shop.json'
$env:STORE_WEB_DETAIL_QA_OUTPUT=Join-Path $OutputDirectory 'independence-detail.json'
try {
    Rename-Item -LiteralPath $auditSource -NewName 'project-catalog.__disabled'
    if (Test-Path -LiteralPath $auditSource) { throw 'Reference still active' }
    Write-Output 'REFERENCE DISABLED: normal Laravel and fresh authenticated kernel regression starting'
    & 'C:\wamp64\bin\php\php7.3.33\php.exe' vendor/bin/phpunit --filter 'StoreWeb(LocalPreview|ProductDetail|AuthenticatedLive)' --log-junit docs/store-web/independence-phpunit.xml
    if ($LASTEXITCODE -ne 0) { throw 'PHP regression failed' }
    node tests/StoreWebPreview/local-bridge-qa.cjs
    if ($LASTEXITCODE -ne 0) { throw 'Anonymous browser regression failed' }
    Copy-Item -LiteralPath 'docs/store-web/local-preview-qa.json' -Destination 'docs/store-web/independence-fixture-qa.json'
    node tests/StoreWebPreview/independence-qa.cjs $env:STORE_WEB_QA_OUTPUT $env:STORE_WEB_DETAIL_QA_OUTPUT
    if ($LASTEXITCODE -ne 0) { throw 'Authenticated response browser regression failed' }
    $auditPass = $true
} finally {
    if ((Test-Path -LiteralPath $auditDisabled) -and !(Test-Path -LiteralPath $auditSource)) {
        Rename-Item -LiteralPath $auditDisabled -NewName 'project-catalog'
    }
    $auditRestored = (Test-Path -LiteralPath $auditSource) -and !(Test-Path -LiteralPath $auditDisabled)
    $auditAfter = Get-ReferenceHashes
    $auditUnchanged = @($auditBefore | Where-Object { $entry=$_; !($auditAfter | Where-Object { $_.path -eq $entry.path -and $_.hash -eq $entry.hash }) }).Count -eq 0 -and $auditBefore.Count -eq $auditAfter.Count
    @{started=$auditStarted;ended=(Get-Date).ToString('o');source=$auditSource;disabled=$auditDisabled;testsPassed=$auditPass;
        restored=$auditRestored;referenceFiles=$auditBefore.Count;referenceHashesUnchanged=$auditUnchanged} |
        ConvertTo-Json | Set-Content -LiteralPath 'docs/store-web/independence-removal-simulation.json' -Encoding UTF8
    Write-Output "REFERENCE RESTORED: $auditRestored; reference hashes unchanged: $auditUnchanged"
    if (!$auditRestored -or !$auditUnchanged) { throw 'Reference restoration verification failed' }
}
