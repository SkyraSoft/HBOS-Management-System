@echo off
setlocal
title SkyraSoft HBOS POS - In-Place Updater & Installer

:: Check common locations for the MSI file
set "MSI_FILE=%USERPROFILE%\Downloads\Skyrasoft\HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi"
if not exist "%MSI_FILE%" (
    set "MSI_FILE=%~dp0..\desktop\target\release\bundle\msi\HBOS POS_1.0.0_x64_en-US.msi"
)

echo ======================================================================
echo          SkyraSoft HBOS POS - Seamless In-Place Updater
echo ======================================================================
echo.

if not exist "%MSI_FILE%" (
    echo [ERROR] Could not find installer at:
    echo "%MSI_FILE%"
    echo.
    echo Please make sure the installer is downloaded to:
    echo "%USERPROFILE%\Downloads\Skyrasoft\HBOS_POS_1.0.0_STAGE3_UAT_x64_en-US.msi"
    echo.
    pause
    exit /b 1
)

echo Target MSI: "%MSI_FILE%"
echo.
echo [1/2] Upgrading / Installing HBOS POS in-place...
echo (Overwrites existing installation without requiring Control Panel uninstall)
echo.

:: Try in-place reinstall / upgrade first
msiexec /i "%MSI_FILE%" REINSTALL=ALL REINSTALLMODE=vomus /qb

:: If error 1605 (product not currently installed), run regular install
if %errorlevel% equ 1605 (
    echo [INFO] First-time install detected. Running clean installation...
    msiexec /i "%MSI_FILE%" /qb
)

if %errorlevel% equ 0 (
    echo.
    echo ======================================================================
    echo [SUCCESS] HBOS POS has been installed / updated successfully!
    echo ======================================================================
    echo.
    echo [2/2] Launching HBOS POS...
    if exist "C:\Program Files\HBOS POS\HBOS POS.exe" (
        start "" "C:\Program Files\HBOS POS\HBOS POS.exe"
    )
) else (
    echo.
    echo [NOTICE] Installer exited with status code: %errorlevel%
)

timeout /t 3 >nul
exit /b 0
