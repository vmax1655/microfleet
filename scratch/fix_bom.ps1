# Fix BOM in all generated fleet/logistics/intelligence PHP and Blade files
$base = "c:\xampp1\htdocs\microfleet"
$utf8NoBom = [System.Text.UTF8Encoding]::new($false)

$dirs = @(
    "$base\app\Livewire\Fleet",
    "$base\app\Livewire\Logistics",
    "$base\app\Livewire\Intelligence",
    "$base\resources\views\livewire\fleet",
    "$base\resources\views\livewire\logistics",
    "$base\resources\views\livewire\intelligence"
)

foreach ($dir in $dirs) {
    if (!(Test-Path $dir)) { continue }
    foreach ($file in Get-ChildItem $dir -File) {
        $bytes = [System.IO.File]::ReadAllBytes($file.FullName)
        # Detect UTF-8 BOM: EF BB BF
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            $stripped = $bytes[3..($bytes.Length - 1)]
            [System.IO.File]::WriteAllBytes($file.FullName, $stripped)
            Write-Host "Fixed BOM: $($file.FullName)" -ForegroundColor Green
        } else {
            Write-Host "Clean (no BOM): $($file.FullName)" -ForegroundColor Gray
        }
    }
}

Write-Host "`nDone." -ForegroundColor Cyan
