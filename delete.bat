@echo off
Setlocal EnableDelayedExpansion

rem drop the database 'opensimddmmyyy_hhmm'
rem echo %1
set dir=%1
set bd=%dir:~-22%
rem echo %bd%
mysqladmin -f -u root --password=root drop %bd%
rem the ping is a litte trick to take 10 sec before delete the folder cos maybe the opensim prompt is still runing 
ping localhost -n 10 >nul
cd/
rmdir /q /s %1
pause