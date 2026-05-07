<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    public function index()
    {
        $availableCodes = Stock::orderBy('trading_code')
            ->pluck('trading_code')
            ->values()
            ->toArray();

        $alerts = Auth::user()
            ->alerts()
            ->where('is_active', true)
            ->latest()
            ->get();

        return view('alerts.index', compact('availableCodes', 'alerts'));
    }

    /**
     * Return the current LTP for a trading code (used by frontend to validate inputs).
     */
    public function ltp(Request $request): JsonResponse
    {
        $request->validate(['trading_code' => 'required|string']);

        $stock = Stock::where('trading_code', $request->trading_code)->first();

        if (! $stock) {
            return response()->json(['ltp' => null, 'message' => 'Stock not found'], 404);
        }

        return response()->json([
            'ltp'          => (float) $stock->ltp,
            'trading_code' => $stock->trading_code,
            'fetched_at'   => $stock->fetched_at?->diffForHumans(),
        ]);
    }

    /**
     * Save (replace all) the user's alerts.
     * BUG FIX: Validates high_price > current LTP and low_price < current LTP.
     */
    public function store(Request $request): JsonResponse
    {
        // Basic structural validation
        $validated = $request->validate([
            'alerts'                    => 'required|array|min:1',
            'alerts.*.trading_code'     => 'required|string|max:50|exists:stocks,trading_code',
            'alerts.*.high_price'       => 'nullable|numeric|min:0',
            'alerts.*.low_price'        => 'nullable|numeric|min:0',
        ]);

        // BUG FIX: LTP-aware price validation per row
        $errors = [];
        foreach ($validated['alerts'] as $index => $alertData) {
            $hp = isset($alertData['high_price']) && $alertData['high_price'] !== ''
                ? (float) $alertData['high_price'] : null;
            $lp = isset($alertData['low_price']) && $alertData['low_price'] !== ''
                ? (float) $alertData['low_price'] : null;

            if ($hp === null && $lp === null) {
                $errors["alerts.{$index}"] = 'Set at least one price target.';
                continue;
            }

            $stock = Stock::where('trading_code', $alertData['trading_code'])->first();
            $ltp   = $stock ? (float) $stock->ltp : null;

            if ($ltp !== null) {
                // High alert: target must be ABOVE current LTP (otherwise already triggered)
                if ($hp !== null && $hp <= $ltp) {
                    $errors["alerts.{$index}.high_price"] =
                        "High price must be above current LTP (৳{$ltp}) for {$alertData['trading_code']}.";
                }
                // Low alert: target must be BELOW current LTP
                if ($lp !== null && $lp >= $ltp) {
                    $errors["alerts.{$index}.low_price"] =
                        "Low price must be below current LTP (৳{$ltp}) for {$alertData['trading_code']}.";
                }
            }

            // High must be greater than low when both are set
            if ($hp !== null && $lp !== null && $hp <= $lp) {
                $errors["alerts.{$index}"] = 'High price must be greater than low price.';
            }
        }

        if (! empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }

        $user = Auth::user();
        $user->alerts()->delete();

        $newAlerts = [];
        foreach ($validated['alerts'] as $alertData) {
            $hp = isset($alertData['high_price']) && $alertData['high_price'] !== ''
                ? (float) $alertData['high_price'] : null;
            $lp = isset($alertData['low_price']) && $alertData['low_price'] !== ''
                ? (float) $alertData['low_price'] : null;

            if ($hp === null && $lp === null) continue;

            $newAlerts[] = new Alert([
                'trading_code' => $alertData['trading_code'],
                'high_price'   => $hp,
                'low_price'    => $lp,
                'is_active'    => true,
            ]);
        }

        if (! empty($newAlerts)) {
            $user->alerts()->saveMany($newAlerts);
        }

        return response()->json(['message' => 'Alerts saved successfully.'], 200);
    }

    public function destroy(Alert $alert): JsonResponse
    {
        if ($alert->user_id !== Auth::id()) {
            abort(403);
        }
        $alert->delete();
        return response()->json(['message' => 'Alert removed.'], 200);
    }
}
