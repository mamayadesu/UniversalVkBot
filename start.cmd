@echo off
:: Path to "php.exe"
set PHP=C:\php\php.exe

setlocal EnableDelayedExpansion

:loop
%PHP% UniversalVkBot.phar
if !ErrorLevel! equ 2 (
    goto :loop
)
if !ErrorLevel! equ 255 (
    set /p restart="UniversalVkBot was crashed. Check crash log above. Do you want to restart UniversalVkBot [y/n]?: "
    if "!restart!" == "y" (
        goto :loop
    )
)
pause