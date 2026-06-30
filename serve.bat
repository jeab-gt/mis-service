@echo off
php -d upload_max_filesize=20M -d post_max_size=25M -d memory_limit=256M artisan serve %*
