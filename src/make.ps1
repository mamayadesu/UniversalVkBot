xrefcore-compiler -b
if ((Test-Path -Path "D:\UVB Server\UniversalVkBot.phar" -PathType Leaf) -eq $true) {
    Remove-Item "D:\UVB Server\UniversalVkBot.phar"
}
copy UniversalVkBot.phar "D:\UVB Server\"