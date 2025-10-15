# PowerShell script to save all changes to git
Write-Host "Checking git status..." -ForegroundColor Green
git status

Write-Host "`nAdding all files..." -ForegroundColor Green
git add .

Write-Host "`nCommitting changes..." -ForegroundColor Green
git commit -m "Save all current changes - complete project state"

Write-Host "`nChecking final status..." -ForegroundColor Green
git status

Write-Host "`nDone! All changes saved locally." -ForegroundColor Green
