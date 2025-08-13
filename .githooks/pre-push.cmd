@echo off
setlocal
where ddev >NUL 2>&1
if %ERRORLEVEL%==0 (
  ddev exec -- ./vendor/bin/grumphp run --testsuite pre-push
  set EXITCODE=%ERRORLEVEL%
  if not %EXITCODE%==0 ( endlocal & exit /b %EXITCODE% )
  if exist behat.yml (
    ddev exec -- ./vendor/bin/behat -c behat.yml -p local --colors --strict
    set EXITCODE=%ERRORLEVEL%
    endlocal & exit /b %EXITCODE%
  )
) else (
  bash -lc "./vendor/bin/grumphp run --testsuite pre-push"
  set EXITCODE=%ERRORLEVEL%
  if not %EXITCODE%==0 ( endlocal & exit /b %EXITCODE% )
  if exist behat.yml (
    bash -lc "./vendor/bin/behat -c behat.yml -p local --colors --strict"
    set EXITCODE=%ERRORLEVEL%
    endlocal & exit /b %EXITCODE%
  )
)
endlocal & exit /b 0
