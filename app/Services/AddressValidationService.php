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
                    'google_api_response' => null,
                    'is_google_verified' => false,
                ];
            }

            $results = $response->json();

            if (empty($results)) {
                return [
                    'status' => 'invalid',
                    'message' => 'Address not found',
                    'corrected_address' => null,
                    'google_api_response' => null,
                    'is_google_verified' => false,
                ];
            }

            $result = $results[0];

            return [
                'status' => 'valid',
                'message' => 'Address verified successfully',
                'corrected_address' => [
                    'address_1' => $addressData['address_1'] ?? '',
                    'address_2' => $addressData['address_2'] ?? '',
                    'suburb' => $addressData['suburb'] ?? '',
                    'state' => $addressData['state'] ?? '',
                    'postcode' => $addressData['postcode'] ?? '',
                ],
                'google_api_response' => $result,
                'is_google_verified' => false,
            ];

        } catch (\Exception $e) {

            Log::error('OSM Validation Error', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'invalid',
                'message' => 'Validation error: ' . $e->getMessage(),
                'corrected_address' => null,
                'google_api_response' => null,
                'is_google_verified' => false,
            ];
        }
    }
}