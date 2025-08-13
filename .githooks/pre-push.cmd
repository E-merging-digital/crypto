@echo off
setlocal
where bash >nul 2>nul
if errorlevel 1 (
  echo [pre-push] Bash introuvable. Lancez le push depuis "Git Bash".
  exit /b 1
)
bash -lc "exec ./.githooks/pre-push"
endlocal
