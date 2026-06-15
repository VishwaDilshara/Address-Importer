<?php

namespace App\Jobs;

use App\Services\GoogleAddressVerificationService;
use App\Models\Address;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class VerifyAddressJob implements ShouldQueue
{
    use Queueable;

    protected $addressData;
    protected $batchId;
    protected $index;
    protected $total;

    /**
     * Create a new job instance.
     */
    public function __construct(array $addressData, string $batchId, int $index, int $total)
    {
        $this->addressData = $addressData;
        $this->batchId = $batchId;
        $this->index = $index;
        $this->total = $total;
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleAddressVerificationService $verificationService): void
    {
        // Verify address using Google API
        $verificationResult = $verificationService->verifyAddress($this->addressData);

        // Store the result in database
        $address = Address::create([
            'address_1' => $this->addressData['address_1'],
            'address_2' => $this->addressData['address_2'],
            'suburb' => $this->addressData['suburb'],
            'state' => $this->addressData['state'],
            'postcode' => $this->addressData['postcode'],
            'validation_status' => $verificationResult['status'],
            'validation_errors' => $verificationResult['status'] === 'invalid' ? $verificationResult['message'] : null,
            'validation_message' => $verificationResult['message'],
            'corrected_address_1' => $verificationResult['corrected_address']['address_1'] ?? null,
            'corrected_address_2' => $verificationResult['corrected_address']['address_2'] ?? null,
            'corrected_suburb' => $verificationResult['corrected_address']['suburb'] ?? null,
            'corrected_state' => $verificationResult['corrected_address']['state'] ?? null,
            'corrected_postcode' => $verificationResult['corrected_address']['postcode'] ?? null,
            'google_api_response' => $verificationResult['google_api_response'],
            'is_google_verified' => $verificationResult['is_google_verified'],
            'imported_at' => now(),
        ]);

        // Update progress in cache
        $this->updateProgress($address->id, $verificationResult);
    }

    /**
     * Update progress in cache for real-time tracking
     */
    protected function updateProgress(int $addressId, array $verificationResult): void
    {
        $progressKey = "address_verification_progress_{$this->batchId}";
        
        $progress = Cache::get($progressKey, [
            'total' => $this->total,
            'processed' => 0,
            'valid' => 0,
            'invalid' => 0,
            'corrected' => 0,
            'results' => [],
        ]);

        $progress['processed']++;
        $progress[$verificationResult['status']]++;
        
        $progress['results'][] = [
            'id' => $addressId,
            'index' => $this->index,
            'status' => $verificationResult['status'],
            'message' => $verificationResult['message'],
            'address' => $this->addressData,
            'corrected_address' => $verificationResult['corrected_address'],
        ];

        Cache::put($progressKey, $progress, now()->addHours(2));
    }
}
