
$ErrorActionPreference = "SilentlyContinue"

# Configurações
$intervalo = 1
$arquivo = "cashfy_monitoramento_$(Get-Date -Format 'yyyyMMdd_HHmmss').csv"
$processadores = (Get-CimInstance Win32_ComputerSystem).NumberOfLogicalProcessors

$anteriores = @{}
$cronometro = [System.Diagnostics.Stopwatch]::StartNew()
$ultimaMedicao = $cronometro.Elapsed.TotalSeconds

function Obter-Processo($nome) {
    $lista = @(Get-Process -Name $nome)

    $cpu = ($lista | Measure-Object -Property CPU -Sum).Sum
    $memoria = ($lista | Measure-Object -Property WorkingSet64 -Sum).Sum

    if ($null -eq $cpu) { $cpu = 0 }
    if ($null -eq $memoria) { $memoria = 0 }

    return @{
        CPU = [double]$cpu
        RAM = [math]::Round($memoria / 1MB, 2)
    }
}

Write-Host "Monitoramento do CashFY iniciado." -ForegroundColor Green
Write-Host "Arquivo CSV: $arquivo"
Write-Host "Pressione Ctrl+C para encerrar."
Write-Host ""

while ($true) {
    $agora = $cronometro.Elapsed.TotalSeconds
    $decorrido = $agora - $ultimaMedicao

    if ($decorrido -le 0) { $decorrido = 1 }

    $ultimaMedicao = $agora

    $sistema = Get-CimInstance Win32_OperatingSystem
    $cpuSistema = Get-CimInstance Win32_Processor |
        Measure-Object -Property LoadPercentage -Average

    $ramTotal = [math]::Round($sistema.TotalVisibleMemorySize / 1MB, 2)
    $ramLivre = [math]::Round($sistema.FreePhysicalMemory / 1MB, 2)
    $ramUsada = [math]::Round($ramTotal - $ramLivre, 2)
    $ramPercentual = [math]::Round(($ramUsada / $ramTotal) * 100, 2)

    $apache = Obter-Processo "httpd"
    $mysql = Obter-Processo "mysqld"

    $cpuApache = 0
    $cpuMysql = 0

    if ($anteriores.ContainsKey("httpd")) {
        $cpuApache = [math]::Round(
            (($apache.CPU - $anteriores["httpd"]) / $decorrido / $processadores) * 100, 2
        )
    }

    if ($anteriores.ContainsKey("mysqld")) {
        $cpuMysql = [math]::Round(
            (($mysql.CPU - $anteriores["mysqld"]) / $decorrido / $processadores) * 100, 2
        )
    }

    $anteriores["httpd"] = $apache.CPU
    $anteriores["mysqld"] = $mysql.CPU

    $registro = [PSCustomObject]@{
        Horario          = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        CPU_Sistema      = [math]::Round($cpuSistema.Average, 2)
        RAM_Usada_GB     = $ramUsada
        RAM_Livre_GB     = $ramLivre
        RAM_Uso_Percent  = $ramPercentual
        Apache_CPU       = $cpuApache
        Apache_RAM_MB    = $apache.RAM
        MySQL_CPU        = $cpuMysql
        MySQL_RAM_MB     = $mysql.RAM
    }

    if (-not (Test-Path $arquivo)) {
        $registro | Export-Csv -Path $arquivo -NoTypeInformation -Encoding UTF8
    } else {
        $registro | Export-Csv -Path $arquivo -NoTypeInformation -Append -Encoding UTF8
    }

    Write-Host (
        "CPU: {0}% | RAM: {1}% ({2}/{3} GB) | Apache: {4}% CPU / {5} MB | MySQL: {6}% CPU / {7} MB" -f
        $registro.CPU_Sistema,
        $registro.RAM_Uso_Percent,
        $registro.RAM_Usada_GB,
        $ramTotal,
        $registro.Apache_CPU,
        $registro.Apache_RAM_MB,
        $registro.MySQL_CPU,
        $registro.MySQL_RAM_MB
    )

    Start-Sleep -Seconds $intervalo
}
