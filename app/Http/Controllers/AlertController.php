<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    /**
     * Show the alert management page.
     */
    public function index()
    {
        // All trading codes for the dropdown (from stocks table)
        $availableCodes = Stock::orderBy('trading_code')->pluck('trading_code')->values()->toArray();

        // User's current alerts
        $alerts = Auth::user()->alerts()->where('is_active', true)->get();

        return view('alerts.index', compact('availableCodes', 'alerts'));
    }

    /**
     * Replace all alerts for the user with the new set.
     *
     * Expected JSON body (array):
     * [
     *   { "trading_code": "ABC", "high_price": 120.5, "low_price": 115 },
     *   ...
     * ]
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'alerts'          => 'required|array',
            'alerts.*.trading_code' => 'required|string|max:50',
            'alerts.*.high_price'   => 'nullable|numeric|min:0',
            'alerts.*.low_price'    => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();

        // Delete all current alerts of the user
        $user->alerts()->delete();

        // Insert the new ones
        $newAlerts = [];
        foreach ($validated['alerts'] as $alertData) {
            // Skip if both high and low are empty (no target set)
            if (empty($alertData['high_price']) && empty($alertData['low_price'])) {
                continue;
            }
            $newAlerts[] = new Alert([
                'trading_code' => $alertData['trading_code'],
                'high_price'   => $alertData['high_price'] ?? null,
                'low_price'    => $alertData['low_price'] ?? null,
                'is_active'    => true,
            ]);
        }

        if (!empty($newAlerts)) {
            $user->alerts()->saveMany($newAlerts);
        }

        return response()->json(['message' => 'Alerts saved.'], 200);
    }

    /**
     * Delete a single alert (optional, for immediate row removal).
     */
    public function destroy(Alert $alert)
    {
        // $this->authorize('delete', $alert);
        if ($alert->user_id !== Auth::id()) {
            abort(403);
        }
        $alert->delete();
        return response()->json(['message' => 'Alert removed.'], 200);
    }
}
