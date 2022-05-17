while ($true) {
    C:\php\php.exe "D:\UVB Server\UniversalVkBot.phar"
    if ($LASTEXITCODE -eq 2) {
        continue
    }
    if ($LASTEXITCODE -eq 255) {
        $confirmation = Read-Host "UniversalVkBot was crashed. Check crash log above. Do you want to restart UniversalVkBot [y/n]?: "
        if ($confirmation -eq "y") {
            continue
        } else {
            break
        }
    }
    break
}
pause