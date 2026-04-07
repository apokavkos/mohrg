# SYSTEM EXPORT: EVE Online Intelligence Center

This document is intended to be parsed by an AI-agent to rapidly acquire context on the `apokavkos.com` server environment and architecture.

## 1. Environment & Architecture
- **Host:** Hetzner Cloud (Ubuntu 24.04, 16GB RAM)
- **Primary Domain:** `apokavkos.com`
- **Methodology:** Strictly containerized using Docker and `docker-compose`. 
- **Repository Structure:** This workspace contains the definitive Infrastructure as Code (IaC) definitions inside the `/infrastructure` directory.

## 2. Core Databases & Components
- **SeAT (`seat-docker`):** Core EVE API manager. Uses `mariadb` inside the `seat-docker_seat-internal` network.
- **EVE SDE (`eve-sde`):** A standalone MariaDB instance holding the static data export natively.
- **Metabase (`infrastructure/metabase`):** Secondary BI tool exposed via Traefik.
- **Import Planner (`infrastructure/import-planner`):** Streamlit container hosted on `imports.apokavkos.com` that scrapes Goonmetrics and generates Jita multibuys.

## 3. Edge Routing (Traefik)
- All external HTTP/HTTPS traffic flows through the `seat-docker-traefik-1` container.
- **Crucial Note:** Traefik uses `entrypoints=https` and `tls.certresolver=primary`. Do not use legacy tags like `websecure` or `letsencrypt` on this node.

## 4. Security & SSO (Authelia)
- **Identity Provider:** Authelia (`infrastructure/authelia`). Accessible at `sso.apokavkos.com`.
- **Middleware:** `authelia@docker` is applied as a Traefik middleware to Metabase, Import Planner, and the MCP server.
- **File Backed:** Passwords use Argon2 hashes in `infrastructure/authelia/config/users_database.yml`.
- **Notification Hack:** Outbound 2FA setup links and OTPs are written to `/opt/authelia/config/notification.txt` instead of being emailed.

## 5. Intelligence Bridge (MCP Server)
- **FastMCP:** A Python-based Model Context Protocol server executing SQL queries directly against the SeAT and SDE databases.
- **Location:** `infrastructure/eve-mcp-server` exposed at `evemcp.apokavkos.com/sse`.
- **OAuth Context:** Claude.ai connects to this endpoint by establishing an OpenID Connect (OIDC) Bearer token handshake natively via the Authelia Identity Provider.
