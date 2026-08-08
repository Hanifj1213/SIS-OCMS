# Simpan ulang .doc (Word 97-2003) sebagai .docx via Word COM (native save,
# cepat — berbeda dengan ekspor HTML yang macet untuk dokumen penuh drawing).
param(
    [Parameter(Mandatory = $true)][string[]]$Files
)

$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
$word.AutomationSecurity = 3  # jangan jalankan macro dokumen
$word.Options.UpdateLinksAtOpen = $false

try {
    foreach ($f in $Files) {
        $src = (Resolve-Path $f).Path
        $dst = [System.IO.Path]::ChangeExtension($src, '.docx')

        if (Test-Path $dst) {
            Write-Host "SKIP (sudah ada): $dst"
            continue
        }

        Write-Host ("[{0}] Open : {1}" -f (Get-Date -Format 'HH:mm:ss'), $src)
        $doc = $word.Documents.Open($src, $false, $true, $false)

        Write-Host ("[{0}] Save : {1}" -f (Get-Date -Format 'HH:mm:ss'), $dst)
        # 16 = wdFormatDocumentDefault (.docx)
        $doc.SaveAs2($dst, 16)
        $doc.Close($false)
        Write-Host ("[{0}] -> OK" -f (Get-Date -Format 'HH:mm:ss'))
    }
}
finally {
    $word.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
}
