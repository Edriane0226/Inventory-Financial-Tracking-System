# Inventory Financial Tracking System

An inventory management and financial tracking system built with CodeIgniter 4. It helps teams manage stock movements, track expenses, and monitor sales-related stock-outs with a consistent, POS-style workflow.

## Key features

- Stock in and stock out flows with barcode support
- Product, category, unit type, and batch tracking
- Financial analytics for expenses and stock-out totals
- Audit trail support for activity tracking
- Role-based access models for staff and owners

## Tech stack

- PHP 8.2+
- CodeIgniter 4
- MySQL or MariaDB

## Getting started

1) Install dependencies:

```
composer install
```

2) Copy the environment file and update settings:

```
copy env .env
```

Set `app.baseURL` and database credentials in `.env`.

3) Run database migrations:

```
php spark migrate
```

4) Start the app (use your web server to point to the `public/` folder):

```
php spark serve
```

## Deployment note

`index.php` lives in the `public/` folder. Configure your web server to use `public/` as the document root.

## Server requirements

PHP version 8.2 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - do not disable)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library

## Stock-In Barcode Rules

Stock-In now uses server-generated EAN-13 barcodes. A barcode is reused for the same product identity (`product_name + category + unit_type`) across all batches, while batch number and expiry stay separate for FIFO and expiration tracking.
## Financial Analytics

Financial Analytics uses two expense sources: paid bills and automatic product expenses from Stock-In capital entries. Product expenses are read-only and generated per new Stock-In transaction.
## Financial analytics

Financial analytics draws from paid bills and automatic product expenses generated from stock-in capital entries. Product expenses are read-only and created per stock-in transaction.
