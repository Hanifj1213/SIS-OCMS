$ErrorActionPreference = 'Stop'
$file = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE\SAA12V140E-3\MAIN LINE\DISASSEMBLY\DISASSEMBLY ENGINE12V140-3.xls"

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false
$wb = $xl.Workbooks.Open($file, 0, $true)

foreach ($ws in $wb.Worksheets) {
    $found = '(REVISION NO. tidak ketemu)'
    $ur = $ws.UsedRange
    $maxR = [Math]::Min(15, $ur.Rows.Count)
    $maxC = [Math]::Min(70, $ur.Columns.Count)
    for ($r = 1; $r -le $maxR; $r++) {
        for ($c = 1; $c -le $maxC; $c++) {
            if ($ws.Cells.Item($r, $c).Text -match 'REVISION') {
                $rev = ''
                for ($cc = $c + 1; $cc -le $maxC; $cc++) {
                    $t = $ws.Cells.Item($r, $cc).Text
                    if ($t) { $rev = $t; break }
                }
                $found = "REVISION di R${r}C${c}, nilai=[$rev]"
            }
        }
    }
    Write-Output ($ws.Name + '  ->  ' + $found)
}

$wb.Close($false)
$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
