<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
    <div>
        <!-- Dropdown -->
        <select wire:model="selectedCode">
            <option value="">Select Trading Code</option>
            @foreach($stockList as $stock)
            <option value="{{ $stock->trading_code }}">{{ $stock->trading_code }}</option>
            @endforeach
        </select>

        <!-- Target Price Input -->
        <input type="number" step="0.01" wire:model="targetPrice" placeholder="Target Price">

        <button wire:click="addAlert">Add Alert</button>

        <!-- List of Active Alerts (Rows) -->
        <table>
            @foreach($alerts as $alert)
            <tr>
                <td>{{ $alert->trading_code }}</td>
                <td>{{ $alert->target_price }}</td>
                <td><button wire:click="removeAlert({{ $alert->id }})">Remove</button></td>
            </tr>
            @endforeach
        </table>
    </div>
</x-app-layout>