# Inspeksi sheet pada semua file measurement mainline
$ErrorActionPreference = 'Stop'
$base = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE"

$files = Get-ChildItem -Path $base -Recurse -File -Include *.xls,*.xlsx |
    Where-Object { $_.DirectoryName -match 'MAIN ?LINE\\MEASUR' } | Sort-Object FullName

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

foreach ($f in $files) {
    Write-Output ("FILE: " + $f.FullName.Replace($base + '\', '') + "  (modified " + $f.LastWriteTime.ToString('yyyy-MM-dd') + ")")
    try {
        $wb = $xl.Workbooks.Open($f.FullName, 0, $true)
        foreach ($ws in $wb.Worksheets) {
            $ur = $ws.UsedRange
            Write-Output ("    '" + $ws.Name + "' visible=" + $ws.Visible + " used=" + $ur.Rows.Count + "x" + $ur.Columns.Count)
        }
        $wb.Close($false)
    } catch {
        Write-Output ("    ERROR: " + $_.Exception.Message)
    }
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "SELESAI"
