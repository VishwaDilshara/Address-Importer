<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Address Import</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Address Import</h1>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <!-- Upload Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Import Address File</h2>
            <form action="{{ route('address-import.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="file" class="block text-gray-700 font-medium mb-2">Select CSV or Excel File</label>
                    <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls" class="block w-full text-gray-700 border border-gray-300 rounded-lg p-2" required>
                    <p class="text-sm text-gray-500 mt-2">File should contain columns: Address 1, Address 2, Suburb, State, Postcode</p>
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                    Import Addresses
                </button>
            </form>
        </div>

        <!-- Pending Valid Addresses -->
        @if(count($pendingValidAddresses) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-8 border-2 border-blue-200">
                <h2 class="text-xl font-semibold text-blue-700 mb-4">Pending Valid Addresses ({{ count($pendingValidAddresses) }})</h2>
                <div class="overflow-x-auto mb-4">
                    <table class="min-w-full bg-white">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-700">Address 1</th>
                                <th class="px-4 py-2 text-left text-gray-700">Address 2</th>
                                <th class="px-4 py-2 text-left text-gray-700">Suburb</th>
                                <th class="px-4 py-2 text-left text-gray-700">State</th>
                                <th class="px-4 py-2 text-left text-gray-700">Postcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingValidAddresses as $address)
                                <tr class="border-b">
                                    <td class="px-4 py-2">{{ $address['address_1'] }}</td>
                                    <td class="px-4 py-2">{{ $address['address_2'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address['suburb'] }}</td>
                                    <td class="px-4 py-2">{{ $address['state'] }}</td>
                                    <td class="px-4 py-2">{{ $address['postcode'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <form action="{{ route('address-import.insert') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
                        Insert to Table
                    </button>
                </form>
            </div>
        @endif

        <!-- Pending Invalid Addresses -->
        @if(count($pendingInvalidAddresses) > 0)
            <div class="bg-white rounded-lg shadow-md p-6 mb-8 border-2 border-red-200">
                <h2 class="text-xl font-semibold text-red-700 mb-4">Pending Invalid Addresses ({{ count($pendingInvalidAddresses) }})</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-red-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-700">Address 1</th>
                                <th class="px-4 py-2 text-left text-gray-700">Address 2</th>
                                <th class="px-4 py-2 text-left text-gray-700">Suburb</th>
                                <th class="px-4 py-2 text-left text-gray-700">State</th>
                                <th class="px-4 py-2 text-left text-gray-700">Postcode</th>
                                <th class="px-4 py-2 text-left text-gray-700">Errors</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingInvalidAddresses as $address)
                                <tr class="border-b">
                                    <td class="px-4 py-2">{{ $address['address_1'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address['address_2'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address['suburb'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address['state'] ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address['postcode'] ?? '-' }}</td>
                                    <td class="px-4 py-2 text-red-600">{{ $address['validation_errors'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Database Valid Addresses -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-green-700 mb-4">Database Valid Addresses ({{ $dbValidAddresses->count() }})</h2>
            @if($dbValidAddresses->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-green-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-700">Address 1</th>
                                <th class="px-4 py-2 text-left text-gray-700">Address 2</th>
                                <th class="px-4 py-2 text-left text-gray-700">Suburb</th>
                                <th class="px-4 py-2 text-left text-gray-700">State</th>
                                <th class="px-4 py-2 text-left text-gray-700">Postcode</th>
                                <th class="px-4 py-2 text-left text-gray-700">Imported At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dbValidAddresses as $address)
                                <tr class="border-b">
                                    <td class="px-4 py-2">{{ $address->address_1 }}</td>
                                    <td class="px-4 py-2">{{ $address->address_2 ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address->suburb }}</td>
                                    <td class="px-4 py-2">{{ $address->state }}</td>
                                    <td class="px-4 py-2">{{ $address->postcode }}</td>
                                    <td class="px-4 py-2">{{ $address->imported_at ? $address->imported_at->format('Y-m-d H:i:s') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">No valid addresses in database yet.</p>
            @endif
        </div>

        <!-- Database Invalid Addresses -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-red-700 mb-4">Database Invalid Addresses ({{ $dbInvalidAddresses->count() }})</h2>
            @if($dbInvalidAddresses->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-red-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-700">Address 1</th>
                                <th class="px-4 py-2 text-left text-gray-700">Address 2</th>
                                <th class="px-4 py-2 text-left text-gray-700">Suburb</th>
                                <th class="px-4 py-2 text-left text-gray-700">State</th>
                                <th class="px-4 py-2 text-left text-gray-700">Postcode</th>
                                <th class="px-4 py-2 text-left text-gray-700">Errors</th>
                                <th class="px-4 py-2 text-left text-gray-700">Imported At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dbInvalidAddresses as $address)
                                <tr class="border-b">
                                    <td class="px-4 py-2">{{ $address->address_1 ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address->address_2 ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address->suburb ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address->state ?? '-' }}</td>
                                    <td class="px-4 py-2">{{ $address->postcode ?? '-' }}</td>
                                    <td class="px-4 py-2 text-red-600">{{ $address->validation_errors }}</td>
                                    <td class="px-4 py-2">{{ $address->imported_at ? $address->imported_at->format('Y-m-d H:i:s') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500">No invalid addresses in database.</p>
            @endif
        </div>
    </div>
</body>
</html>
