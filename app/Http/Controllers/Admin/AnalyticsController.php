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
                $ev->meta = json_decode($ev->meta);
                return $ev;
            });

        // ==========================================
        // 6. REGISTROS POR DÍA (Line Chart - últimos 30 días)
        // ==========================================
        $registrationTrend = User::where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                'role',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date', 'role')
            ->orderBy('date')
            ->get();

        // Procesar en formato {date, drivers, passengers}
        $trendMap = [];
        foreach ($registrationTrend as $row) {
            $d = $row->date;
            if (!isset($trendMap[$d])) {
                $trendMap[$d] = ['date' => $d, 'drivers' => 0, 'passengers' => 0];
            }
            if ($row->role === 'driver') {
                $trendMap[$d]['drivers'] = $row->total;
            } else if ($row->role === 'passenger') {
                $trendMap[$d]['passengers'] = $row->total;
            }
        }
        $registrations = array_values($trendMap);

        // ==========================================
        // 7. CONDUCTORES POR MUNICIPIO (Doughnut)
        // ==========================================
        $driversByMunicipality = User::where('role', 'driver')
            ->whereNotNull('municipality_id')
            ->join('municipalities', 'users.municipality_id', '=', 'municipalities.id')
            ->select('municipalities.name as municipality', DB::raw('COUNT(*) as total'))
            ->groupBy('municipalities.name')
            ->orderByDesc('total')
            ->get();

        // ==========================================
        // 8. MÉTODOS DE PAGO (Pie Chart)
        // ==========================================
        $paymentMethods = Trip::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->whereNotNull('payment_method')
            ->select('payment_method', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_method')
            ->get();

        // ==========================================
        // 9. VIAJES POR DÍA DE SEMANA (Bar Chart)
        // ==========================================
        $tripsByWeekday = Trip::where('created_at', '>=', $startDate)
            ->select(DB::raw('DAYOFWEEK(created_at) as day_num'), DB::raw('COUNT(*) as total'))
            ->groupBy('day_num')
            ->orderBy('day_num')
            ->get()
            ->map(function($row) {
                $days = ['', 'Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
                $row->day_name = $days[$row->day_num] ?? '?';
                return $row;
            });

        // ==========================================
        // 10. DISTRIBUCIÓN DE CALIFICACIONES (Bar Chart)
        // ==========================================
        $ratingsDistribution = DB::table('reviews')
            ->select(DB::raw('FLOOR(rating) as stars'), DB::raw('COUNT(*) as total'))
            ->groupBy('stars')
            ->orderBy('stars')
            ->get();

        // ==========================================
        // 11. PANORAMA DE FLOTA (Doughnut + Stats)
        // ==========================================
        $fleetByType = DB::table('vehicles')
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->get();

        $fleetAvgYear = DB::table('vehicles')->avg('year');
        $fleetTotal = DB::table('vehicles')->count();

        // ==========================================
        // 12. ESTADÍSTICAS EXTRA PARA KPIs
        // ==========================================
        $totalDrivers = User::where('role', 'driver')->count();
        $approvedDrivers = User::where('role', 'driver')->where('is_approved', true)->count();
        $totalPassengers = User::where('role', 'passenger')->count();
        $pendingVerifications = User::where('identity_status', 'pending')->count();

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
                'total_drivers' => $totalDrivers,
                'approved_drivers' => $approvedDrivers,
                'total_passengers' => $totalPassengers,
                'pending_verifications' => $pendingVerifications,
            ],
            'live_feed' => $liveActivity,
            // Nuevas gráficas
            'registrations_trend' => $registrations,
            'drivers_by_municipality' => $driversByMunicipality,
            'payment_methods' => $paymentMethods,
            'trips_by_weekday' => $tripsByWeekday,
            'ratings_distribution' => $ratingsDistribution,
            'fleet' => [
                'by_type' => $fleetByType,
                'avg_year' => round($fleetAvgYear ?? 0),
                'total' => $fleetTotal,
            ],
        ]);
    }
}

