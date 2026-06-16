<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Addresses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(1.3); opacity: 0; }
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes checkmark {
            0% { stroke-dashoffset: 100; }
            100% { stroke-dashoffset: 0; }
        }
        @keyframes cross {
            0% { stroke-dashoffset: 100; }
            100% { stroke-dashoffset: 0; }
        }
        .pulse-ring {
            animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
        }
        .spinner {
            animation: spin 1s linear infinite;
        }
        .slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
        .checkmark-path {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: checkmark 0.5s ease-out forwards;
        }
        .cross-path {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: cross 0.5s ease-out forwards;
        }
        .progress-bar-fill {
            transition: width 0.5s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-slate-100 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4 relative">
                <div class="absolute inset-0 bg-blue-400 rounded-full pulse-ring"></div>
                <svg class="w-8 h-8 text-white spinner" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Processing Addresses</h1>
            <p class="text-gray-600">Validating addresses using OpenStreetMap Nominatim API</p>
        </div>

        <!-- Progress Section -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                <span id="progress-text" class="text-sm font-bold text-blue-600">0%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                <div id="progress-bar" class="progress-bar-fill bg-gradient-to-r from-blue-500 to-blue-600 h-4 rounded-full" style="width: 0%"></div>
            </div>
            <p id="current-record" class="text-sm text-gray-500 mt-3 text-center">Initializing...</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-md p-4 text-center">
                <div class="text-3xl font-bold text-gray-800" id="total-count">0</div>
                <div class="text-sm text-gray-500 mt-1">Total Records</div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4 text-center border-2 border-green-200">
                <div class="text-3xl font-bold text-green-600" id="valid-count">0</div>
                <div class="text-sm text-gray-500 mt-1">Valid</div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4 text-center border-2 border-red-200">
                <div class="text-3xl font-bold text-red-600" id="invalid-count">0</div>
                <div class="text-sm text-gray-500 mt-1">Invalid</div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-4 text-center border-2 border-yellow-200">
                <div class="text-3xl font-bold text-yellow-600" id="corrected-count">0</div>
                <div class="text-sm text-gray-500 mt-1">Corrected</div>
            </div>
        </div>

        <!-- Address List -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Address Validation Results</h2>
            <div id="address-list" class="space-y-3 max-h-96 overflow-y-auto">
                <!-- Addresses will be dynamically added here -->
            </div>
        </div>

        <!-- Completion Section (Hidden initially) -->
        <div id="completion-section" class="hidden mt-6">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl shadow-lg p-8 text-center text-white">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold mb-2">Processing Complete!</h2>
                <p class="text-white/90 mb-6">All addresses have been validated successfully.</p>
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-white/10 rounded-lg p-4">
                        <div class="text-2xl font-bold" id="final-valid">0</div>
                        <div class="text-sm text-white/80">Valid</div>
                    </div>
                    <div class="bg-white/10 rounded-lg p-4">
                        <div class="text-2xl font-bold" id="final-invalid">0</div>
                        <div class="text-sm text-white/80">Invalid</div>
                    </div>
                    <div class="bg-white/10 rounded-lg p-4">
                        <div class="text-2xl font-bold" id="final-corrected">0</div>
                        <div class="text-sm text-white/80">Corrected</div>
                    </div>
                </div>
                <a href="{{ route('address-import.index') }}" class="inline-block bg-white text-green-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    View Results
                </a>
            </div>
        </div>
    </div>

    <script>
        const batchId = '{{ $batchId }}';
        let isComplete = false;

        function getStatusIcon(status) {
            if (status === 'valid') {
                return `
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path class="checkmark-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                `;
            } else if (status === 'invalid') {
                return `
                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path class="cross-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                `;
            } else if (status === 'corrected') {
                return `
                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                `;
            } else {
                return `
                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-gray-400 spinner" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                `;
            }
        }

        function getStatusBadge(status) {
            if (status === 'valid') {
                return '<span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Valid</span>';
            } else if (status === 'invalid') {
                return '<span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-medium">Invalid</span>';
            } else if (status === 'corrected') {
                return '<span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Corrected</span>';
            } else {
                return '<span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">Processing</span>';
            }
        }

        function addAddressCard(result, index) {
            const addressList = document.getElementById('address-list');
            const card = document.createElement('div');
            card.className = 'slide-in bg-gray-50 rounded-lg p-4 border border-gray-200';
            card.id = `address-${result.id}`;
            
            const fullAddress = `${result.address.address_1}, ${result.address.address_2 || ''}, ${result.address.suburb}, ${result.address.state} ${result.address.postcode}`;
            
            let correctedInfo = '';
            if (result.corrected_address && result.status === 'corrected') {
                const correctedFull = `${result.corrected_address.address_1}, ${result.corrected_address.address_2 || ''}, ${result.corrected_address.suburb}, ${result.corrected_address.state} ${result.corrected_address.postcode}`;
                correctedInfo = `
                    <div class="mt-2 p-2 bg-yellow-50 rounded border border-yellow-200">
                        <p class="text-xs text-yellow-700 font-medium mb-1">Suggested Correction:</p>
                        <p class="text-xs text-yellow-800">${correctedFull}</p>
                    </div>
                `;
            }

            card.innerHTML = `
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-1">
                        ${getStatusIcon(result.status)}
                    </div>
                    <div class="flex-grow">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs text-gray-500">#${index}</span>
                            ${getStatusBadge(result.status)}
                        </div>
                        <p class="text-sm text-gray-700">${fullAddress}</p>
                        ${correctedInfo}
                        ${result.message ? `<p class="text-xs text-gray-500 mt-1">${result.message}</p>` : ''}
                    </div>
                </div>
            `;
            
            addressList.insertBefore(card, addressList.firstChild);
        }

        function updateAddressStatus(result, index) {
            const card = document.getElementById(`address-${result.id}`);
            if (card) {
                const iconContainer = card.querySelector('.flex-shrink-0');
                const badgeContainer = card.querySelector('.flex.items-center.justify-between.mb-1 span:last-child');
                
                iconContainer.innerHTML = getStatusIcon(result.status);
                badgeContainer.outerHTML = getStatusBadge(result.status);
                
                if (result.corrected_address && result.status === 'corrected') {
                    const correctedFull = `${result.corrected_address.address_1}, ${result.corrected_address.address_2 || ''}, ${result.corrected_address.suburb}, ${result.corrected_address.state} ${result.corrected_address.postcode}`;
                    if (!card.querySelector('.bg-yellow-50')) {
                        const addressText = card.querySelector('.text-sm.text-gray-700');
                        addressText.insertAdjacentHTML('afterend', `
                            <div class="mt-2 p-2 bg-yellow-50 rounded border border-yellow-200">
                                <p class="text-xs text-yellow-700 font-medium mb-1">Suggested Correction:</p>
                                <p class="text-xs text-yellow-800">${correctedFull}</p>
                            </div>
                        `);
                    }
                }
            }
        }

        async function fetchProgress() {
            if (isComplete) return;

            try {
                const response = await fetch(`{{ route('address-import.progress', ['batchId' => ':batchId']) }}`.replace(':batchId', batchId));
                const data = await response.json();

                if (data.error) {
                    console.error(data.error);
                    return;
                }

                // Update progress bar
                const percentage = Math.round((data.processed / data.total) * 100);
                document.getElementById('progress-bar').style.width = `${percentage}%`;
                document.getElementById('progress-text').textContent = `${percentage}%`;

                // Update current record text
                if (data.processed < data.total) {
                    document.getElementById('current-record').textContent = `Processing address ${data.processed + 1} of ${data.total}...`;
                } else {
                    document.getElementById('current-record').textContent = 'Processing complete!';
                }

                // Update statistics
                document.getElementById('total-count').textContent = data.total;
                document.getElementById('valid-count').textContent = data.valid;
                document.getElementById('invalid-count').textContent = data.invalid;
                document.getElementById('corrected-count').textContent = data.corrected;

                // Update address list
                data.results.forEach((result, index) => {
                    const existingCard = document.getElementById(`address-${result.id}`);
                    if (!existingCard) {
                        addAddressCard(result, result.index);
                    } else if (result.status !== 'processing') {
                        updateAddressStatus(result, result.index);
                    }
                });

                // Check if complete
                if (data.processed >= data.total) {
                    isComplete = true;
                    document.getElementById('completion-section').classList.remove('hidden');
                    document.getElementById('final-valid').textContent = data.valid;
                    document.getElementById('final-invalid').textContent = data.invalid;
                    document.getElementById('final-corrected').textContent = data.corrected;
                    
                    // Scroll to completion section
                    document.getElementById('completion-section').scrollIntoView({ behavior: 'smooth' });
                }
            } catch (error) {
                console.error('Error fetching progress:', error);
            }

            // Continue polling if not complete
            if (!isComplete) {
                setTimeout(fetchProgress, 1000);
            }
        }

        // Start polling
        fetchProgress();
    </script>
</body>
</html>
