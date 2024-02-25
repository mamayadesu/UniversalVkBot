echo off
set mydir=%CD%
for %%a in (".") do set CURRENT_DIR_NAME=%%~na
cd C:\php

php C:\phpdocumentor.phar -d C:\Users\Semyon\Documents\PHPStorm\UniversalVkBot\UniversalVkBot\uvb -t C:\Users\Semyon\Documents\PHPStorm\UniversalVkBotDocumentation\
pause