@echo off
echo Starting Laravel Tests with PostgreSQL...
echo ========================================

cd /d "%~dp0"

echo Current directory: %CD%
echo.

echo Testing basic functionality first...
php test_basic_functionality.php
if %errorlevel% neq 0 (
    echo Basic functionality test failed!
    pause
    exit /b 1
)

echo.
echo Running PHPUnit tests...
vendor\bin\phpunit --configuration phpunit.xml --verbose
if %errorlevel% neq 0 (
    echo Some tests failed!
    pause
    exit /b 1
)

echo.
echo All tests completed successfully!
pause

