# Address Importer

A Laravel-based web application for importing and validating Australian addresses from CSV files. This application provides a user-friendly interface to upload CSV files, validate address data against Australian standards, and store valid addresses in the database.

## Features

- **CSV File Upload**: Upload CSV or Excel files containing address data
- **Smart Column Mapping**: Automatically maps CSV columns to address fields
- **Australian Address Validation**: Validates addresses against Australian standards
  - Required fields: Address Line 1, Suburb, State, Postcode
  - Postcode format validation (4 digits)
  - Australian state code validation (NSW, VIC, QLD, SA, WA, TAS, NT, ACT)
- **Two-Step Import Process**: Preview validated data before inserting into database
- **Validation Status Tracking**: Track valid and invalid addresses separately
- **Error Reporting**: Detailed error messages for invalid addresses
- **Modern UI**: Clean, responsive interface built with Tailwind CSS

## Requirements

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL, PostgreSQL, or SQLite database

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Address-Importer
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**
   Edit `.env` file and set your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=address_importer
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Build assets**
   ```bash
   npm run build
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

   Access the application at `http://localhost:8000`

## Usage

### Importing Addresses

1. Navigate to the home page
2. Click "Choose File" and select your CSV file
3. Click "Import CSV" to upload and validate
4. Review the validation results:
   - **Valid Addresses**: Shown in green, ready to be inserted
   - **Invalid Addresses**: Shown in red with error details
5. Click "Insert to Table" to save valid addresses to the database

### CSV Format Requirements

Your CSV file should contain the following columns (headers can vary):

- **Address Line 1** (required): Street address
- **Address Line 2** (optional): Apartment, suite, unit, etc.
- **Suburb** (required): Suburb or city name
- **State** (required): Australian state code (NSW, VIC, QLD, SA, WA, TAS, NT, ACT)
- **Postcode** (required): 4-digit Australian postcode

**Example CSV:**
```csv
Address1,Address2,Suburb,State,Postcode
123 Main Street,Apt 4,Sydney,NSW,2000
456 George Street,,Melbourne,VIC,3000
```


### Validation Rules

The application validates addresses according to Australian standards:

- **Address Line 1**: Must not be empty
- **Suburb**: Must not be empty
- **State**: Must be a valid Australian state code:
  - NSW (New South Wales)
  - VIC (Victoria)
  - QLD (Queensland)
  - SA (South Australia)
  - WA (Western Australia)
  - TAS (Tasmania)
  - NT (Northern Territory)
  - ACT (Australian Capital Territory)
- **Postcode**: Must be exactly 4 digits

## Testing

Test CSV files are included in the project root:

- `test-address-list.csv`: Valid addresses for testing
- `test-address-list_invalid.csv`: Invalid addresses for testing validation

Run the test suite:
```bash
php artisan test
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


