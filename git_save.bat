@echo off
echo Checking git status...
git status
echo.
echo Adding all files...
git add .
echo.
echo Committing changes...
git commit -m "Save all current changes - complete project state"
echo.
echo Checking final status...
git status
echo.
echo Done! All changes saved locally.
