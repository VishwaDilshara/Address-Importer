<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'address_1',
        'address_2',
        'suburb',
        'state',
        'postcode',
        'validation_status',
        'validation_errors',
        'validation_message',
        'corrected_address_1',
        'corrected_address_2',
        'corrected_suburb',
        'corrected_state',
        'corrected_postcode',
        'imported_at',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public static function validateAddress(array $data): array
    {
        $errors = [];

        // Check required fields
        if (empty($data['address_1'])) {
            $errors[] = 'Address 1 is required';
        }

        if (empty($data['suburb'])) {
            $errors[] = 'Suburb is required';
        }

        if (empty($data['state'])) {
            $errors[] = 'State is required';
        }

        if (empty($data['postcode'])) {
            $errors[] = 'Postcode is required';
        }

        // Validate postcode format (Australian postcode: 4 digits)
        if (!empty($data['postcode']) && !preg_match('/^\d{4}$/', $data['postcode'])) {
            $errors[] = 'Postcode must be 4 digits';
        }

        // Validate state (Australian states)
        // $validStates = ['NSW', 'VIC', 'QLD', 'SA', 'WA', 'TAS', 'NT', 'ACT'];
        $validStates = [
            // Australia
            'NSW', 'VIC', 'QLD', 'SA', 'WA', 'TAS', 'NT', 'ACT',

            // USA
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL',
            'GA', 'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA',
            'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE',
            'NV', 'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK',
            'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT',
            'VA', 'WA', 'WV', 'WI', 'WY', 'DC'
        ];
        if (!empty($data['state']) && !in_array(strtoupper($data['state']), $validStates)) {
            $errors[] = 'Invalid Australian state code';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
