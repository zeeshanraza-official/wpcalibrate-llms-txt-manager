# scripts/build-plugin-folder.ps1
# Automates deleting the old plugin distribution folder and recreating a fresh, clean build.

$ErrorActionPreference = "Stop"

$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
$distDir = Join-Path $projectRoot "wpcalibrate-llms-txt-manager"
$zipFile = Join-Path $projectRoot "wpcalibrate-llms-txt-manager.zip"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  WPCalibrate LLMs.txt Manager - Rebuilding Plugin Folder" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Delete old plugin distribution folder if it exists
if (Test-Path $distDir) {
    Write-Host "Deleting old plugin folder: $distDir" -ForegroundColor Yellow
    Remove-Item -Path $distDir -Recurse -Force
}

# 2. Recreate fresh plugin directory
Write-Host "Creating fresh plugin folder: $distDir" -ForegroundColor Cyan
New-Item -ItemType Directory -Path $distDir -Force | Out-Null

# 3. Production files and directories to include
$rootFiles = @(
    "wpcalibrate-llms-txt-manager.php",
    "uninstall.php",
    "readme.txt",
    "README.md",
    "LICENSE",
    "CHANGELOG_AI.md",
    "TODO_AI.md",
    "CLAUDE.md",
    "PROJECT_MEMORY.md",
    "TROUBLESHOOTING.md"
)

$subDirs = @(
    "admin",
    "includes",
    "assets",
    "branding",
    "languages"
)

# Copy root files
foreach ($file in $rootFiles) {
    $src = Join-Path $projectRoot $file
    if (Test-Path $src) {
        Copy-Item -Path $src -Destination $distDir -Force
        Write-Host "  [COPIED] $file" -ForegroundColor Green
    }
}

# Copy directories
foreach ($dir in $subDirs) {
    $src = Join-Path $projectRoot $dir
    if (Test-Path $src) {
        $dest = Join-Path $distDir $dir
        Copy-Item -Path $src -Destination $dest -Recurse -Force
        Write-Host "  [COPIED DIR] $dir" -ForegroundColor Green
    }
}

# 4. Clean up any unintended artifacts from the build folder
Get-ChildItem -Path $distDir -Recurse -Include "Thumbs.db", ".DS_Store" -File | Remove-Item -Force

# 5. Recreate distribution zip
if (Test-Path $zipFile) {
    Remove-Item -Path $zipFile -Force
}

Write-Host "Generating fresh zip archive: $zipFile" -ForegroundColor Cyan
Compress-Archive -Path $distDir -DestinationPath $zipFile -Force

Write-Host "Plugin folder and zip successfully rebuilt." -ForegroundColor Green
