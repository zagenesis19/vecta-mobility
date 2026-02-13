<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Store batch of events from frontend
     */
    public function store(Request $request)
    {
        $events = $request->input('events', []);
        $now = now();
        
        $insertData = [];
        foreach ($events as $event) {
            $insertData[] = [
                'user_id' => auth()->id(),
                'session_id' => $request->session()->getId(),
                'event_type' => $event['type'],
                'target' => $event['target'] ?? null,
                'meta' => isset($event['meta']) ? json_encode($event['meta']) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($insertData)) {
            DB::table('analytics_events')->insert($insertData);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Return Aggregated Stats for Dashboard
     */
    public function stats(Request $request)
    {
        $range = $request->input('range', '30_days');
        $startDate = match($range) {
            'today' => Carbon::today(),
            '7_days' => Carbon::now()->subDays(7),
            '30_days' => Carbon::now()->subDays(30),
            default => Carbon::now()->subDays(30),
        };

        // 1. OPERATIONAL HEALTH ❤️
        $totalTrips = Trip::where('created_at', '>=', $startDate)->count();
        $completedTrips = Trip::where('created_at', '>=', $startDate)->where('status', 'completed')->count();
        $completionRate = $totalTrips > 0 ? round(($completedTrips / $totalTrips) * 100, 1) : 0;

        // Wait Time (Avg minutes between Accepted and Arrived)
        $avgWaitTime = Trip::where('created_at', '>=', $startDate)
            ->whereNotNull('accepted_at')
            ->whereNotNull('driver_arrived_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, accepted_at, driver_arrived_at)) as avg_wait')
            ->value('avg_wait');
            
        // Cancellation Breakdown
        $cancelledTrips = Trip::where('created_at', '>=', $startDate)->where('status', 'cancelled')->count();
        $cancellationRate = $totalTrips > 0 ? round(($cancelledTrips / $totalTrips) * 100, 1) : 0;
        
        // 2. MARKET 🏍️ vs 🚗
        $marketSplit = Trip::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->select('vehicle_type', DB::raw('count(*) as total'), DB::raw('AVG(price) as avg_ticket'))
            ->groupBy('vehicle_type')
            ->get();

        // 3. FINANCIAL 💰
        $gmv = Trip::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->sum('price');
            
        $takeRate = 0.10; // 10% configurable
        $revenue = $gmv * $takeRate;

        // 4. GROWTH 📈 (MAU - Monthly Active Users)
        // Active Riders + Active Drivers in period
        $activeUsers = Trip::where('created_at', '>=', $startDate)
            ->select('passenger_id as uid')
            ->union(
                Trip::where('created_at', '>=', $startDate)->select('driver_id as uid')
            )
            ->count();

        // 5. LIVE ACTIVITY (Last 5 mins events)
        $liveActivity = DB::table('analytics_events')
            ->where('created_at', '>=', Carbon::now()->subMinutes(5))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function($ev) {
                // Decode Meta safely
                $ev->meta = json_decode($ev->meta);
                return $ev;
            });

        return response()->json([
            'operational' => [
                'completion_rate' => $completionRate,
                'cancellation_rate' => $cancellationRate,
                'avg_wait_time' => round($avgWaitTime ?? 0, 1),
                'total_trips' => $totalTrips
            ],
            'market' => $marketSplit,
            'financial' => [
                'gmv' => $gmv,
                'revenue' => $revenue,
            ],
            'growth' => [
                'active_users' => $activeUsers,
            ],
            'live_feed' => $liveActivity
        ]);
    }
}
