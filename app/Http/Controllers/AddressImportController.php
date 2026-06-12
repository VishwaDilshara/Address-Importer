<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Address;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class AddressImportController extends Controller
{
    public function index()
    {
        $pendingValidAddresses = session('pending_valid_addresses', []);
        $pendingInvalidAddresses = session('pending_invalid_addresses', []);
        
        $dbValidAddresses = Address::where('validation_status', 'valid')->latest()->get();
        $dbInvalidAddresses = Address::where('validation_status', 'invalid')->latest()->get();

        return view('address-import.index', compact('pendingValidAddresses', 'pendingInvalidAddresses', 'dbValidAddresses', 'dbInvalidAddresses'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        $file = $request->file('file');
        $importedCount = 0;
        $validCount = 0;
        $invalidCount = 0;

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

            $validAddresses = [];
            $invalidAddresses = [];

            foreach ($rows as $row) {
                $addressData = [
                    'address_1' => isset($columnMap['address_1']) ? trim($row[$columnMap['address_1']] ?? '') : trim($row[0] ?? ''),
                    'address_2' => isset($columnMap['address_2']) ? trim($row[$columnMap['address_2']] ?? '') : trim($row[1] ?? ''),
                    'suburb' => isset($columnMap['suburb']) ? trim($row[$columnMap['suburb']] ?? '') : trim($row[2] ?? ''),
                    'state' => isset($columnMap['state']) ? trim($row[$columnMap['state']] ?? '') : trim($row[3] ?? ''),
                    'postcode' => isset($columnMap['postcode']) ? trim($row[$columnMap['postcode']] ?? '') : trim($row[4] ?? ''),
                ];

                $validation = Address::validateAddress($addressData);

                $addressRecord = [
                    'address_1' => $addressData['address_1'],
                    'address_2' => $addressData['address_2'],
                    'suburb' => $addressData['suburb'],
                    'state' => $addressData['state'],
                    'postcode' => $addressData['postcode'],
                    'validation_status' => $validation['is_valid'] ? 'valid' : 'invalid',
                    'validation_errors' => $validation['is_valid'] ? null : implode(', ', $validation['errors']),
                ];

                if ($validation['is_valid']) {
                    $validAddresses[] = $addressRecord;
                    $validCount++;
                } else {
                    $invalidAddresses[] = $addressRecord;
                    $invalidCount++;
                }

                $importedCount++;
            }

            // Store validated records in session
            session(['pending_valid_addresses' => $validAddresses]);
            session(['pending_invalid_addresses' => $invalidAddresses]);

            return redirect()->route('address-import.index')
                ->with('success', "Import completed: {$importedCount} records validated ({$validCount} valid, {$invalidCount} invalid). Click 'Insert to Table' to save valid records.");

        } catch (\Exception $e) {
            return redirect()->route('address-import.index')
                ->with('error', 'Error importing file: ' . $e->getMessage());
        }
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
