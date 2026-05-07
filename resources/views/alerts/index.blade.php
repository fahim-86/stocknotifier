<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Price Alerts') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
            <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg border border-green-200">
                {{ session('success') }}
            </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                x-data="alertManager()"
                x-init="init(
                    {{ json_encode($availableCodes) }},
                    {{ json_encode($alerts->map(fn($a) => [
                        'id'           => $a->id,
                        'trading_code' => $a->trading_code,
                        'high_price'   => $a->high_price,
                        'low_price'    => $a->low_price,
                    ])) }}
                )">

                {{-- Header --}}
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Your Active Alerts</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Active trading hours: Sun–Thu, 10:00–14:30 BST
                        </p>
                    </div>
                    <button @click="addRow()" type="button"
                        class="inline-flex items-center gap-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors text-sm font-medium">
                        <span class="text-lg leading-none">+</span> Add Alert
                    </button>
                </div>

                {{-- Global error banner --}}
                <div x-show="globalError" x-cloak
                    class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm"
                    x-text="globalError"></div>

                {{-- Alerts table --}}
                <template x-if="rows.length">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                                        Trading Code
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Current LTP
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        High Alert ↑
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Low Alert ↓
                                    </th>
                                    <th class="px-4 py-3 w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(row, index) in rows" :key="index">
                                    <tr :class="{'bg-red-50': rowHasError(index)}">
                                        {{-- Trading Code --}}
                                        <td class="px-4 py-3">
                                            <select x-model="row.trading_code"
                                                @change="fetchLtp(row)"
                                                class="border-gray-300 rounded w-full text-sm focus:ring-blue-500 focus:border-blue-500"
                                                :class="{'border-red-400': fieldError(index, 'trading_code')}">
                                                <option value="">— Select —</option>
                                                <template x-for="code in availableCodes" :key="code">
                                                    <option :value="code"
                                                        :selected="row.trading_code === code"
                                                        x-text="code"></option>
                                                </template>
                                            </select>
                                            <p x-show="fieldError(index, 'trading_code')"
                                                class="text-red-600 text-xs mt-1"
                                                x-text="fieldError(index, 'trading_code')"></p>
                                        </td>

                                        {{-- LTP display --}}
                                        <td class="px-4 py-3">
                                            <span x-show="row.loading" class="text-gray-400 text-xs">Loading…</span>
                                            <span x-show="!row.loading && row.ltp !== null"
                                                class="font-mono font-medium text-gray-700"
                                                x-text="'৳ ' + Number(row.ltp).toFixed(2)"></span>
                                            <span x-show="!row.loading && row.ltp === null && row.trading_code"
                                                class="text-gray-400 text-xs">N/A</span>
                                        </td>

                                        {{-- High Price --}}
                                        <td class="px-4 py-3">
                                            <input type="number" step="0.01" min="0"
                                                x-model="row.high_price"
                                                placeholder="e.g. 52.00"
                                                class="border-gray-300 rounded w-32 text-sm focus:ring-blue-500 focus:border-blue-500"
                                                :class="{'border-red-400': fieldError(index, 'high_price')}">
                                            <p x-show="fieldError(index, 'high_price')"
                                                class="text-red-600 text-xs mt-1 max-w-xs"
                                                x-text="fieldError(index, 'high_price')"></p>
                                        </td>

                                        {{-- Low Price --}}
                                        <td class="px-4 py-3">
                                            <input type="number" step="0.01" min="0"
                                                x-model="row.low_price"
                                                placeholder="e.g. 44.00"
                                                class="border-gray-300 rounded w-32 text-sm focus:ring-blue-500 focus:border-blue-500"
                                                :class="{'border-red-400': fieldError(index, 'low_price')}">
                                            <p x-show="fieldError(index, 'low_price')"
                                                class="text-red-600 text-xs mt-1 max-w-xs"
                                                x-text="fieldError(index, 'low_price')"></p>
                                        </td>

                                        {{-- Remove --}}
                                        <td class="px-4 py-3 text-right">
                                            <button @click="removeRow(index)" type="button"
                                                class="text-red-500 hover:text-red-700 font-medium text-sm">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

                {{-- Empty state --}}
                <div x-show="!rows.length" class="text-center py-12 text-gray-400">
                    <p class="text-base">No alerts configured.</p>
                    <p class="text-sm mt-1">Click "+ Add Alert" to get started.</p>
                </div>

                {{-- Footer actions --}}
                <div class="mt-6 flex items-center justify-between" x-show="rows.length" x-cloak>
                    <p class="text-xs text-gray-400">
                        High alert fires when LTP &ge; your target.<br>
                        Low alert fires when LTP &le; your target.
                    </p>
                    <div class="flex items-center gap-4">
                        <p x-show="saveMessage"
                            x-text="saveMessage"
                            class="text-sm"
                            :class="saveSuccess ? 'text-green-600' : 'text-red-600'"></p>
                        <button @click="saveAlerts()" type="button"
                            :disabled="saving"
                            class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700
                                   disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium">
                            <span x-show="!saving">Save Alerts</span>
                            <span x-show="saving">Saving…</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function alertManager() {
            return {
                availableCodes: [],
                rows: [],
                saving: false,
                saveMessage: '',
                saveSuccess: false,
                globalError: '',
                fieldErrors: {}, // keyed by "index" or "index.field"

                init(codes, existingAlerts) {
                    this.availableCodes = codes;
                    const arr = Array.isArray(existingAlerts) ? existingAlerts : Object.values(existingAlerts);
                    this.rows = arr.map(a => ({
                        trading_code: String(a.trading_code || '').trim(),
                        high_price: a.high_price ?? '',
                        low_price: a.low_price ?? '',
                        ltp: null,
                        loading: false,
                    }));
                    // Load LTPs for pre-existing rows
                    this.rows.forEach(row => {
                        if (row.trading_code) this.fetchLtp(row);
                    });
                },

                addRow() {
                    this.rows = [...this.rows, {
                        trading_code: '',
                        high_price: '',
                        low_price: '',
                        ltp: null,
                        loading: false,
                    }];
                    this.fieldErrors = {};
                    this.globalError = '';
                },

                removeRow(index) {
                    this.rows = this.rows.filter((_, i) => i !== index);
                    this.rebuildErrors();
                },

                async fetchLtp(row) {
                    if (!row.trading_code) {
                        row.ltp = null;
                        return;
                    }
                    row.loading = true;
                    try {
                        const res = await fetch('/alerts/ltp?trading_code=' + encodeURIComponent(row.trading_code));
                        const data = await res.json();
                        row.ltp = res.ok ? data.ltp : null;
                    } catch {
                        row.ltp = null;
                    } finally {
                        row.loading = false;
                    }
                },

                rowHasError(index) {
                    return Object.keys(this.fieldErrors).some(k => k.startsWith(`alerts.${index}`));
                },

                fieldError(index, field) {
                    return this.fieldErrors[`alerts.${index}.${field}`] ||
                        this.fieldErrors[`alerts.${index}`] ||
                        null;
                },

                rebuildErrors() {
                    // Remap errors after a row is removed (keys shift)
                    this.fieldErrors = {};
                },

                async saveAlerts() {
                    this.fieldErrors = {};
                    this.globalError = '';
                    this.saveMessage = '';

                    // Client-side pre-validation
                    for (let i = 0; i < this.rows.length; i++) {
                        const row = this.rows[i];
                        if (!String(row.trading_code || '').trim()) {
                            this.fieldErrors[`alerts.${i}.trading_code`] = 'Select a trading code.';
                        }
                        const hp = row.high_price !== '' ? parseFloat(row.high_price) : null;
                        const lp = row.low_price !== '' ? parseFloat(row.low_price) : null;
                        if (hp === null && lp === null) {
                            this.fieldErrors[`alerts.${i}`] = 'Set at least one price target (high or low).';
                        }
                        if (row.ltp !== null) {
                            if (hp !== null && hp <= row.ltp) {
                                this.fieldErrors[`alerts.${i}.high_price`] =
                                    `Must be above current LTP (৳${row.ltp.toFixed(2)}).`;
                            }
                            if (lp !== null && lp >= row.ltp) {
                                this.fieldErrors[`alerts.${i}.low_price`] =
                                    `Must be below current LTP (৳${row.ltp.toFixed(2)}).`;
                            }
                        }
                        if (hp !== null && lp !== null && hp <= lp) {
                            this.fieldErrors[`alerts.${i}`] = 'High price must be greater than low price.';
                        }
                    }

                    if (Object.keys(this.fieldErrors).length > 0) return;

                    this.saving = true;
                    try {
                        const res = await fetch('/alerts', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                alerts: this.rows
                            }),
                        });

                        const data = await res.json();

                        if (res.ok) {
                            this.saveMessage = data.message || 'Alerts saved.';
                            this.saveSuccess = true;
                            setTimeout(() => location.reload(), 1200);
                        } else if (res.status === 422 && data.errors) {
                            // Map server-side errors back to field keys
                            this.fieldErrors = data.errors;
                            this.saveMessage = 'Please fix the errors above.';
                            this.saveSuccess = false;
                        } else {
                            throw new Error(data.message || 'Save failed.');
                        }
                    } catch (err) {
                        this.globalError = err.message || 'An unexpected error occurred.';
                        this.saveSuccess = false;
                    } finally {
                        this.saving = false;
                    }
                }
            };
        }
    </script>
</x-app-layout>