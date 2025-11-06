# 
#  MailJZTech - Docker Helper
# 

param(
    [string]$Command = "help"
)

$InfraPath = "C:\laragon\www\docker-infra"
$ProjectPath = $PSScriptRoot

function Show-Header {
    Write-Host "`n════════════════════════════════════════════════" -ForegroundColor Cyan
    Write-Host "        MailJZTech Docker Manager            " -ForegroundColor Cyan
    Write-Host "════════════════════════════════════════════════`n" -ForegroundColor Cyan
}

function Start-Infrastructure {
    Write-Host "📦 Iniciando infraestrutura (MySQL + phpMyAdmin)..." -ForegroundColor Yellow
    Set-Location $InfraPath
    docker-compose up -d
    Set-Location $ProjectPath
    Write-Host "✅ Infraestrutura iniciada!" -ForegroundColor Green
}

function Start-API {
    Write-Host "🚀 Iniciando API MailJZTech..." -ForegroundColor Yellow
    docker-compose up -d
    Write-Host "✅ API rodando em http://localhost:8050" -ForegroundColor Green
}

function Start-All {
    Start-Infrastructure
    Start-Sleep -Seconds 5
    Start-API
    Show-Status
}

function Stop-All {
    Write-Host " Parando containers..." -ForegroundColor Yellow
    docker-compose down
    Set-Location $InfraPath
    docker-compose down
    Set-Location $ProjectPath
    Write-Host " Containers parados!" -ForegroundColor Green
}

function Show-Status {
    Write-Host "`n📊 Status dos containers:" -ForegroundColor Cyan
    docker ps --filter "name=mysql_shared" --filter "name=phpmyadmin_shared" --filter "name=mailjztech_api" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
}

function Show-Logs {
    Write-Host "📋 Logs da API:" -ForegroundColor Cyan
    docker-compose logs -f api
}

function Enter-Container {
    Write-Host "🐚 Entrando no container da API..." -ForegroundColor Yellow
    docker exec -it mailjztech_api bash
}

function Run-Composer {
    Write-Host "📦 Instalando dependências Composer..." -ForegroundColor Yellow
    docker exec -it mailjztech_api composer install
}

function Show-Help {
    Show-Header
    Write-Host "📚 Comandos disponíveis:" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  .\docker.ps1 start-infra" -ForegroundColor Green
    Write-Host "     Inicia apenas MySQL + phpMyAdmin" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  .\docker.ps1 start-api" -ForegroundColor Green
    Write-Host "     Inicia apenas a API MailJZTech" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  .\docker.ps1 start" -ForegroundColor Green
    Write-Host "     Inicia tudo (infra + API)" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  .\docker.ps1 stop" -ForegroundColor Green
    Write-Host "     Para todos os containers" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  .\docker.ps1 status" -ForegroundColor Green
    Write-Host "     Mostra status dos containers" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  .\docker.ps1 logs" -ForegroundColor Green
    Write-Host "     Mostra logs da API" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  .\docker.ps1 shell" -ForegroundColor Green
    Write-Host "     Entra no container da API" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  .\docker.ps1 composer" -ForegroundColor Green
    Write-Host "     Instala dependências Composer" -ForegroundColor Gray
    Write-Host ""
    Write-Host "🌐 URLs:" -ForegroundColor Cyan
    Write-Host "   API:        http://localhost:8050" -ForegroundColor White
    Write-Host "   phpMyAdmin: http://localhost:8090" -ForegroundColor White
    Write-Host ""
}

# 
# Execução de comandos
# 

switch ($Command.ToLower()) {
    "start-infra" { Start-Infrastructure }
    "start-api" { Start-API }
    "start" { Start-All }
    "stop" { Stop-All }
    "status" { Show-Status }
    "logs" { Show-Logs }
    "shell" { Enter-Container }
    "composer" { Run-Composer }
    default { Show-Help }
}
