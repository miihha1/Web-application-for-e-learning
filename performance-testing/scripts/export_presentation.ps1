$ErrorActionPreference = 'Stop'
$performanceRoot = Split-Path $PSScriptRoot -Parent
$presentationPath = Join-Path $performanceRoot 'documentation/KS_Tema14_Prezentacia_Mykhailo_Adamenko.pptx'
$previewPath = Join-Path $performanceRoot 'screenshots/presentation'
$presentationPdf = Join-Path $performanceRoot 'documentation/KS_Tema14_Prezentacia_Mykhailo_Adamenko.pdf'
New-Item -ItemType Directory -Force $previewPath | Out-Null
$hadPowerPoint = [bool](Get-Process POWERPNT -ErrorAction SilentlyContinue)
$powerPoint = New-Object -ComObject PowerPoint.Application
try {
    $presentation = $powerPoint.Presentations.Open($presentationPath, $true, $false, $false)
    try {
        $presentation.Export($previewPath, 'PNG', 1600, 900)
        $presentation.SaveAs($presentationPdf, 32)
    } finally { $presentation.Close() }
} finally { if (-not $hadPowerPoint) { $powerPoint.Quit() } }
$deliveryRoot = Split-Path (Split-Path $performanceRoot -Parent) -Parent
Copy-Item -LiteralPath $presentationPdf -Destination (Join-Path $deliveryRoot 'KS_Tema14_Prezentacia_Mykhailo_Adamenko_v3.pdf')
