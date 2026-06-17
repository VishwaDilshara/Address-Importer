<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;
use App\Jobs\VerifyAddressJob;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AddressImportController extends Controller
{
    public function index()
    {
        $pendingValidAddresses = session('pending_valid_addresses', []);
        $pendingInvalidAddresses = session('pending_invalid_addresses', []);
        
        $dbValidAddresses = Address::where('validation_status', 'valid')->latest()->get();
        $dbInvalidAddresses = Address::where('validation_status', 'invalid')->latest()->get();
        $dbCorrectedAddresses = Address::where('validation_status', 'corrected')->latest()->get();

        return view('address-import.index', compact('pendingValidAddresses', 'pendingInvalidAddresses', 'dbValidAddresses', 'dbInvalidAddresses', 'dbCorrectedAddresses'));
    }
    // Import addresses from CSV file
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        $file = $request->file('file');

        try {
            $data = Excel::toCollection(new class implements ToCollection {
                public function collection(Collection $rows)
                {
                    return $rows;
                }
            }, $file);

            $rows = $data->first();
            
            // Get header row
            $headers = $rows->first();
            $rows->shift();

            // Normalize headers to lowercase for matching
            $normalizedHeaders = array_map(function($header) {
                return strtolower(trim(str_replace([' ', '_'], '', $header)));
            }, $headers->toArray());

            // Map column indices
            $columnMap = [];
            foreach ($normalizedHeaders as $index => $header) {
                if (strpos($header, 'address1') !== false || strpos($header, 'address1') !== false) {
                    $columnMap['address_1'] = $index;
                } elseif (strpos($header, 'address2') !== false || strpos($header, 'address2') !== false) {
                    $columnMap['address_2'] = $index;
                } elseif (strpos($header, 'suburb') !== false) {
                    $columnMap['suburb'] = $index;
                } elseif (strpos($header, 'state') !== false) {
                    $columnMap['state'] = $index;
                } elseif (strpos($header, 'postcode') !== false || strpos($header, 'pcode') !== false || strpos($header, 'postalcode') !== false) {
                    $columnMap['postcode'] = $index;
                }
            }

            $addresses = [];
            foreach ($rows as $row) {
                $addressData = [
                    'address_1' => isset($columnMap['address_1']) ? trim($row[$columnMap['address_1']] ?? '') : trim($row[0] ?? ''),
                    'address_2' => isset($columnMap['address_2']) ? trim($row[$columnMap['address_2']] ?? '') : trim($row[1] ?? ''),
                    'suburb' => isset($columnMap['suburb']) ? trim($row[$columnMap['suburb']] ?? '') : trim($row[2] ?? ''),
                    'state' => isset($columnMap['state']) ? trim($row[$columnMap['state']] ?? '') : trim($row[3] ?? ''),
                    'postcode' => isset($columnMap['postcode']) ? trim($row[$columnMap['postcode']] ?? '') : trim($row[4] ?? ''),
                ];
                $addresses[] = $addressData;
            }

            // Generate batch ID for tracking
            $batchId = Str::uuid();
            $total = count($addresses);

            // Initialize progress in cache
            Cache::put("address_verification_progress_{$batchId}", [
                'total' => $total,
                'processed' => 0,
                'valid' => 0,
                'invalid' => 0,
                'corrected' => 0,
                'results' => [],
            ], now()->addHours(2));

            // Store addresses in session for processing
            session(['import_batch_id' => $batchId]);
            session(['import_addresses' => $addresses]);

            // Dispatch jobs for each address
            foreach ($addresses as $index => $address) {
                VerifyAddressJob::dispatch($address, $batchId, $index + 1, $total);
            }

            return redirect()->route('address-import.processing', ['batchId' => $batchId]);

        } catch (\Exception $e) {
            return redirect()->route('address-import.index')
                ->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function processing(Request $request, $batchId)
    {
        return view('address-import.processing', compact('batchId'));
    }

    public function progress($batchId)
    {
        $progressKey = "address_verification_progress_{$batchId}";
        $progress = Cache::get($progressKey);

        if (!$progress) {
            return response()->json(['error' => 'Progress not found'], 404);
        }

        return response()->json($progress);
    }

    public function insertToDatabase()
    {
        $pendingValidAddresses = session('pending_valid_addresses', []);

        if (empty($pendingValidAddresses)) {
            return redirect()->route('address-import.index')
                ->with('error', 'No valid records to insert.');
        }

        try {
            $insertedCount = 0;

            foreach ($pendingValidAddresses as $addressData) {
                Address::create([
                    'address_1' => $addressData['address_1'],
                    'address_2' => $addressData['address_2'],
                    'suburb' => $addressData['suburb'],
                    'state' => $addressData['state'],
                    'postcode' => $addressData['postcode'],
                    'validation_status' => 'valid',
                    'validation_errors' => null,
                    'imported_at' => now(),
                ]);

                $insertedCount++;
            }

            // Clear session after successful insert
            session()->forget(['pending_valid_addresses', 'pending_invalid_addresses']);

            return redirect()->route('address-import.index')
                ->with('success', "Successfully inserted {$insertedCount} valid records into the database.");

        } catch (\Exception $e) {
            return redirect()->route('address-import.index')
                ->with('error', 'Error inserting records: ' . $e->getMessage());
        }
    }
}
