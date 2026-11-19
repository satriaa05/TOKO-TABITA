# Uji checkout konkurensi nyata dengan 5 proses server & SQLite WAL.
# Jalankan dari root project di PowerShell.

$ErrorActionPreference = 'Stop'
$php = 'php'   # PHP 8.5 dari Herd Lite; cukup untuk server uji
$ports = 8101..8105

$env:DB_BUSY_TIMEOUT = '5000'
$env:DB_JOURNAL_MODE = 'wal'
$env:CONCURRENCY_PORTS = ($ports -join ',')

Write-Host "Menjalankan 5 server pada port $($ports -join ', ') ..."
$procs = foreach ($port in $ports) {
    Start-Sleep -Milliseconds 400
    Start-Process -FilePath $php `
        -ArgumentList 'artisan','serve','--host=127.0.0.1',"--port=$port" `
        -WorkingDirectory (Get-Location).Path `
        -PassThru -WindowStyle Hidden
}

try {
    Start-Sleep -Seconds 4
    & $php 'scripts/concurrency-checkout.php'
    $code = $LASTEXITCODE
} finally {
    Write-Host 'Mematikan server uji ...'
    foreach ($proc in $procs) {
        if (-not $proc.HasExited) { Stop-Process -Id $proc.Id -Force }
    }
}

exit $code
