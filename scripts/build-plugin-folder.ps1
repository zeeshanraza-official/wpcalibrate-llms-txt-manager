# scripts/build-plugin-folder.ps1
# Automates clean packaging adhering strictly to WordPress distribution standards:
# - Creates 'wpcalibrate-llms-txt-manager/' inside project root
# - Excludes all AI instructions, memory, planning, troubleshooting, and dev artifacts
# - Generates installable ZIP 'wpcalibrate-llms-txt-manager-1.0.0.zip' in parent directory
# - Generates root 'wpcalibrate-llms-txt-manager.zip' for local/GitHub releases

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$parentDir = (Resolve-Path (Join-Path $projectRoot "..")).Path
$pluginSlug = "wpcalibrate-llms-txt-manager"
$version = "1.0.0"

$distDir = Join-Path $projectRoot $pluginSlug
$parentZipFile = Join-Path $parentDir "$pluginSlug-$version.zip"
$rootZipFile = Join-Path $projectRoot "$pluginSlug.zip"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  WPCalibrate LLMs.txt Manager - Production Packaging     " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Clean up old distribution folder
if (Test-Path $distDir) {
    Write-Host "Removing existing distribution directory: $distDir" -ForegroundColor Yellow
    Remove-Item -Path $distDir -Recurse -Force
}

# 2. Create fresh plugin distribution directory
Write-Host "Creating clean plugin directory: $distDir" -ForegroundColor Cyan
New-Item -ItemType Directory -Path $distDir -Force | Out-Null

# 3. Production-only files and directories
# STRICTLY EXCLUDED: .agents, .claude, AGENTS.md, CLAUDE.md, PROJECT_MEMORY.md, 
# TROUBLESHOOTING.md, CHANGELOG_AI.md, TODO_AI.md, tests/, scripts/, .vscode/, secrets
$prodFiles = @(
    "wpcalibrate-llms-txt-manager.php",
    "uninstall.php",
    "readme.txt",
    "README.md",
    "LICENSE"
)

$prodDirs = @(
    "admin",
    "includes",
    "assets",
    "branding",
    "languages"
)

# Copy production root files
foreach ($file in $prodFiles) {
    $src = Join-Path $projectRoot $file
    if (Test-Path $src) {
        Copy-Item -Path $src -Destination $distDir -Force
        Write-Host "  [COPIED] $file" -ForegroundColor Green
    } else {
        Write-Warning "Required production file missing: $src"
    }
}

# Copy production subdirectories
foreach ($dir in $prodDirs) {
    $src = Join-Path $projectRoot $dir
    if (Test-Path $src) {
        $dest = Join-Path $distDir $dir
        Copy-Item -Path $src -Destination $dest -Recurse -Force
        Write-Host "  [COPIED DIR] $dir" -ForegroundColor Green
    } else {
        Write-Warning "Required production directory missing: $src"
    }
}

# 4. Clean up any unintended OS/editor artifacts from inside the dist folder
$artifacts = Get-ChildItem -Path $distDir -Recurse -Include "Thumbs.db", ".DS_Store", "*.log" -File
foreach ($art in $artifacts) {
    Remove-Item -Path $art.FullName -Force
    Write-Host "  [STRIPPED] $($art.Name)" -ForegroundColor Yellow
}

# 5. Create installable ZIPs
if (Test-Path $parentZipFile) { Remove-Item -Path $parentZipFile -Force }
if (Test-Path $rootZipFile) { Remove-Item -Path $rootZipFile -Force }

Write-Host ""
Write-Host "Generating installable ZIP in parent directory:" -ForegroundColor Cyan
Write-Host "  -> $parentZipFile" -ForegroundColor Green

Push-Location $projectRoot
try {
    tar -a -c -f $parentZipFile $pluginSlug
    Copy-Item -Path $parentZipFile -Destination $rootZipFile -Force
    Write-Host "Generating release ZIP in project root:" -ForegroundColor Cyan
    Write-Host "  -> $rootZipFile" -ForegroundColor Green
} catch {
    Write-Warning "tar command failed or unavailable. Falling back to Compress-Archive."
    Compress-Archive -Path $distDir -DestinationPath $parentZipFile -Force
    Compress-Archive -Path $distDir -DestinationPath $rootZipFile -Force
} finally {
    Pop-Location
}

Write-Host ""
Write-Host "Packaging complete." -ForegroundColor Green
