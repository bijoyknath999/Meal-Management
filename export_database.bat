@echo off
REM Database Export Script for Meal Management System
REM This script exports the current database to a SQL file

echo ========================================
echo Meal Management System - DB Export
echo ========================================
echo.

REM Configuration
set DB_NAME=meal_management
set DB_USER=root
set DB_PASS=
set TIMESTAMP=%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set OUTPUT_FILE=database_export_%TIMESTAMP%.sql
set MYSQL_PATH=C:\xampp\mysql\bin

echo Database: %DB_NAME%
echo Output: %OUTPUT_FILE%
echo.

REM Check if MySQL bin directory exists
if not exist "%MYSQL_PATH%\mysqldump.exe" (
    echo ERROR: MySQL not found at %MYSQL_PATH%
    echo Please update MYSQL_PATH in this script
    pause
    exit /b 1
)

echo Exporting database...
echo.

REM Export database
"%MYSQL_PATH%\mysqldump.exe" -u %DB_USER% %DB_NAME% > %OUTPUT_FILE%

if %ERRORLEVEL% EQU 0 (
    echo ========================================
    echo SUCCESS!
    echo ========================================
    echo.
    echo Database exported successfully!
    echo File: %OUTPUT_FILE%
    echo Size: 
    dir /-C %OUTPUT_FILE% | find "%OUTPUT_FILE%"
    echo.
    echo You can now:
    echo 1. Commit this file to Git (if needed)
    echo 2. Use it for backup
    echo 3. Import on production server
    echo.
) else (
    echo ========================================
    echo ERROR!
    echo ========================================
    echo.
    echo Failed to export database
    echo Please check:
    echo - MySQL is running
    echo - Database name is correct
    echo - User has permissions
    echo.
)

pause

