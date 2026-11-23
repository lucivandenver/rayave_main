# Running Raymundo Avenue Flower Shop with Docker

## Prerequisites
* **Docker Desktop** must be open and running.

## Start the System (with Clickable Terminal Links)
```powershell
.\start.ps1
```
*(Or add `-Open` to launch the browser automatically: `.\start.ps1 -Open`)*

Or using standard Docker Compose directly:
```powershell
docker compose up -d
```
*(Note: Docker's `-d` detached flag hides container stdout by design; run `docker compose up` without `-d` or `.\start.ps1` to see the clickable URL banner directly in the terminal.)*

If you changed `Dockerfile` and want to rebuild:
```powershell
.\start.ps1 -Build
```

## Open in Browser
* **Shop / Web System:** http://localhost:8080 (or http://localhost:8080/flowershop-main)
* **Customize Live Bouquet Studio:** http://localhost:8080/products.php?category=17
* **Admin Panel:** http://localhost:8080/admin/
* **phpMyAdmin (Database UI):** http://localhost:8081 (User: `root`, Password: *(blank)*)
* **MySQL Host Port (for external DB clients):** `127.0.0.1:3307`, Database: `flowershop`

> Ports `8080`, `8081`, and `3307` are used so Docker never conflicts with Laragon (`80` / `3306`).

## Useful Commands
```powershell
# Check container status
docker compose ps

# View Apache / PHP logs
docker compose logs -f app

# Stop containers (keeps database data)
docker compose down

# Fresh reset & re-import database from database/init/01-flowershop.sql
docker compose down -v
docker compose up -d
```
