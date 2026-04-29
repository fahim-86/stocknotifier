<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Price Alerts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                x-data="alertManager()"
                x-init="init({{ json_encode($availableCodes) }}, {{ json_encode($alerts->map(fn($a) => ['trading_code' => $a->trading_code, 'high_price' => $a->high_price, 'low_price' => $a->low_price])) }})">

                {{--Header with Add button--}}
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Your Active Alerts</h3>
                    <button @click="addRow()" type="button"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        + Add
                    </button>
                </div>

                {{--Table of alert rows--}}
                <template x-if="rows.length">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Trading Code</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">High Price</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Low Price</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in rows" :key="index">
                                <tr>
                                    <td class="px-4 py-2">
                                        <select x-model="row.trading_code" class="border-gray-300 rounded w-full">
                                            <option value="">-- Select --</option>
                                            <template x-for="code in availableCodes" :key="code">
                                                <option :value="code" :selected="row.trading_code === code" x-text="code"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" min="0" x-model="row.high_price"
                                            class="border-gray-300 rounded w-full" placeholder="High">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" step="0.01" min="0" x-model="row.low_price"
                                            class="border-gray-300 rounded w-full" placeholder="Low">
                                    </td>
                                    <td class="px-4 py-2">
                                        <button @click="removeRow(index)" type="button"
                                            class="text-red-600 hover:text-red-800 text-sm">
                                            Remove
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>

                {{-- Message when no rows --}}
                <div x-show="!rows.length" class="text-gray-500 text-center py-4">
                    No alerts yet. Click "Add" to create one.
                </div>

                <div class="mt-4 text-right" x-show="rows.length" x-cloak>
                    <button @click="saveAlerts()" type="button"
                        class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                        Save Alerts
                    </button>
                    <p x-text="message" class="text-sm mt-2" :class="{'text-green-600': success, 'text-red-600': !success}"></p>
                </div>
                {{-- Save Button --}}
            </div>
        </div>
    </div>

    {{-- Alpine.js CDN (if not already included in layout) --}}
    {{-- <script src="//unpkg.com/alpinejs"></script> --}}

    <script>
        function alertManager() {
            return {
                availableCodes: [],
                rows: [],
                message: '',
                success: false,

                init(codes, existingAlerts) {
                    this.availableCodes = codes;

                    // Handle both array and collection-like objects
                    const alertsArray = Array.isArray(existingAlerts) ? existingAlerts : Object.values(existingAlerts);

                    this.rows = alertsArray.map(a => ({
                        trading_code: String(a.trading_code || '').trim(),
                        high_price: a.high_price ?? '',
                        low_price: a.low_price ?? '',
                        saved: true,
                    }));
                },

                addRow() {
                    this.rows = [...this.rows, {
                        trading_code: '',
                        high_price: '',
                        low_price: '',
                        saved: false,
                    }];
                },

                removeRow(index) {
                    this.rows = this.rows.filter((_, i) => i !== index);
                },

                async saveAlerts() {
                    // Validate required trading_code
                    const emptyCode = this.rows.some(r => !String(r.trading_code || '').trim());
                    if (emptyCode) {
                        this.message = 'Please select a trading code for all rows.';
                        this.success = false;
                        return;
                    }

                    // Validate that at least one price is set per alert
                    const noPrices = this.rows.some(r => !r.high_price && !r.low_price);
                    if (noPrices) {
                        this.message = 'Each alert must have at least a high price or low price.';
                        this.success = false;
                        return;
                    }

                    try {
                        const response = await fetch('/alerts', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                alerts: this.rows
                            }),
                        });

                        const data = await response.json();
                        if (response.ok) {
                            this.message = data.message || 'Alerts saved successfully.';
                            this.success = true;
                            // Reload the page or reset form after successful save
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            throw new Error(data.message || 'Could not save alerts.');
                        }
                    } catch (error) {
                        console.error('Save error:', error);
                        this.message = error.message || 'An error occurred.';
                        this.success = false;
                    }
                }
            }
        }
    </script>
</x-app-layout>