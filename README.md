# Address Import & Validation - Setup Instructions

## Overview
This Laravel application now includes real address verification using Google Geocoding API and a professional processing UI with animated progress tracking.

## New Features

### 1. Real Address Verification
- Validates addresses against Google Geocoding API
- Combines Address 1, Address 2, Suburb, State, and Postcode for full address validation
- Marks records as:
  - **Valid Address** - Address exists and matches exactly
  - **Invalid Address** - Address not found or doesn't exist
  - **Corrected Address** - API returned a better match/suggestion
- Stores validation results and messages for each record
- Displays suggested corrections when available

### 2. Professional Validation Processing UI
- Modern processing screen with animated progress bar
- Real-time progress updates showing current record being processed
- Visual status indicators:
  - Green checkmark for valid addresses
  - Red cross for invalid addresses
  - Yellow warning icon for corrected addresses
- Summary statistics after processing completion
- Smooth animations and SaaS-style user experience

## Setup Instructions

### 1. Configure openstreetmap API Key

```
https://nominatim.openstreetmap.org/search
```

**Note:** You need to enable the Geocoding API in your Google Cloud Console for this key to work.

### 2. Run Database Migrations

The migration has already been run, but if you need to reset:

```bash
php artisan migrate:rollback
php artisan migrate
```

### 3. Start Queue Worker

The address verification uses Laravel queues for async processing. Start the queue worker:

```bash
php artisan queue:work
```

**Important:** Keep this running in a separate terminal window while testing the import functionality.

### 4. Start Laravel Server

```bash
php artisan serve
```

## Project Structure

```
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── AddressImportController.php  # Main import logic
│   └── Models/
│       └── Address.php                      # Address model with validation
├── database/
│   └── migrations/
│       └── create_addresses_table.php       # Database schema
├── resources/
│   └── views/
│       └── address-import/
│           └── index.blade.php              # Main UI
└── routes/
    └── web.php                              # Route definitions
```

## Technologies Used

- **Backend**: Laravel 13.8 (PHP Framework)
- **Frontend**: Blade Templates, Tailwind CSS 4.0
- **CSV Processing**: Maatwebsite Excel 3.1
- **Build Tool**: Vite 8.0
- **Database**: Eloquent ORM

## Development

### Quick Setup Script

Use the composer setup script for automated installation:
```bash
composer run setup
```

### Development Server

Run all development services (server, queue, logs, vite):
```bash
composer run dev
```


