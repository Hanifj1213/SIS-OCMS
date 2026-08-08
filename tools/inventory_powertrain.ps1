param([string[]]$Units)

$base = "CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\POWERTRAIN"
$outFile = "tools\powertrain_inventory.txt"

$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false
$excel.AskToUpdateLinks = $false
$excel.EnableEvents = $false

$ok = 0; $fail = 0
try {
    foreach ($unit in $Units) {
        $unitPath = Join-Path $base $unit
        $unitFull = (Resolve-Path -LiteralPath $unitPath).Path
        Add-Content -LiteralPath $outFile -Value "========== $unit =========="

        $files = Get-ChildItem -LiteralPath $unitPath -Recurse -File |
            Where-Object { $_.Extension -match '^\.(xls|xlsx|ods)$' -and $_.Name -notlike '~$*' } |
            Sort-Object FullName

        $groups = $files | Group-Object DirectoryName | Sort-Object Name
        foreach ($g in $groups) {
            $rel = $g.Name.Substring($unitFull.Length).TrimStart('\')
            if ($rel -eq '') { $rel = '.' }
            Add-Content -LiteralPath $outFile -Value "  [$rel]"
            foreach ($f in $g.Group) {
                try {
                    # dummy password arg -> throws instead of prompting on protected files
                    $wb = $excel.Workbooks.Open($f.FullName, 0, $true, [Type]::Missing, "dummy_pw_x")
                    $sheets = @()
                    foreach ($ws in $wb.Worksheets) { $sheets += $ws.Name }
                    $wb.Close($false)
                    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($wb) | Out-Null
                    Add-Content -LiteralPath $outFile -Value "     FILE: $($f.Name)"
                    Add-Content -LiteralPath $outFile -Value "     SHEETS: $($sheets -join ' | ')"
                    $ok++
                    Write-Host "OK   [$unit] $($f.Name)"
                } catch {
                    Add-Content -LiteralPath $outFile -Value "     FILE: $($f.Name)"
                    Add-Content -LiteralPath $outFile -Value "     SHEETS: ERROR - $($_.Exception.Message)"
                    $fail++
                    Write-Host "FAIL [$unit] $($f.Name) :: $($_.Exception.Message)"
                }
            }
        }
        Add-Content -LiteralPath $outFile -Value ""
        Write-Host "--- unit done: $unit ---"
    }
} finally {
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
    [GC]::Collect(); [GC]::WaitForPendingFinalizers()
}
Write-Host "CHUNK DONE ok=$ok fail=$fail"
