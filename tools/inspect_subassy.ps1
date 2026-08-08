# Inventarisasi file DISASSY + MEASURE sub-assy per EGI,
# termasuk daftar sheet di dalam tiap file (untuk pilih revisi terakhir).
$ErrorActionPreference = 'Stop'
$base = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE"
$out  = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\tools\subassy_inventory.txt"

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

$lines = New-Object System.Collections.Generic.List[string]
$egis = @('SA12V140E-1','SA6D140E-2','SAA12V140E-3','SAA6D140E-5','SAA6D170E-5')

foreach ($egi in $egis) {
    $sub = Get-ChildItem "$base\$egi" -Directory | Where-Object { $_.Name -match 'SUB' } | Select-Object -First 1
    if (-not $sub) { continue }
    $lines.Add("========== $egi ==========")

    Get-ChildItem $sub.FullName -Directory | Sort-Object Name | ForEach-Object {
        $part = $_.Name
        $files = Get-ChildItem $_.FullName -Recurse -File -Include *.xls,*.xlsx -ErrorAction SilentlyContinue |
            Where-Object { $_.Name -notmatch '^~\$' }

        foreach ($f in $files) {
            $p = $f.DirectoryName.ToUpper()
            $kind = if ($p -match 'DISS?AS+EM') {'DISASSY'}
                    elseif ($p -match 'MEASUR|SALVAG') {'MEASURE'}
                    else { $null }
            if (-not $kind) { continue }

            try {
                $wb = $xl.Workbooks.Open($f.FullName, 0, $true)
                $sheetNames = @()
                foreach ($ws in $wb.Worksheets) {
                    if ($ws.Visible -eq -1) { $sheetNames += $ws.Name }
                }
                $wb.Close($false)
                $rel = $f.FullName.Replace("$base\$egi\", '')
                $lines.Add("  [$kind] $part")
                $lines.Add("       FILE: $rel")
                $lines.Add("       SHEETS: $($sheetNames -join ' | ')")
            } catch {
                $lines.Add("  [$kind] $part  ERROR: $($_.Exception.Message)  FILE=$($f.Name)")
            }
        }
    }
    $lines.Add('')
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
$lines | Set-Content -Path $out -Encoding UTF8
Write-Output "tersimpan: $out ($($lines.Count) baris)"
