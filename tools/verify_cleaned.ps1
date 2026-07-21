$ErrorActionPreference = 'Stop'
$outDir = "C:\Users\Administrator\.gemini\antigravity\scratch\SISI-OCMS\CHECKSHEET FOR PROCESS DEVELOPMEN ROBBY\ENGINE\_SIAP_UPLOAD_GSHEET"

$xl = New-Object -ComObject Excel.Application
$xl.Visible = $false
$xl.DisplayAlerts = $false

foreach ($f in Get-ChildItem $outDir -Filter *.xlsx) {
    $wb = $xl.Workbooks.Open($f.FullName, 0, $true)
    Write-Output ("FILE: " + $f.Name + " | jumlah sheet: " + $wb.Worksheets.Count)
    foreach ($ws in $wb.Worksheets) {
        $ur = $ws.UsedRange
        # cari header pertama utk sampling
        $sample = ''
        for ($r = 1; $r -le [Math]::Min(30, $ur.Rows.Count); $r++) {
            for ($c = 1; $c -le [Math]::Min(40, $ur.Columns.Count); $c++) {
                if ($ws.Cells.Item($r, $c).Text -match '^REUSE$') {
                    $hOri = $ws.Cells.Item($r, $c).Orientation
                    $dOri = $ws.Cells.Item($r + 5, $c).Orientation
                    $sample = "header orient=$hOri (harus -4170) | sel isian orient=$dOri (harus -4128)"
                    break
                }
            }
            if ($sample) { break }
        }
        Write-Output ("  '" + $ws.Name + "' " + $ur.Rows.Count + "x" + $ur.Columns.Count + " merge-sample=" + $ws.Cells.Item(1,1).MergeCells + " | " + $sample)
    }
    $wb.Close($false)
}

$xl.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($xl) | Out-Null
Write-Output "VERIFIKASI SELESAI"
