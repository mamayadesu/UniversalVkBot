echo off
set mydir=%CD%
for %%a in (".") do set CURRENT_DIR_NAME=%%~na
cd C:\php

php C:\phpdocumentor.phar -d C:\Users\Semyon\Documents\PHPStorm\%CURRENT_DIR_NAME%\%CURRENT_DIR_NAME% -t C:\Users\Semyon\Documents\PHPStorm\%CURRENT_DIR_NAME%\phpdoc
pause