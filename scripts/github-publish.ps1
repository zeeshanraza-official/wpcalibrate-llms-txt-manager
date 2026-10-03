# scripts/github-publish.ps1
# Automates creating the public repository on GitHub, pushing the code, and creating release v1.0.0

param (
    [string]$repoName = "wpcalibrate-llms-txt-manager",
    [string]$target = "" # Can be "wpcalibrate/wpcalibrate-llms-txt-manager" or leave empty to use default account
)

$ErrorActionPreference = "Stop"
$ghExe = "C:\Program Files\GitHub CLI\gh.exe"
$gitExe = "C:\Program Files\Git\cmd\git.exe"

if (!(Test-Path $ghExe)) {
    Write-Error "GitHub CLI not found at $ghExe"
    exit 1
}

# 1. Check login status
try {
    & $ghExe auth status
} catch {
    Write-Host ""
    Write-Host "Please log into GitHub first by running:" -ForegroundColor Yellow
    Write-Host '  & "C:\Program Files\GitHub CLI\gh.exe" auth login --web' -ForegroundColor Cyan
    exit 1
}

# 2. Determine target repository name
$fullRepo = if ($target) { $target } else { $repoName }

Write-Host "Creating/Verifying public GitHub repository: $fullRepo..." -ForegroundColor Cyan

# Create public repo if it does not exist
try {
    & $ghExe repo create $fullRepo --public --source=. --remote=origin --description="Create, generate, edit, import, validate, and serve site /llms.txt natively and virtually through WordPress."
    Write-Host "Repository created successfully." -ForegroundColor Green
} catch {
    Write-Host "Repository might already exist or remote is already set. Checking remote..." -ForegroundColor Yellow
}

# 3. Push main branch
Write-Host "Pushing main branch to origin..." -ForegroundColor Cyan
& $gitExe branch -M main
& $gitExe push -u origin main

# 4. Create GitHub Release v1.0.0 with zip attached
Write-Host "Creating GitHub Release v1.0.0..." -ForegroundColor Cyan
$zipFile = Join-Path $PSScriptRoot "..\wpcalibrate-llms-txt-manager.zip"
if (Test-Path $zipFile) {
    try {
        & $ghExe release create v1.0.0 $zipFile --title "v1.0.0 - Production Release" --notes "Initial production release of WPCalibrate LLMs.txt Manager with native virtual serving, structured builder, raw editor, and in-dashboard GitHub auto-updater."
        Write-Host "GitHub release v1.0.0 published with zip archive attached!" -ForegroundColor Green
    } catch {
        Write-Host "Release v1.0.0 might already exist." -ForegroundColor Yellow
    }
} else {
    Write-Host "Zip package not found at $zipFile. Please run scripts\build-plugin-folder.ps1 first." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "  GitHub Repository and Release Published Successfully!   " -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
