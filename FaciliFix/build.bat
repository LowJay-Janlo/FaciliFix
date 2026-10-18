@echo off
rem Rebuilds the minified css/ and js/ files from the readable ones in src/
rem Works from the FaciliFix folder or from inside the src folder.

cd /d "%~dp0"
if not exist "src\css\base.css" cd ..
if not exist "src\css\base.css" (
    echo Could not find the src folder.
    echo Put build.bat inside your FaciliFix folder, next to the src folder.
    pause
    exit /b 1
)

echo Working in: %cd%
set FAILED=0

echo Rebuilding CSS...
for %%f in (base app public) do (
    call npx --yes esbuild src/css/%%f.css --minify --outfile=css/%%f.css --log-level=warning
    if errorlevel 1 set FAILED=1
)

echo Rebuilding JS...
for %%f in (add_room equipment_form facilities layout manage_staff room staff_form) do (
    call npx --yes esbuild src/js/%%f.js --minify --format=iife --target=es2017 --outfile=js/%%f.js --log-level=warning
    if errorlevel 1 set FAILED=1
)

echo.
if "%FAILED%"=="1" (
    echo SOMETHING FAILED. Read the messages above.
) else (
    echo Done. Refresh the browser with Ctrl+F5.
)
pause