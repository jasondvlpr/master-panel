# InfraPanel - Multi-Tenant Infrastructure & Domain Manager

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)
![Filament](https://img.shields.io/badge/Filament-3.x-FBA918?style=for-the-badge&logo=filament)
![Cloudflare](https://img.shields.io/badge/Cloudflare-API-F38020?style=for-the-badge&logo=cloudflare)

InfraPanel is a robust, centralized dashboard built with **Laravel 12** and **Filament v3**, designed to seamlessly manage multi-tenant infrastructure, remote server nodes, and complex Cloudflare DNS configurations from a single, elegant interface.

## 🚀 Key Features

### 1. Advanced Cloudflare Integration
- **Global Sync:** Fetch and synchronize zones across multiple Cloudflare accounts simultaneously.
- **Live DNS Manager:** Create, view, and delete DNS records directly from a custom, high-speed Livewire modal without leaving the dashboard.
- **Automated Subdomain Tracking:** Deep scans DNS records (A, AAAA, CNAME) to automatically extract and list subdomains alongside root domains.
- **Direct DNS Push:** Push domain routing to remote server IP addresses with a single click.

### 2. Multi-Tenant Architecture (Client Registry)
- Manage clients (tenants) and link them directly to registered Cloudflare domains.
- Assign tenants to specific remote servers/nodes.
- Automated API communication with remote servers for tenant provisioning and domain alias registration.

### 3. Infrastructure Management
- Register and monitor multiple remote Web Servers.
- Store secure API Endpoints and Keys to enable automated multi-server task distribution.

### 4. Premium SaaS UI/UX
- Completely customized Filament dashboard featuring a modern **Purple, Blue, and White** color composition.
- Re-engineered, "borderless" SaaS-style tables for cleaner data visualization.
- Responsive, pill-shaped action buttons and soft gradient accents for a highly polished administrative experience.

---

## 🛠️ Technology Stack

- **Framework:** Laravel 12.x
- **Admin Panel:** Filament v3 (TALL Stack - Tailwind, Alpine.js, Laravel, Livewire)
- **Database:** MySQL
- **Styling:** Custom Inline & Filament Hooks (Optimized to bypass local Vite compilation bottlenecks)
- **Integrations:** Cloudflare REST API v4

---

## ⚙️ Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/jasondvlpr/master-panel.git
   cd master-panel
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Configure your database credentials in the `.env` file.*

4. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

5. **Start the Development Server:**
   ```bash
   php artisan serve
   ```
   *Access the panel at: `http://localhost:8000/admin`*

---

## 🛡️ Security

This system manages critical infrastructure credentials (Cloudflare API Tokens, Server Root API Keys). It is highly recommended to:
- Run this application behind a strict SSL/HTTPS connection.
- Restrict access to the `/admin` path using VPN or specific IP whitelists.
- Regularly rotate Cloudflare API tokens.

---

*Built with ❤️ for scalable infrastructure management.*
