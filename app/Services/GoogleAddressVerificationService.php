<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAddressVerificationService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('google.maps.api_key');
        $this->apiUrl = config('google.maps.geocoding_url');
    }

    /**
     * Verify an address using Google Geocoding API
     *
     * @param array $addressData
     * @return array
     */
    public function verifyAddress(array $addressData): array
{
    if (empty($this->apiKey)) {
        Log::warning('Google Maps API key is not configured');
        return $this->getBasicValidationResult($addressData);
    }

    $fullAddress = $this->buildFullAddress($addressData);

    // 🔥 START TIMER
    $startTime = microtime(true);

    try {
        // 🔥 LOG REQUEST
        Log::info('Google API REQUEST', [
            'address' => $fullAddress,
            'key_present' => !empty($this->apiKey),
        ]);

        $response = Http::get($this->apiUrl, [
            'address' => $fullAddress,
            'key' => $this->apiKey,
        ]);

        // 🔥 LOG RESPONSE STATUS
        Log::info('Google API HTTP RESPONSE', [
            'status' => $response->status(),
        ]);

        if (!$response->successful()) {
            Log::error('Google Geocoding API request failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return $this->getBasicValidationResult($addressData);
        }

        $data = $response->json();

        // 🔥 LOG FULL API RESPONSE
        Log::info('Google API RESPONSE DATA', [
            'response' => $data
        ]);

        $result = $this->parseGeocodingResponse($data, $addressData);

        // 🔥 END TIMER
        Log::info('Google API TIME', [
            'seconds' => microtime(true) - $startTime
        ]);

        return $result;

    } catch (\Exception $e) {

        Log::error('Google API EXCEPTION', [
            'error' => $e->getMessage(),
            'address' => $addressData
        ]);

        return $this->getBasicValidationResult($addressData);
    }
}

    /**
     * Build full address string from components
     *
     * @param array $addressData
     * @return string
     */
    protected function buildFullAddress(array $addressData): string
    {
        $parts = array_filter([
            $addressData['address_1'] ?? '',
            $addressData['address_2'] ?? '',
            $addressData['suburb'] ?? '',
            $addressData['state'] ?? '',
            $addressData['postcode'] ?? '',
        ]);

        return implode(', ', $parts);
    }

    /**
     * Build components parameter for Google API
     *
     * @param array $addressData
     * @return string
     */
    protected function buildComponents(array $addressData): string
    {
        $components = [];
        
        if (!empty($addressData['state'])) {
            $components[] = 'administrative_area:' . $addressData['state'];
        }
        
        if (!empty($addressData['postcode'])) {
            $components[] = 'postal_code:' . $addressData['postcode'];
        }
        
        if (!empty($addressData['suburb'])) {
            $components[] = 'locality:' . $addressData['suburb'];
        }

        return implode('|', $components);
    }

    /**
     * Parse Google Geocoding API response
     *
     * @param array $data
     * @param array $originalAddress
     * @return array
     */
    protected function parseGeocodingResponse(array $data, array $originalAddress): array
    {
        if ($data['status'] === 'ZERO_RESULTS') {
            return [
                'status' => 'invalid',
                'message' => 'Address not found - no matching results',
                'is_google_verified' => true,
                'google_api_response' => $data,
                'corrected_address' => null,
            ];
        }

        if ($data['status'] !== 'OK') {
            return [
                'status' => 'invalid',
                'message' => 'Address verification failed: ' . ($data['error_message'] ?? $data['status']),
                'is_google_verified' => true,
                'google_api_response' => $data,
                'corrected_address' => null,
            ];
        }

        $result = $data['results'][0] ?? null;
        
        if (!$result) {
            return [
                'status' => 'invalid',
                'message' => 'No results found',
                'is_google_verified' => true,
                'google_api_response' => $data,
                'corrected_address' => null,
            ];
        }

        // Extract address components from Google response
        $correctedAddress = $this->extractAddressComponents($result);
        
        // Check if the address was corrected
        $isCorrected = $this->isAddressCorrected($originalAddress, $correctedAddress);
        
        if ($isCorrected) {
            return [
                'status' => 'corrected',
                'message' => 'Address was corrected based on Google suggestions',
                'is_google_verified' => true,
                'google_api_response' => $data,
                'corrected_address' => $correctedAddress,
            ];
        }

        return [
            'status' => 'valid',
            'message' => 'Address verified successfully',
            'is_google_verified' => true,
            'google_api_response' => $data,
            'corrected_address' => null,
        ];
    }

    /**
     * Extract address components from Google API response
     *
     * @param array $result
     * @return array
     */
    protected function extractAddressComponents(array $result): array
    {
        $components = [
            'address_1' => '',
            'address_2' => '',
            'suburb' => '',
            'state' => '',
            'postcode' => '',
        ];

        foreach ($result['address_components'] as $component) {
            $types = $component['types'];
            
            if (in_array('street_number', $types)) {
                $components['address_1'] = $component['long_name'] . ' ' . $components['address_1'];
            }
            
            if (in_array('route', $types)) {
                $components['address_1'] .= $component['long_name'];
            }
            
            if (in_array('sublocality', $types) || in_array('locality', $types)) {
                $components['suburb'] = $component['long_name'];
            }
            
            if (in_array('administrative_area_level_1', $types)) {
                $components['state'] = $component['short_name'];
            }
            
            if (in_array('postal_code', $types)) {
                $components['postcode'] = $component['long_name'];
            }
        }

        // Clean up address_1
        $components['address_1'] = trim($components['address_1']);

        return $components;
    }

    /**
     * Check if address was corrected by Google
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

    /**
     * Get basic validation result when Google API is not available
     *
     * @param array $addressData
     * @return array
     */
    protected function getBasicValidationResult(array $addressData): array
    {
        $validation = \App\Models\Address::validateAddress($addressData);
        
        return [
            'status' => $validation['is_valid'] ? 'valid' : 'invalid',
            'message' => $validation['is_valid'] ? 'Basic validation passed' : implode(', ', $validation['errors']),
            'is_google_verified' => false,
            'google_api_response' => null,
            'corrected_address' => null,
        ];
    }
}
