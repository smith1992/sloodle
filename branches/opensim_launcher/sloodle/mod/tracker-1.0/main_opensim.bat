@echo off
Setlocal EnableDelayedExpansion

rem get the parameters (firstname and lastname of the user in the Moodle site)
set firstname=%1
set lastname=%2

rem ***** Port section *****
rem Get the next avaliable port (8999 < port < 9030)
set aux=9030
set port=8999
for /F "usebackq tokens=2-7 delims=: " %%g in (`netstat -nao ^| find "LISTENING"`) do @if %%h GTR %port% (@if %%h LSS %aux% @set port=%%h)
set /a port=%port%+1
set http_listener_port=%port%
rem echo %puerto%

rem ***** Directory section *****
rem Get date and time to create the directory (copy of opensim) in opensimInstances
rem for /f  "usebackq tokens=1,2 delims=:" %%i in (`time /t`) do set time=%%i%%j
mysql -s -s -u root --password=root -e "select date_format(now(),'%%h%%i%%s')" > time.txt
for /f "eol=; tokens=* delims=" %%i in (time.txt) do set time=%%i
del time.txt > nul
rem echo %time%
rem echo %time%
rem pause > nul
for /f  "usebackq tokens=1,2,3 delims=/" %%i in (`date /t`) do set tmpdate=%%i%%j%%k
rem echo %tmpdate%
rem Limpiamos los espacios en blanco
for /f  "usebackq tokens=1,2,3 delims= " %%i in (`echo %tmpdate%`) do set date=%%i%%j%%k
rem pause > nul
set date_time=%date%_%time%
rem echo %date_time%
rem pause > nul
xcopy c:\opensimTest c:\opensimInstances\opensim%date_time% /e /i > nul
rem pause > nul

rem ***** Database section *****
rem export the original database
mysqldump -u root --password=root opensimdb07 > c:\opensim%date_time%.sql
rem echo 'opensimdb07' database exported in file c:\opensim%date_time%.sql
rem pause > nul
rem create the new database 'opensimddmmyyy_hhmm'
mysqladmin -u root --password=root create opensim%date_time%
rem echo opensim%date_time% database created
rem pause > nul
rem import the original database into the new's one
mysql -u root --password=root opensim%date_time% < c:\opensim%date_time%.sql
rem echo c:\opensim%date_time%.sql imported in opensim%date_time% database 
del c:\opensim%date_time%.sql > nul
rem get a new UUID for the avatar
mysql -s -s -u root --password=root -e "select uuid()" > uuid.txt
for /f "eol=; tokens=* delims=" %%i in (uuid.txt) do (@set uuid=%%i)
del uuid.txt > nul
rem update Sloodle mdl_sloodle_users table with the new avatar UUID for the moodle user
mysql -u root --password=root -e "use moodle;update mdl_sloodle_users set uuid='%uuid%' where avname='%firstname% %lastname%'"
rem update OpenSim useraccounts table with the new UUID, and the first and last moodle name
mysql -u root --password=root -e "use opensim%date_time%;update useraccounts set PrincipalID='%uuid%',FirstName='%firstname%',LastName='%lastname%'"
rem update OpenSim auth table with the new UUID
mysql -u root --password=root -e "use opensim%date_time%;update auth set UUID='%uuid%'"
rem update OpenSim inventoryfolders table with the new UUID
mysql -u root --password=root -e "use opensim%date_time%;update inventoryfolders set agentID='%uuid%'"
rem pause > nul

rem ***** File Reader section *****
rem Read and modify files which we need 
rem Regions.ini (we have to modidy the InternalPort parameter)
for /F "usebackq tokens=1,2,3 delims= " %%g IN (c:\opensimInstances\opensim%date_time%\bin\Regions\Regions.ini) do @if %%g == InternalPort (@echo %%g %%h !port! >> c:\opensimInstances\opensim%date_time%\bin\Regions\Regions_.ini & @set /a port=!port!+1) else (@echo %%g %%h %%i >> c:\opensimInstances\opensim%date_time%\bin\Regions\Regions_.ini)
del c:\opensimInstances\opensim%date_time%\bin\Regions\Regions.ini > nul
ren c:\opensimInstances\opensim%date_time%\bin\Regions\Regions_.ini Regions.ini > nul
rem OpenSim.ini (we have to update the db name and the http_listener_port parameter)
rem update the db
set cadorig=opensimdb07
set cadsust=opensim%date_time%
for /f "eol=; tokens=* delims=" %%i in (c:\opensimInstances\opensim%date_time%\bin\OpenSim.ini) do (set ANT=%%i & echo !ANT:%cadorig%=%cadsust%! >> c:\opensimInstances\opensim%date_time%\bin\OpenSim1.ini)
del c:\opensimInstances\opensim%date_time%\bin\OpenSim.ini > nul
rem update the http_listener_parameter
set cadorig=9000
set cadsust=%http_listener_port%
for /f "eol=; tokens=* delims=" %%i in (c:\opensimInstances\opensim%date_time%\bin\OpenSim1.ini) do (set ANT=%%i & echo !ANT:%cadorig%=%cadsust%! >> c:\opensimInstances\opensim%date_time%\bin\OpenSim.ini)
del c:\opensimInstances\opensim%date_time%\bin\Opensim1.ini > nul
rem bin\config-include\StandaloneCommon.ini (we have to update the db name)
set cadorig=opensimdb07
set cadsust=opensim%date_time%
for /f "eol=; tokens=* delims=" %%i in (c:\opensimInstances\opensim%date_time%\bin\config-include\StandaloneCommon.ini) do (set ANT=%%i & echo !ANT:%cadorig%=%cadsust%! >> c:\opensimInstances\opensim%date_time%\bin\config-include\StandaloneCommon_.ini)
del c:\opensimInstances\opensim%date_time%\bin\config-include\StandaloneCommon.ini > nul
ren c:\opensimInstances\opensim%date_time%\bin\config-include\StandaloneCommon_.ini StandaloneCommon.ini > nul
rem pause > nul

rem ***** Launch OpenSim *****
cd/ > nul
cd c:\opensimInstances\opensim%date_time%\bin > nul
rem echo http://osurl.org/193.61.190.230:%http_listener_port%/regionOne/127/124/25 c:\opensimInstances\opensim%date%_%time%\bin
echo opensim://193.61.190.230:%http_listener_port%/regionOne/127/124/25 c:\opensimInstances\opensim%date%_%time%\bin
rem echo secondlife://193.61.190.230:%http_listener_port%/regionOne/127/124/25 c:\opensimInstances\opensim%date%_%time%\bin
rem start opensim.exe
rem echo %1 %2
rem pause > nul
exit 

