@echo off
REM Wrapper Windows: exécute le hook bash via Git Bash
where bash >NUL 2>&1
if errorlevel 1 (
  echo [pre-push.cmd] Git Bash introuvable. Installez Git for Windows.
  exit /b 1
)
bash -lc ".githooks/pre-push"
exit /b %ERRORLEVEL%
