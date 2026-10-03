param (
    [string]$subPath = ""
)

$config = Get-Content -Raw -Path (Join-Path $PSScriptRoot "ftp-config.json") | ConvertFrom-Json

$ftpUrl = "ftp://$($config.host):$($config.port)/" + $subPath.TrimStart('/')
Write-Host "Connecting to $ftpUrl ..."

$request = [System.Net.FtpWebRequest]::Create($ftpUrl)
$request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
$request.Credentials = New-Object System.Net.NetworkCredential($config.user, $config.pass)
$request.UsePassive = $true
$request.UseBinary = $true
$request.KeepAlive = $false

try {
    $response = $request.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    $content = $reader.ReadToEnd()
    $reader.Close()
    $response.Close()
    Write-Host "Directory listing for $subPath :"
    Write-Host $content
} catch {
    Write-Host "Error: $($_.Exception.Message)"
}
