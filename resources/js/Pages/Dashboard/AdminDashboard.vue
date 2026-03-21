<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import "leaflet/dist/leaflet.css";
import L from "leaflet"; 
import "leaflet.heat";   
import { LMap, LTileLayer } from "@vue-leaflet/vue-leaflet";
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Doughnut } from 'vue-chartjs';

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    trips: { type: Array, default: () => [] },
    adminStats: { type: Object, default: () => ({}) },
    driverLocations: { type: Array, default: () => [] } 
});

// --- Estado reactivo ---
const stats = ref({ ...props.adminStats });
const recentTrips = ref([...props.trips]);
const heatData = ref([...props.driverLocations]);

// --- MAPA ---
const zoom = ref(11); 
const center = ref([10.19, -66.79]); // Centro de Valles del Tuy (entre Cúa y Sta Teresa)
const mapReady = ref(false); 
const mapRef = ref(null);
let heatLayer = null;

const onMapReady = (mapObject) => {
    console.log("🗺️ Mapa Admin listo.");
    if (heatData.value.length > 0) {
        heatLayer = L.heatLayer(heatData.value, { 
            radius: 40, 
            blur: 30, 
            maxZoom: 15,
            gradient: { 0.2: '#3b82f6', 0.4: '#10b981', 0.6: '#f59e0b', 0.8: '#f97316', 1.0: '#ef4444' }
        }).addTo(mapObject);
        center.value = heatData.value[0];
    }
};



// --- POLLING: actualización en tiempo real ---
let pollInterval = null;

const refreshStats = async () => {
    try {
        const response = await axios.get(route('admin.analytics.stats'), { params: { range: '30_days' } });
        const data = response.data;
        stats.value = {
            total_trips: data.operational.total_trips,
            completed_trips: Math.round(data.operational.total_trips * data.operational.completion_rate / 100),
            cancelled_trips: Math.round(data.operational.total_trips * data.operational.cancellation_rate / 100),
            active_trips: stats.value.active_trips ?? 0,
            in_progress_trips: stats.value.in_progress_trips ?? 0,
            total_drivers: data.growth.total_drivers,
            approved_drivers: data.growth.approved_drivers,
            total_passengers: data.growth.total_passengers,
            total_revenue: data.financial.gmv,
            avg_trip_price: data.operational.total_trips > 0 ? (data.financial.gmv / data.operational.total_trips).toFixed(2) : 0,
            completion_rate: data.operational.completion_rate,
            pending_verifications: data.growth.pending_verifications,
            
            // Datos para Gráficas
            cancellation_reasons: data.operational.cancellation_reasons || [],
            rejection_reasons: data.operational.rejection_reasons || {},
        };
    } catch (e) {
        // silencioso
    }
};

// --- DATA PARA GRÁFICAS ---
const cancellationChartData = computed(() => {
    const labels = stats.value.cancellation_reasons?.map(r => r.cancellation_reason) || [];
    const data = stats.value.cancellation_reasons?.map(r => r.total) || [];
    return {
        labels,
        datasets: [{
            backgroundColor: ['#f87171', '#fb923c', '#fbbf24', '#a3e635', '#22d3ee', '#818cf8'],
            data
        }]
    };
});

const rejectionChartData = computed(() => {
    const labels = Object.keys(stats.value.rejection_reasons || {});
    const data = Object.values(stats.value.rejection_reasons || {});
    return {
        labels,
        datasets: [{
            backgroundColor: ['#f472b6', '#c084fc', '#60a5fa', '#34d399', '#facc15'],
            data
        }]
    };
});
const chartOptions = { responsive: true, maintainAspectRatio: false };

// Computed helpers
const statusColor = (status) => {
    const map = {
        'completed': 'bg-green-100 text-green-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'accepted': 'bg-indigo-100 text-indigo-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'cancelled': 'bg-red-100 text-red-800',
    };
    return map[status] || 'bg-gray-100 text-gray-800';
};

const statusLabel = (status) => {
    const map = {
        'completed': '✅ Completado',
        'in_progress': '🚀 En Curso',
        'accepted': '🤝 Aceptado',
        'pending': '⏳ Pendiente',
        'cancelled': '❌ Cancelado',
    };
    return map[status] || status;
};

onMounted(() => {
    setTimeout(() => { mapReady.value = true; }, 100);
    pollInterval = setInterval(refreshStats, 15000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <div class="space-y-6">
        <!-- ==========================================
             SECCIÓN 1: KPIs PRINCIPALES
             ========================================== -->
        <section class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <!-- Total Viajes -->
            <div class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition group">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-lg">🚕</span>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Viajes</span>
                </div>
                <p class="text-3xl font-black text-gray-900">{{ stats.total_trips }}</p>
            </div>

            <!-- Completados -->
            <div class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition border-l-4 border-l-green-500">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-lg">✅</span>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Completados</span>
                </div>
                <p class="text-3xl font-black text-green-600">{{ stats.completed_trips }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ stats.completion_rate }}% tasa</p>
            </div>

            <!-- Cancelados -->
            <div class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition border-l-4 border-l-red-400">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-lg">❌</span>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Cancelados</span>
                </div>
                <p class="text-3xl font-black text-red-500">{{ stats.cancelled_trips }}</p>
            </div>

            <!-- Conductores -->
            <div class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-lg">🧑‍✈️</span>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Conductores</span>
                </div>
                <p class="text-3xl font-black text-gray-900">{{ stats.total_drivers }}</p>
                <p class="text-xs text-green-600 mt-1">{{ stats.approved_drivers }} aprobados</p>
            </div>

            <!-- Pasajeros -->
            <div class="bg-white rounded-xl shadow-sm border p-5 hover:shadow-md transition">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-lg">👥</span>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Pasajeros</span>
                </div>
                <p class="text-3xl font-black text-gray-900">{{ stats.total_passengers }}</p>
            </div>

            <!-- Ingresos -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl shadow-lg p-5 text-white hover:shadow-xl transition">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-lg">💰</span>
                    <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">Ingresos</span>
                </div>
                <p class="text-2xl font-black">${{ Number(stats.total_revenue).toLocaleString() }}</p>
                <p class="text-xs opacity-70 mt-1">Prom. ${{ stats.avg_trip_price }}/viaje</p>
            </div>
        </section>

        <!-- ==========================================
             SECCIÓN 1.5: ANÁLISIS DE MOTIVOS (Solicitud #4)
             ========================================== -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6" v-if="(stats.cancellation_reasons && stats.cancellation_reasons.length > 0) || (stats.rejection_reasons && Object.keys(stats.rejection_reasons).length > 0)">
            <!-- Motivos de Cancelación -->
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h3 class="font-bold text-gray-800 mb-4 flex item-center gap-2">❌ Motivos de Cancelación (Pasajeros)</h3>
                <div class="h-64 relative">
                    <Doughnut :data="cancellationChartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Motivos de Rechazo -->
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h3 class="font-bold text-gray-800 mb-4 flex item-center gap-2">⛔ Motivos de Rechazo (Conductores)</h3>
                <div class="h-64 relative">
                    <Doughnut :data="rejectionChartData" :options="chartOptions" />
                </div>
            </div>
        </section>

        <!-- ==========================================
             SECCIÓN 2: ACCESOS RÁPIDOS
             ========================================== -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Verificaciones Pendientes -->
            <div class="bg-yellow-50 p-5 rounded-xl border border-yellow-200 shadow-sm flex flex-col justify-between relative overflow-hidden">
                <div class="relative z-10">
                    <h4 class="font-bold text-yellow-900 text-lg">📁 Verificaciones</h4>
                    <p class="text-sm text-yellow-700 mt-1">
                        <strong>{{ stats.pending_verifications }}</strong> solicitudes pendientes
                    </p>
                </div>
                <a :href="route('admin.verifications')" class="mt-3 inline-block text-center bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg shadow hover:bg-yellow-500 transition relative z-10">
                    Ir a Solicitudes 👉
                </a>
                <div class="absolute -right-4 -bottom-4 text-8xl opacity-10">📄</div>
            </div>

            <!-- Analíticas Completas -->
            <div class="bg-indigo-50 p-5 rounded-xl border border-indigo-200 shadow-sm flex flex-col justify-between relative overflow-hidden">
                <div class="relative z-10">
                    <h4 class="font-bold text-indigo-900 text-lg">📊 Analíticas</h4>
                    <p class="text-sm text-indigo-700 mt-1">Gráficas, tendencias y datos profundos</p>
                </div>
                <a :href="route('admin.analytics')" class="mt-3 inline-block text-center bg-indigo-500 text-white font-bold py-2 px-4 rounded-lg shadow hover:bg-indigo-600 transition relative z-10">
                    Ver Dashboard Completo 📈
                </a>
                <div class="absolute -right-4 -bottom-4 text-8xl opacity-10">📊</div>
            </div>

            <!-- Viajes Activos -->
            <div class="bg-blue-50 p-5 rounded-xl border border-blue-200 shadow-sm flex flex-col justify-between relative overflow-hidden">
                <div class="relative z-10">
                    <h4 class="font-bold text-blue-900 text-lg">🚀 En Tiempo Real</h4>
                    <p class="text-sm text-blue-700 mt-1">
                        <strong>{{ stats.active_trips }}</strong> viajes activos ahora
                    </p>
                </div>
                <div class="mt-3 flex items-center gap-2 relative z-10">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                    </span>
                    <span class="text-xs text-blue-600 font-medium">Actualización automática cada 15s</span>
                </div>
                <div class="absolute -right-4 -bottom-4 text-8xl opacity-10">🚀</div>
            </div>
        </section>

        <!-- ==========================================
             SECCIÓN 3: MAPA DE CALOR
             ========================================== -->
        <section class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="p-5 border-b flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-gray-800 flex items-center gap-2">
                        🔥 Mapa de Calor — Actividad de Viajes
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">Orígenes y destinos de viajes de los últimos 30 días + conductores en línea</p>
                </div>
            </div>
            
            <div class="h-96 relative bg-gray-100">
                <l-map v-if="mapReady" ref="mapRef" v-model:zoom="zoom" :center="center" :use-global-leaflet="false" @ready="onMapReady" class="h-full w-full z-0">
                    <l-tile-layer url="https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png"></l-tile-layer>
                </l-map>
                
                <div v-if="driverLocations.length === 0" class="absolute inset-0 flex items-center justify-center pointer-events-none z-[1000]">
                    <div class="bg-white/90 backdrop-blur px-6 py-4 rounded-xl shadow-lg text-center">
                        <div class="text-3xl mb-2">🗺️</div>
                        <p class="text-sm text-gray-600 font-medium">Sin datos de ubicación</p>
                        <p class="text-xs text-gray-400 mt-1">Los puntos aparecerán cuando haya viajes con coordenadas</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             SECCIÓN 4: ÚLTIMOS VIAJES
             ========================================== -->
        <section class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="p-5 border-b">
                <h3 class="font-bold text-gray-800">📋 Últimos Viajes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">ID</th>
                            <th class="px-4 py-3 text-left">Pasajero</th>
                            <th class="px-4 py-3 text-left">Conductor</th>
                            <th class="px-4 py-3 text-left">Estado</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                            <th class="px-4 py-3 text-left">Pago</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in trips.slice(0, 10)" :key="t.id" class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-bold text-gray-900">#{{ t.id }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ t.passenger?.name || 'Anon' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ t.driver?.name || '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-bold" :class="statusColor(t.status)">
                                    {{ statusLabel(t.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="t.vehicle_type === 'moto'">🏍️</span>
                                <span v-else>🚗</span>
                                {{ t.vehicle_type }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-gray-900">${{ t.price }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ t.payment_method || '—' }}</td>
                            <td class="px-4 py-3 text-gray-400 text-xs">{{ new Date(t.created_at).toLocaleDateString() }}</td>
                        </tr>
                        <tr v-if="!trips.length">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">Sin viajes registrados</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
