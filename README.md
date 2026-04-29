<p align="center"><a href="https://" target="_blank"><img src="/public/images/logo.svg" width="200" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# StockBuzz

A Laravel web application that monitors the Dhaka Stock Exchange (DSE) and sends real-time email alerts when a stock’s last traded price (LTP) matches your defined target price.

## Features

- **Live Stock Data:** Fetches trading codes and LTP from the [DSE Latest Share Price](https://dsebd.org/latest_share_price_scroll_l.php) page every minute during market hours.
- **Custom Alerts:** Select multiple trading codes and set individual target prices.
- **Instant Email Notification:** Sends an email the moment a stock’s LTP equals your target price.
- **User Accounts:** Secure registration and login via email.
- **Active Hours Only:** The data fetching and alert checking are automatically restricted to DSE trading hours (Sunday–Thursday, 10:00 AM – 2:30 PM BST).
- **Responsive UI:** Built with Laravel Livewire for a dynamic, single-page experience without complex JavaScript.

## Tech Stack

- **Backend:** Laravel 10/11 (PHP 8.1+)
- **Frontend:** Laravel Livewire, Blade, Tailwind CSS (optional)
- **Database:** MySQL / MariaDB
- **Scraping:** Laravel HTTP Client + Symfony DomCrawler
- **Notifications:** Laravel Notifications (email channel)
- **Queue:** Database/Laravel Horizon for asynchronous email delivery

## Prerequisites

Before installing, ensure your environment meets the following:

- **PHP** >= 8.1 (with `curl`, `dom`, `mbstring`, `pdo_mysql` extensions)
- **Composer** (latest stable)
- **MySQL** (or compatible database)
- **Node.js & NPM** (for frontend assets, if using Vite)
- **Git** (for cloning the repository)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/fahim-86/stocknotifier.git
cd stocknotifier
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Set Up Environment File

Copy the example environment file and generate an application key:

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database & Mail

Edit the `.env ` file with your database credentials and mail server settings.

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dse_alerts
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io     # For development use Mailtrap/Mailpit
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="alerts@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Set the Application Timezone

In the same `.env` file, set the timezone to Bangladesh Standard Time:

```bash
APP_TIMEZONE=Asia/Dhaka
```

This ensures the scheduler runs according to DSE market hours.

### 6. Install & Build Frontend Assets

```bash
npm install
npm run build
```

### 7. Run Migrations and Seeders

This creates the necessary tables and populates the `stocks` table with an initial snapshot (optional seeder included).

```bash
php artisan migrate --seed
```

### 8. Create Storage Link (if needed)

```bash
php artisan storage:link
```

### 9. Run the Application

```bash
php artisan serve
```

The app will be available at `http://127.0.0.1:8000`.

## Scheduling & Queue Processing

The core functionality relies on one fetch command:

- `fetch:ltp` – Fetches the latest share prices from DSE.

**During local development**, run the scheduler in foreground mode:

```bash
php artisan schedule:work
```

**For production**, add a single cron entry to your server:

```bash
cron

* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

The schedule is automatically restricted to market hours (Sun–Thu, 10:00–14:30 Asia/Dhaka).

**Email sending is queued** to avoid slowing down the scheduler. Run a queue worker:

```bash
php artisan queue:work
```

Or use Supervisor for long-running processes. During testing, you can set `QUEUE_CONNECTION=sync` in `.env` to send emails immediately (not recommended for production).

## Manual Testing

You can run the commands individually to test:
`--force` will let you bypass the schedule code

```bash
php artisan fetch:ltp --force
```

The `fetch:ltp` command will populate the stocks table with current DSE data. The `check:alerts` command will scan all active alerts.

## Usage

1. **Register** a new account using a valid email address.

2. **Login** to your dashboard.

3. **Add an Alert:**
    - Select a trading code from the dropdown (populated from the `stocks` cache).

    - Enter your target expected price for High and Low (e.g., `21.50`).

    - Click “Add Alert” to create a new row.

4. **Manage Alerts:** Existing alerts appear in a table with a “Remove” button to delete them.

5. **Wait for the Match:** When the stock’s LTP hits your target price, an email will be sent to your registered address instantly.

## Contributing

Contributions, issues, and feature requests are welcome. Feel free to check [issues page](https://github.com/fahim-86/stocknotifier/issues).

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Security Vulnerabilities

If you discover a security vulnerability, please send an e-mail to [your email] instead of using the issue tracker.

##

**Disclaimer:** This application relies on the DSE website’s HTML structure for data scraping. Any changes to the DSE page may temporarily break the data fetching process.
