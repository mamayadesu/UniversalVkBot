xrefcore-compiler -b
if ((Test-Path -Path "D:\UVB Server\UniversalVkBot.phar" -PathType Leaf) -eq $true) {
    Remove-Item "D:\UVB Server\UniversalVkBot.phar"
}
copy UniversalVkBot.phar "D:\UVB Server\"
while ($true) {
    C:\php\php.exe "D:\UVB Server\UniversalVkBot.phar"
    if ($LASTEXITCODE -eq 2) {
        continue
    }
    if ($LASTEXITCODE -eq 255) {
        $confirmation = Read-Host "UniversalVkBot was crashed. Check crash log above. Do you want to compile and restart UniversalVkBot [y/n]?: "
        if ($confirmation -eq "y") {
            xrefcore-compiler -b
            continue
        } else {
            break
        }
    }
    break
}
pause