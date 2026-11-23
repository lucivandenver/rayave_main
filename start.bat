@echo off
docker compose up -d
if errorlevel 1 exit /b %errorlevel%
echo.
echo ============================================================
echo   Raymundo Avenue Flower Shop is now running!
echo ============================================================
echo   Shop URL:          http://localhost:8080
echo   Customize Studio:  http://localhost:8080/products.php?category=17
echo   Admin Panel:       http://localhost:8080/admin/
echo   phpMyAdmin:        http://localhost:8081
echo ============================================================
echo.
