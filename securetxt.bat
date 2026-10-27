@echo off
rem ==== Securetxt ====
rem Mets ici le chemin complet de ton php.exe 8.5 (celui de PhpStorm) :
set "PHP85=C:\chemin\vers\php-8.5\php.exe"

if not exist "%PHP85%" (
    echo PHP 8.5 introuvable : %PHP85%
    echo Ouvre securetxt.bat avec un editeur et corrige la ligne PHP85.
    pause
    exit /b 1
)
chcp 65001 >nul
title Securetxt
"%PHP85%" "%~dp0securetxt.php" %*
if errorlevel 1 pause
