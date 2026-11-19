# ============================================================
# Akses publik SEMENTARA untuk Tabita Modiste dari komputer lokal
# - Memakai ulang URL tunnel yang masih hidup
# - Kalau mati, membuat tunnel baru dengan log unik
# ============================================================

$ErrorActionPreference = 'SilentlyContinue'

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$state = Join-Path $env:TEMP 'tabita-tunnel-url.txt'

# Token webhook dibaca dari .env. Jangan pernah ditulis literal di script/docs.
$envFile = Join-Path $root '.env'
$webhookToken = (Get-Content $envFile -ErrorAction SilentlyContinue |
    Where-Object { $_ -match '^BITESHIP_WEBHOOK_TOKEN=(.*)$' } |
    Select-Object -Last 1) -replace '^BITESHIP_WEBHOOK_TOKEN=', ''
if (-not $webhookToken) { $webhookToken = $env:BITESHIP_WEBHOOK_TOKEN }
if (-not $webhookToken) {
    Write-Host "FATAL: BITESHIP_WEBHOOK_TOKEN kosong di .env" -ForegroundColor Red
    exit 1
}

function Test-TunnelUrl {
    param([string]$Url)
    if (-not $Url) { return $false }
    try {
        $r = Invoke-WebRequest -Uri "$Url/shipping/biteship/webhook" `
            -Method POST `
            -Headers @{ 'Tabita-Modiste-Signature' = $webhookToken } `
            -ContentType 'application/json' `
            -Body '{"event":"ping"}' `
            -UseBasicParsing -TimeoutSec 12
        return ($r.StatusCode -eq 200)
    } catch {
        return $false
    }
}

function Show-Info {
    param([string]$Url)
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host "WEBSITE : $Url" -ForegroundColor Green
    Write-Host "WEBHOOK : $Url/shipping/biteship/webhook" -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Di Biteship: Integrasi -> Webhook -> Add Webhook"
    Write-Host "Events : order.status, order.waybill_id, order.price"
    Write-Host "Header : Tabita-Modiste-Signature"
    Write-Host "Secret : dari .env (BITESHIP_WEBHOOK_TOKEN) — jangan dibagikan"
    Write-Host ""
    Write-Host "CATATAN: URL berubah kalau tunnel dibuat ulang."
    Write-Host "Jangan tutup jendela proses sampai selesai uji coba."
}

Write-Host "Folder project: $root"

# 1. Cek URL lama yang masih aktif
if (Test-Path $state) {
    $existing = (Get-Content $state -Raw).Trim()
    if (Test-TunnelUrl $existing) {
        Write-Host "Tunnel sudah aktif, memakai ulang URL yang ada." -ForegroundColor Cyan
        Show-Info $existing
        exit 0
    }
    Write-Host "Tunnel lama tidak merespons. Membuat tunnel baru..." -ForegroundColor Yellow
}

# 2. Pastikan Laravel server jalan
$listening = Get-NetTCPConnection -LocalPort 8000 -State Listen
if (-not $listening) {
    Write-Host "Menjalankan php artisan serve..."
    Start-Process -FilePath 'php' -ArgumentList 'artisan serve --host=127.0.0.1 --port=8000' -WindowStyle Hidden
} else {
    Write-Host "Laravel sudah berjalan di port 8000."
}

# 3. Hentikan cloudflared lama supaya tidak berebut log
Get-Process cloudflared -ErrorAction SilentlyContinue | Stop-Process -Force

# 4. Log unik untuk proses baru
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$log = Join-Path $env:TEMP "tabita-tunnel-$stamp.log"
$err = Join-Path $env:TEMP "tabita-tunnel-$stamp.err"

Write-Host "Menjalankan Cloudflare Tunnel (first run bisa download cloudflared)..."
Start-Process -FilePath 'npx.cmd' `
    -ArgumentList 'cloudflared tunnel --url http://127.0.0.1:8000' `
    -RedirectStandardOutput $log `
    -RedirectStandardError $err `
    -WindowStyle Hidden

# 5. Tunggu URL publik muncul dari log unik
$url = $null
for ($i = 0; $i -lt 60; $i++) {
    Start-Sleep -Seconds 2

    $match = $null
    foreach ($file in @($err, $log)) {
        if (Test-Path $file) {
            $match = Select-String -Path $file -Pattern 'https://[a-z0-9-]+\.trycloudflare\.com' | Select-Object -Last 1
            if ($match) { break }
        }
    }

    if ($match) {
        $url = $match.Matches[0].Value
        break
    }
}

if (-not $url) {
    Write-Host "Gagal mendapat URL. Cek log: $err" -ForegroundColor Red
    exit 1
}

Set-Content -Path $state -Value $url -NoNewline
Write-Host "Tunnel baru siap." -ForegroundColor Cyan
Show-Info $url
