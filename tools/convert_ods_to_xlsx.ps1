# Konversi file .ods ke .xlsx memakai Excel COM (aman untuk gambar/objek,
# tidak seperti openpyxl). File asli .ods tidak dihapus.
param(
    [Parameter(Mandatory = $true)][string[]]$Files
)

$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

try {
    foreach ($f in $Files) {
        $src = (Resolve-Path $f).Path
        $dst = [System.IO.Path]::ChangeExtension($src, '.xlsx')

        if (Test-Path $dst) {
            Write-Host "SKIP (sudah ada): $dst"
            continue
        }

        Write-Host "Konversi: $src"
        $wb = $excel.Workbooks.Open($src)
        # 51 = xlOpenXMLWorkbook (.xlsx)
        $wb.SaveAs($dst, 51)
        $wb.Close($false)
        Write-Host "  -> OK: $dst"
    }
}
finally {
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
}
