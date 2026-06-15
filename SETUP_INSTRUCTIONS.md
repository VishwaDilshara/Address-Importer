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

### 1. Configure Google Maps API Key

Add your Google Maps API key to your `.env` file:

```env
GOOGLE_MAPS_API_KEY=your_google_maps_api_key_here
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

## Testing the Flow

1. **Navigate to the application**
   - Open `http://localhost:8000` in your browser

2. **Upload a CSV/Excel file**
   - Use the test files provided: `test-address-list.csv` or `test-address-list_invalid.csv`
   - Or create your own with columns: Address 1, Address 2, Suburb, State, Postcode

3. **Watch the processing screen**
   - You'll see the animated progress bar
   - Real-time updates as each address is validated
   - Status indicators for each address (valid/invalid/corrected)

4. **View results**
   - After processing, you'll see a summary with statistics
   - Click "View Results" to see the detailed address list
   - The main page now shows three sections:
     - Valid Addresses (green)
     - Corrected Addresses (yellow) - with suggested corrections
     - Invalid Addresses (red) - with error messages

## Database Schema Changes

The `addresses` table now includes:

- `validation_status` - enum: 'valid', 'invalid', 'corrected'
- `validation_message` - detailed message from API
- `corrected_address_1` - suggested address line 1
- `corrected_address_2` - suggested address line 2
- `corrected_suburb` - suggested suburb
- `corrected_state` - suggested state
- `corrected_postcode` - suggested postcode
- `google_api_response` - full API response (JSON)
- `is_google_verified` - boolean flag

## How It Works

1. **Import Process:**
   - User uploads CSV/Excel file
   - System parses the file and extracts addresses
   - Generates a unique batch ID for tracking
   - Dispatches a job for each address to the queue
   - Redirects to processing screen

2. **Verification Process:**
   - Each job calls the Google Geocoding API
   - API validates the full address
   - Results are stored in the database
   - Progress is updated in cache for real-time tracking

3. **Progress Tracking:**
   - Frontend polls the progress endpoint every second
   - Updates progress bar and statistics
   - Shows individual address cards with status
   - Displays completion summary when done

## Troubleshooting

### Queue not processing
- Make sure the queue worker is running: `php artisan queue:work`
- Check the queue connection in `.env`: `QUEUE_CONNECTION=database`

### Google API errors
- Verify your API key is correct
- Ensure Geocoding API is enabled in Google Cloud Console
- Check API quota limits

### Progress not updating
- Ensure cache is working: `CACHE_STORE=database` in `.env`
- Check that cache table exists and is accessible
- Verify the progress endpoint is accessible

### Addresses not being verified
- Check queue worker logs for errors
- Verify Google API key is set in `.env`
- Ensure jobs table exists and is being populated

## Files Modified/Created

### New Files:
- `app/Services/GoogleAddressVerificationService.php` - Google API integration
- `app/Jobs/VerifyAddressJob.php` - Async verification job
- `resources/views/address-import/processing.blade.php` - Processing UI
- `config/google.php` - Google API configuration
- `database/migrations/2026_06_15_110412_add_google_validation_fields_to_addresses_table.php` - Database schema update

### Modified Files:
- `app/Models/Address.php` - Added new fields to fillable and casts
- `app/Http/Controllers/AddressImportController.php` - Added processing and progress methods
- `routes/web.php` - Added processing and progress routes
- `resources/views/address-import/index.blade.php` - Added corrected addresses section
- `.env.example` - Added GOOGLE_MAPS_API_KEY

## Notes

- The system falls back to basic validation if Google API is not configured
- Progress data is cached for 2 hours
- Jobs are processed asynchronously, so the queue worker must be running
- The processing UI polls for progress every second
- All validation results are stored in the database for future reference
