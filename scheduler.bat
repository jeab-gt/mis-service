@echo off
"C:\Program Files\php\php-8.2.3\php.exe" "C:\inetpub\wwwroot\mis-service\artisan" schedule:run >> "C:\inetpub\wwwroot\mis-service\storage\logs\scheduler.log" 2>&1
