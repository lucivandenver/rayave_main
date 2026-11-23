param(
    [switch]$Build,
    [switch]$Open
)

$ErrorActionPreference = 'Stop'

if ($Build) {
    docker compose up -d --build
} else {
    docker compose up -d
}

if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  Raymundo Avenue Flower Shop is now running!" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  Shop URL:          http://localhost:8080" -ForegroundColor Cyan
Write-Host "  Customize Studio:  http://localhost:8080/products.php?category=17" -ForegroundColor Cyan
Write-Host "  Admin Panel:       http://localhost:8080/admin/" -ForegroundColor Cyan
Write-Host "  phpMyAdmin:        http://localhost:8081" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  Tip: Ctrl+Click any link above to open in your browser." -ForegroundColor DarkGray
Write-Host ""

if ($Open) {
    Start-Process "http://localhost:8080"
}
