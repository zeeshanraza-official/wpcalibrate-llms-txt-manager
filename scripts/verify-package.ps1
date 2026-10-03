# scripts/verify-package.ps1
# Inspects and verifies the installable ZIP contents and structure

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$parentDir = (Resolve-Path (Join-Path $projectRoot "..")).Path
$zipPath = Join-Path $parentDir "wpcalibrate-llms-txt-manager-1.0.0.zip"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  WPCalibrate LLMs.txt Manager - Package Verification     " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "Target ZIP: $zipPath"
Write-Host ""

if (!(Test-Path $zipPath)) {
    Write-Error "ZIP file not found: $zipPath"
    exit 1
}

Add-Type -AssemblyName System.IO.Compression.FileSystem

$zip = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
$entries = $zip.Entries

Write-Host "Total entries in ZIP: $($entries.Count)" -ForegroundColor Cyan
Write-Host ""

$disallowedPatterns = @(
    "CLAUDE",
    "PROJECT_MEMORY",
    "TROUBLESHOOTING",
    "CHANGELOG_AI",
    "TODO_AI",
    "AGENTS",
    "\.agents",
    "\.claude",
    "\.git",
    "\.vscode",
    "ftp-config",
    "scripts[\\/]",
    "tests[\\/]"
)

$violations = @()
$nonTopLevel = @()
$fileList = @()

foreach ($entry in $entries) {
    $fullName = $entry.FullName
    $fileList += $fullName

    # Check top-level folder prefix
    if ($fullName -notmatch "^wpcalibrate-llms-txt-manager[\\/]") {
        $nonTopLevel += $fullName
    }

    # Check disallowed patterns
    foreach ($pattern in $disallowedPatterns) {
        if ($fullName -match $pattern) {
            $violations += "$fullName (matched $pattern)"
        }
    }
}

$zip.Dispose()

# Verification 1: Top-level folder
if ($nonTopLevel.Count -eq 0) {
    Write-Host "[PASS] Exactly one top-level 'wpcalibrate-llms-txt-manager/' folder container." -ForegroundColor Green
} else {
    Write-Host "[FAIL] Entries found outside top-level container: $($nonTopLevel.Count)" -ForegroundColor Red
    $nonTopLevel | ForEach-Object { Write-Host "       $_" -ForegroundColor Red }
}

# Verification 2: Strict exclusions
if ($violations.Count -eq 0) {
    Write-Host "[PASS] Zero AI, dev, testing, or credential files found in ZIP archive." -ForegroundColor Green
} else {
    Write-Host "[FAIL] Disallowed files found in archive: $($violations.Count)" -ForegroundColor Red
    $violations | ForEach-Object { Write-Host "       $_" -ForegroundColor Red }
}

# Verification 3: Required core files present
$requiredFiles = @(
    "wpcalibrate-llms-txt-manager/wpcalibrate-llms-txt-manager.php",
    "wpcalibrate-llms-txt-manager/uninstall.php",
    "wpcalibrate-llms-txt-manager/readme.txt",
    "wpcalibrate-llms-txt-manager/README.md",
    "wpcalibrate-llms-txt-manager/LICENSE",
    "wpcalibrate-llms-txt-manager/admin/class-admin.php",
    "wpcalibrate-llms-txt-manager/admin/class-menu.php",
    "wpcalibrate-llms-txt-manager/includes/class-plugin.php",
    "wpcalibrate-llms-txt-manager/includes/class-router.php",
    "wpcalibrate-llms-txt-manager/includes/class-generator.php",
    "wpcalibrate-llms-txt-manager/includes/class-validator.php",
    "wpcalibrate-llms-txt-manager/includes/class-importer.php",
    "wpcalibrate-llms-txt-manager/includes/class-options.php",
    "wpcalibrate-llms-txt-manager/includes/class-github-updater.php",
    "wpcalibrate-llms-txt-manager/assets/css/admin.css",
    "wpcalibrate-llms-txt-manager/assets/js/admin.js",
    "wpcalibrate-llms-txt-manager/branding/icon-white.png"
)

$missingRequired = @()
foreach ($req in $requiredFiles) {
    $normalizedReq = $req.Replace('\', '/')
    $found = $false
    foreach ($f in $fileList) {
        if ($f.Replace('\', '/') -eq $normalizedReq) {
            $found = $true
            break
        }
    }
    if (!$found) {
        $missingRequired += $req
    }
}

if ($missingRequired.Count -eq 0) {
    Write-Host "[PASS] All required production core, admin, asset, and branding files verified present." -ForegroundColor Green
} else {
    Write-Host "[FAIL] Missing required files: $($missingRequired.Count)" -ForegroundColor Red
    $missingRequired | ForEach-Object { Write-Host "       $_" -ForegroundColor Red }
}

Write-Host ""
Write-Host "Archive Inspection Details:" -ForegroundColor Cyan
$fileList | Sort-Object | ForEach-Object { Write-Host "  $_" }

Write-Host ""
if ($violations.Count -eq 0 -and $nonTopLevel.Count -eq 0 -and $missingRequired.Count -eq 0) {
    Write-Host "==========================================================" -ForegroundColor Green
    Write-Host "  ZIP Package Verification: 100% PASSED                   " -ForegroundColor Green
    Write-Host "==========================================================" -ForegroundColor Green
    exit 0
} else {
    Write-Host "==========================================================" -ForegroundColor Red
    Write-Host "  ZIP Package Verification: FAILED                        " -ForegroundColor Red
    Write-Host "==========================================================" -ForegroundColor Red
    exit 1
}
