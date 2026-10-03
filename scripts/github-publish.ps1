# scripts/github-publish.ps1
# Automates creating the public repository on GitHub, pushing the code, and creating release v1.0.0

param (
    [string]$repoName = "wpcalibrate-llms-txt-manager",
    [string]$target = "" # Can be "wpcalibrate/wpcalibrate-llms-txt-manager" or leave empty to use default account
)

$ErrorActionPreference = "Stop"
$ghExe = "C:\Program Files\GitHub CLI\gh.exe"
$gitExe = "C:\Program Files\Git\cmd\git.exe"

$env:Path = "C:\Program Files\Git\cmd;C:\Program Files\GitHub CLI;" + $env:Path
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
Set-Location $projectRoot

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
    & $ghExe repo create $fullRepo --public --description "Create, generate, edit, import, validate, and serve site /llms.txt natively and virtually through WordPress."
    Write-Host "Repository created successfully on GitHub." -ForegroundColor Green
} catch {
    Write-Host "Repository might already exist on GitHub." -ForegroundColor Yellow
}

# 3. Configure remote and push main branch
Write-Host "Configuring remote and pushing main branch..." -ForegroundColor Cyan
$existingRemotes = & $gitExe remote
if ($existingRemotes -contains "origin") {
    & $gitExe remote remove origin
}
& $gitExe remote add origin "https://github.com/zeeshanraza-official/$repoName.git"
& $gitExe branch -M main

$token = (& $ghExe auth token).Trim()
$authPushUrl = "https://zeeshanraza-official:$token@github.com/zeeshanraza-official/$repoName.git"
Write-Host "Pushing main branch to GitHub..." -ForegroundColor Cyan
& $gitExe push -u $authPushUrl main
& $gitExe remote set-url origin "https://github.com/zeeshanraza-official/$repoName.git"

# 4. Create GitHub Release v1.0.0 with zip attached
Write-Host "Creating GitHub Release v1.0.0..." -ForegroundColor Cyan
$zipFile = Join-Path $projectRoot "wpcalibrate-llms-txt-manager.zip"
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
