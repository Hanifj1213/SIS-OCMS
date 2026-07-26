# Inventarisasi checksheet POWERTRAIN: daftar sheet per file Excel.
# .xlsx dibaca dari zip XML (cepat), .xls/.ods via Excel COM.
$ErrorActionPreference = 'Continue'

$base = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\POWERTRAIN"
$outFile = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\tools\powertrain_inventory.txt"

Add-Type -AssemblyName System.IO.Compression.FileSystem

function Get-XlsxSheets([string]$path) {
    $zip = [System.IO.Compression.ZipFile]::OpenRead($path)
    try {
        $entry = $zip.GetEntry('xl/workbook.xml')
        if (-not $entry) { return @() }
        $reader = New-Object System.IO.StreamReader($entry.Open())
        $xmlText = $reader.ReadToEnd()
        $reader.Close()
        $xml = [xml]$xmlText
        $ns = New-Object System.Xml.XmlNamespaceManager($xml.NameTable)
        $ns.AddNamespace('m', $xml.DocumentElement.NamespaceURI)
        return @($xml.SelectNodes('//m:sheets/m:sheet', $ns) | ForEach-Object { $_.name })
    } finally {
        $zip.Dispose()
    }
}

$xl = $null
function Get-LegacySheets([string]$path) {
    if (-not $script:xl) {
        $script:xl = New-Object -ComObject Excel.Application
        $script:xl.Visible = $false
        $script:xl.DisplayAlerts = $false
    }
    $wb = $script:xl.Workbooks.Open($path, 0, $true)
    try {
        return @($wb.Worksheets | ForEach-Object { $_.Name })
    } finally {
        $wb.Close($false)
    }
}

$lines = @("POWERTRAIN CHECKSHEET INVENTORY - generated " + (Get-Date -Format 'yyyy-MM-dd HH:mm'))
$ok = 0; $fail = 0

foreach ($unit in (Get-ChildItem $base -Directory | Sort-Object Name)) {
    $lines += ""
    $lines += "========== $($unit.Name) =========="
    $files = Get-ChildItem $unit.FullName -Recurse -File -Include *.xls,*.xlsx,*.xlsm,*.ods |
        Where-Object { $_.Name -notlike '~$*' } | Sort-Object FullName

    $lastSub = $null
    foreach ($f in $files) {
        $sub = Split-Path $f.FullName -Parent
        $rel = $sub.Substring($unit.FullName.Length).TrimStart('\')
        if ($rel -eq '') { $rel = '.' }
        if ($rel -ne $lastSub) {
            $lines += "  [$rel]"
            $lastSub = $rel
        }
        $lines += "     FILE: $($f.Name)"
        try {
            if ($f.Extension -ieq '.xlsx' -or $f.Extension -ieq '.xlsm') {
                $sheets = Get-XlsxSheets $f.FullName
            } else {
                $sheets = Get-LegacySheets $f.FullName
            }
            $lines += "     SHEETS: " + ($sheets -join ' | ')
            $ok++
        } catch {
            $lines += "     ERROR: $($_.Exception.Message)"
            $fail++
        }
    }
    Write-Output "done: $($unit.Name)"
}

if ($script:xl) {
    $script:xl.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($script:xl) | Out-Null
}

Set-Content -Path $outFile -Value ($lines -join "`r`n")
Write-Output "SELESAI ok=$ok fail=$fail -> $outFile"
