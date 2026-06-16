<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddressValidationService
{
    public function verifyAddress(array $addressData): array
    {
        $fullAddress = implode(', ', array_filter([
            $addressData['address_1'] ?? '',
            $addressData['address_2'] ?? '',
            $addressData['suburb'] ?? '',
            $addressData['state'] ?? '',
            $addressData['postcode'] ?? '',
        ]));

        try {
            Log::info('OSM API REQUEST', [
                'address' => $fullAddress
            ]);

            $response = Http::withHeaders([
                'User-Agent' => 'Laravel Address Importer/1.0'
            ])->timeout(10)->get(
                'https://nominatim.openstreetmap.org/search',
                [
                    'q' => $fullAddress,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                ]
            );

            Log::info('OSM API RESPONSE', [
                'status' => $response->status()
            ]);

            if (!$response->successful()) {
                return [
                    'status' => 'invalid',
                    'message' => 'Address validation service unavailable',
                    'corrected_address' => null,
                ];
            }

            $results = $response->json();

            if (empty($results)) {
                return [
                    'status' => 'invalid',
                    'message' => 'Address not found',
                    'corrected_address' => null,
                ];
            }

            $result = $results[0];

            // Extract address components from OSM response
            $correctedAddress = $this->extractAddressComponents($result);

            // Check if the address was corrected
            $isCorrected = $this->isAddressCorrected($addressData, $correctedAddress);

            if ($isCorrected) {
                return [
                    'status' => 'corrected',
                    'message' => 'Address was corrected based on OSM suggestions',
                    'corrected_address' => $correctedAddress,
                ];
            }

            return [
                'status' => 'valid',
                'message' => 'Address verified successfully',
                'corrected_address' => null,
            ];

        } catch (\Exception $e) {
            Log::error('OSM Validation Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'invalid',
                'message' => 'Validation error: ' . $e->getMessage(),
                'corrected_address' => null,
            ];
        }
    }

    /**
     * Extract address components from OSM Nominatim response
     *
     * @param array $result
     * @return array
     */
    protected function extractAddressComponents(array $result): array
    {
        $address = $result['address'] ?? [];
        
        $components = [
            'address_1' => '',
            'address_2' => '',
            'suburb' => '',
            'state' => '',
            'postcode' => '',
        ];

        // Build address_1 from house number and road/street
        if (!empty($address['house_number']) && !empty($address['road'])) {
            $components['address_1'] = $address['house_number'] . ' ' . $address['road'];
        } elseif (!empty($address['road'])) {
            $components['address_1'] = $address['road'];
        }

        // suburb can be in different fields
        $components['suburb'] = $address['suburb'] ?? $address['city'] ?? $address['town'] ?? $address['village'] ?? '';

        // state
        $components['state'] = $address['state'] ?? '';

        // postcode
        $components['postcode'] = $address['postcode'] ?? '';

        return $components;
    }

    /**
     * Check if address was corrected by OSM
     *
     * @param array $original
     * @param array $corrected
     * @return bool
     */
    protected function isAddressCorrected(array $original, array $corrected): bool
    {
        // Normalize for comparison
        $normalize = function($str) {
            return strtolower(trim(preg_replace('/\s+/', ' ', $str)));
        };

        $originalNormalized = array_map($normalize, $original);
        $correctedNormalized = array_map($normalize, $corrected);

        // Check if any significant field changed
        $significantFields = ['address_1', 'suburb', 'state', 'postcode'];
        
        foreach ($significantFields as $field) {
            if (!empty($originalNormalized[$field]) && 
                !empty($correctedNormalized[$field]) && 
                $originalNormalized[$field] !== $correctedNormalized[$field]) {
                return true;
            }
        }

        return false;
    }
}