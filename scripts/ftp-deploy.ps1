param (
    [string]$localDir = (Join-Path $PSScriptRoot "..\wpcalibrate-llms-txt-manager")
)

$ErrorActionPreference = "Stop"

$configFile = Join-Path $PSScriptRoot "ftp-config.json"
if (!(Test-Path $configFile)) {
    Write-Error "Config file not found: $configFile"
    exit 1
}

$config = Get-Content -Raw -Path $configFile | ConvertFrom-Json
$baseFtpUrl = "ftp://$($config.host):$($config.port)/" + $config.remotePluginPath.Trim('/') + "/"
$credentials = New-Object System.Net.NetworkCredential($config.user, $config.pass)

# Rebuild clean plugin folder before deployment
$buildScript = Join-Path $PSScriptRoot "build-plugin-folder.ps1"
if (Test-Path $buildScript) {
    & $buildScript
    Write-Host ""
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  WPCalibrate LLMs.txt Manager - FTP Automated Deployment" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "Target Remote: $baseFtpUrl"
Write-Host "Source Local:  $localDir"
Write-Host ""

$createdDirs = @{}

function New-RemoteDir([string]$dirPath) {
    if ([string]::IsNullOrWhiteSpace($dirPath) -or $dirPath -eq ".") { return }
    
    $normalized = $dirPath.Replace('\', '/').Trim('/')
    if ($createdDirs.ContainsKey($normalized)) { return }

    # Ensure parent directory first
    $parent = [System.IO.Path]::GetDirectoryName($dirPath)
    if (![string]::IsNullOrEmpty($parent) -and $parent -ne ".") {
        New-RemoteDir $parent
    }

    $dirUrl = $baseFtpUrl + $normalized
    try {
        $req = [System.Net.FtpWebRequest]::Create($dirUrl)
        $req.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $req.Credentials = $credentials
        $req.UsePassive = $true
        $req.UseBinary = $true
        $req.KeepAlive = $false
        $resp = $req.GetResponse()
        $resp.Close()
        Write-Host "  [DIR CREATED] $normalized" -ForegroundColor Yellow
    } catch {
        # 550 Directory already exists or cannot be created
    }
    $createdDirs[$normalized] = $true
}

[System.Net.ServicePointManager]::DefaultConnectionLimit = 16
[System.Net.ServicePointManager]::Expect100Continue = $false

function Send-RemoteFile([string]$localFilePath, [string]$relativePath) {
    $remotePath = $relativePath.Replace('\', '/').TrimStart('/')
    $remoteUrl = $baseFtpUrl + $remotePath

    # Ensure directory exists
    $dirName = [System.IO.Path]::GetDirectoryName($relativePath)
    if (![string]::IsNullOrEmpty($dirName)) {
        New-RemoteDir $dirName
    }

    $fileBytes = [System.IO.File]::ReadAllBytes($localFilePath)
    $maxRetries = 3
    $attempt = 0
    $uploaded = $false

    while (-not $uploaded -and $attempt -lt $maxRetries) {
        $attempt++
        try {
            $req = [System.Net.FtpWebRequest]::Create($remoteUrl)
            $req.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
            $req.Credentials = $credentials
            $req.UsePassive = $true
            $req.UseBinary = $true
            $req.KeepAlive = $false
            $req.Timeout = 20000
            $req.ReadWriteTimeout = 20000
            $req.ContentLength = $fileBytes.Length

            $requestStream = $req.GetRequestStream()
            $requestStream.Write($fileBytes, 0, $fileBytes.Length)
            $requestStream.Close()

            $resp = $req.GetResponse()
            $resp.Close()

            Write-Host "  [UPLOADED] $remotePath ($($fileBytes.Length) bytes)" -ForegroundColor Green
            $uploaded = $true
        } catch {
            if ($attempt -lt $maxRetries) {
                Write-Host "  [RETRY $attempt/$maxRetries] $remotePath ($($_.Exception.Message)) - waiting 2s..." -ForegroundColor Yellow
                Start-Sleep -Seconds 2
            } else {
                throw $_
            }
        }
    }
}

$resolvedLocalDir = (Resolve-Path $localDir).Path
$files = Get-ChildItem -Path $resolvedLocalDir -Recurse -File

$successCount = 0
$failCount = 0

$stopwatch = [System.Diagnostics.Stopwatch]::StartNew()

foreach ($file in $files) {
    $relPath = $file.FullName.Substring($resolvedLocalDir.Length).TrimStart('\', '/')
    try {
        Send-RemoteFile -localFilePath $file.FullName -relativePath $relPath
        $successCount++
        Start-Sleep -Milliseconds 150
    } catch {
        Write-Host "  [FAILED] $relPath - $($_.Exception.Message)" -ForegroundColor Red
        $failCount++
    }
}

$stopwatch.Stop()

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "Deployment Summary:" -ForegroundColor Cyan
Write-Host "  Files Uploaded: $successCount" -ForegroundColor Green
Write-Host "  Files Failed:   $failCount" -ForegroundColor $(if ($failCount -gt 0) { "Red" } else { "Green" })
Write-Host "  Elapsed Time:   $($stopwatch.Elapsed.TotalSeconds.ToString('F2')) seconds" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

if ($failCount -gt 0) {
    exit 1
} else {
    exit 0
}
