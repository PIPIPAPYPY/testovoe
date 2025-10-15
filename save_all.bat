@echo off
echo Saving all changes to Git...
echo.

REM Change to project directory
cd /d "D:\проекты\untitled\untitled1\ЛЬ"

REM Check git status
echo Checking git status...
git status
echo.

REM Add all files
echo Adding all files to staging area...
git add .
echo.

REM Commit changes
echo Creating commit...
git commit -m "Save all current changes - complete project state"
echo.

REM Check final status
echo Checking final status...
git status
echo.

echo Done! All changes saved locally to Git.
echo Nothing was pushed to GitHub - everything is local only.
echo.
pause
