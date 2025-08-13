@echo off
setlocal
REM Essaie ddev s'il est présent, sinon bash local (Git Bash)
where ddev >NUL 2>&1
if %ERRORLEVEL%==0 (
  ddev exec -- ./vendor/bin/grumphp run --testsuite pre-commit
) else (
  bash -lc "./vendor/bin/grumphp run --testsuite pre-commit"
)
set EXITCODE=%ERRORLEVEL%
endlocal & exit /b %EXITCODE%
