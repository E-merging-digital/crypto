@echo off
REM Lance le hook bash depuis Git Bash si présent (Windows)
where bash >NUL 2>&1
if errorlevel 1 (
  echo [pre-push.cmd] "bash" introuvable. Installez Git for Windows (avec Git Bash).
  exit /b 1
)
bash -lc ".githooks/pre-push"
exit /b %ERRORLEVEL%
