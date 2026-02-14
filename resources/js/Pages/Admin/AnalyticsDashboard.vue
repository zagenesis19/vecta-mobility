<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { 
    Chart as ChartJS, 
    Title, Tooltip, Legend, 
    BarElement, CategoryScale, LinearScale, 
    ArcElement,
    PointElement, LineElement, Filler
} from 'chart.js';
import { Bar, Pie, Doughnut, Line } from 'vue-chartjs';

ChartJS.register(
    CategoryScale, LinearScale, BarElement, 
    Title, Tooltip, Legend, ArcElement,
    PointElement, LineElement, Filler
);

const stats = ref(null);
const loading = ref(true);
const activeRange = ref('30_days');
let pollInterval = null;

// Paleta Premium
const COLORS = {
    primary: '#6366f1',
    success: '#10b981',
    warning: '#f59e0b',
    danger: '#ef4444',
    info: '#3b82f6',
    purple: '#8b5cf6',
    pink: '#ec4899',
    teal: '#14b8a6',
    orange: '#f97316',
    slate: '#64748b',
};

const CHART_COLORS = [
    '#6366f1', '#10b981', '#f59e0b', '#ef4444', 
    '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'
];

// ==========================================
// CHART DATA CONFIGS
// ==========================================

// 1. Market (Moto vs Carro)
const marketChartData = computed(() => {
    if (!stats.value?.market?.length) return { labels: [], datasets: [] };
    return {
        labels: stats.value.market.map(m => m.vehicle_type === 'moto' ? '🏍️ Moto' : '🚗 Carro'),
        datasets: [{
            label: 'Viajes Completados',
            backgroundColor: ['#f59e0b', '#3b82f6'],
            borderWidth: 0,
            borderRadius: 8,
            data: stats.value.market.map(m => m.total)
        }]
    };
});

// 2. Registros por Día (Line)
const registrationChartData = computed(() => {
    if (!stats.value?.registrations_trend?.length) return { labels: [], datasets: [] };
    const trend = stats.value.registrations_trend;
    return {
        labels: trend.map(r => {
            const d = new Date(r.date);
            return `${d.getDate()}/${d.getMonth()+1}`;
        }),
        datasets: [
            {
                label: 'Conductores',
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 6,
                data: trend.map(r => r.drivers)
            },
            {
                label: 'Pasajeros',
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 6,
                data: trend.map(r => r.passengers)
            }
        ]
    };
});

// 3. Conductores por Municipio (Doughnut)
const municipalityChartData = computed(() => {
    if (!stats.value?.drivers_by_municipality?.length) return { labels: [], datasets: [] };
    const data = stats.value.drivers_by_municipality;
    return {
        labels: data.map(m => m.municipality),
        datasets: [{
            data: data.map(m => m.total),
            backgroundColor: CHART_COLORS.slice(0, data.length),
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 8
        }]
    };
});

// 4. Métodos de Pago (Doughnut)
const paymentChartData = computed(() => {
    if (!stats.value?.payment_methods?.length) return { labels: [], datasets: [] };
    const data = stats.value.payment_methods;
    const colorMap = {
        'Efectivo': '#10b981',
        'Pago Móvil': '#6366f1',
        'Tarjeta': '#f59e0b',
    };
    return {
        labels: data.map(p => p.payment_method),
        datasets: [{
            data: data.map(p => p.total),
            backgroundColor: data.map(p => colorMap[p.payment_method] || '#64748b'),
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 8
        }]
    };
});

// 5. Viajes por Día de Semana (Bar)
const weekdayChartData = computed(() => {
    if (!stats.value?.trips_by_weekday?.length) return { labels: [], datasets: [] };
    const data = stats.value.trips_by_weekday;
    return {
        labels: data.map(d => d.day_name),
        datasets: [{
            label: 'Viajes',
            backgroundColor: data.map((_, i) => CHART_COLORS[i % CHART_COLORS.length]),
            borderRadius: 6,
            borderWidth: 0,
            data: data.map(d => d.total)
        }]
    };
});

// 6. Distribución de Ratings (Bar horizontal)
const ratingsChartData = computed(() => {
    if (!stats.value?.ratings_distribution?.length) return { labels: [], datasets: [] };
    const data = stats.value.ratings_distribution;
    const starColors = { 1: '#ef4444', 2: '#f97316', 3: '#f59e0b', 4: '#10b981', 5: '#6366f1' };
    return {
        labels: data.map(r => '⭐'.repeat(r.stars)),
        datasets: [{
            label: 'Calificaciones',
            backgroundColor: data.map(r => starColors[r.stars] || '#64748b'),
            borderRadius: 6,
            borderWidth: 0,
            data: data.map(r => r.total)
        }]
    };
});

// 7. Flota (Doughnut)
const fleetChartData = computed(() => {
    if (!stats.value?.fleet?.by_type?.length) return { labels: [], datasets: [] };
    const data = stats.value.fleet.by_type;
    return {
        labels: data.map(f => f.type === 'motorcycle' ? '🏍️ Motos' : '🚗 Carros'),
        datasets: [{
            data: data.map(f => f.total),
            backgroundColor: ['#f59e0b', '#3b82f6'],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 8
        }]
    };
});

// Chart Options
const barOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } };
const lineOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } };
const doughnutOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle' } } }, cutout: '65%' };

// ==========================================
// FETCH
// ==========================================
const fetchStats = async (range = null) => {
    if (range) activeRange.value = range;
    loading.value = !stats.value; // solo spinner la primera vez
    try {
        const response = await axios.get(route('admin.analytics.stats'), { params: { range: activeRange.value } });
        stats.value = response.data;
    } catch (error) {
        console.error("Error fetching analytics", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchStats();
    pollInterval = setInterval(() => fetchStats(), 15000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <Head title="Analytics Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h2 class="font-bold text-xl text-gray-900 leading-tight">📊 Centro de Comando</h2>
                    <p class="text-sm text-gray-500 mt-0.5">Datos en tiempo real para decisiones inteligentes</p>
                </div>
                <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                    <button 
                        v-for="r in [{key:'today',label:'Hoy'},{key:'7_days',label:'7d'},{key:'30_days',label:'30d'}]" 
                        :key="r.key"
                        @click="fetchStats(r.key)" 
                        class="px-3 py-1.5 text-sm font-medium rounded-md transition-all"
                        :class="activeRange === r.key ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    >{{ r.label }}</button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <!-- Loading -->
            <div v-if="loading && !stats" class="max-w-7xl mx-auto px-4 text-center py-20">
                <div class="animate-spin h-10 w-10 border-4 border-indigo-500 border-t-transparent rounded-full mx-auto"></div>
                <p class="mt-4 text-gray-500">Analizando datos...</p>
            </div>

            <div v-else-if="stats" class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- ==========================================
                     ROW 1: KPIs PRINCIPALES
                     ========================================== -->
                <section class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border p-4 hover:shadow-md transition">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Conductores</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">{{ stats.growth.total_drivers }}</div>
                        <div class="text-xs text-green-600 mt-1">✅ {{ stats.growth.approved_drivers }} aprobados</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border p-4 hover:shadow-md transition">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Pasajeros</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">{{ stats.growth.total_passengers }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border p-4 hover:shadow-md transition">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Viajes (Período)</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">{{ stats.operational.total_trips }}</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border p-4 hover:shadow-md transition" :class="stats.operational.completion_rate > 80 ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-red-500'">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tasa Finalización</div>
                        <div class="text-2xl font-black" :class="stats.operational.completion_rate > 80 ? 'text-green-600' : 'text-red-600'">{{ stats.operational.completion_rate }}%</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border p-4 hover:shadow-md transition border-l-4 border-l-yellow-500">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Cancelación</div>
                        <div class="text-2xl font-black text-yellow-600">{{ stats.operational.cancellation_rate }}%</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border p-4 hover:shadow-md transition">
                        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Verificaciones</div>
                        <div class="text-2xl font-black text-orange-600">{{ stats.growth.pending_verifications }}</div>
                        <div class="text-xs text-gray-400 mt-1">pendientes</div>
                    </div>
                </section>

                <!-- ==========================================
                     ROW 2: FINANZAS + REGISTROS TREND
                     ========================================== -->
                <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Finanzas -->
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-700 p-6 rounded-2xl shadow-lg text-white">
                        <h3 class="text-sm font-medium opacity-80 uppercase tracking-wider mb-4">💰 Finanzas</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="text-xs opacity-70">GMV (Ventas Brutas)</div>
                                <div class="text-3xl font-black">${{ stats.financial.gmv.toLocaleString() }}</div>
                            </div>
                            <div class="border-t border-white/20 pt-3">
                                <div class="text-xs opacity-70">Revenue (Tu Comisión 10%)</div>
                                <div class="text-2xl font-bold text-green-300">${{ stats.financial.revenue.toLocaleString() }}</div>
                            </div>
                            <div class="border-t border-white/20 pt-3 flex items-center gap-2">
                                <div class="text-xs opacity-70">Espera Promedio:</div>
                                <div class="font-bold">{{ stats.operational.avg_wait_time }} min</div>
                            </div>
                        </div>
                    </div>

                    <!-- Registros Trend (Line Chart) -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border lg:col-span-2">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            📈 Crecimiento de Usuarios
                            <span class="text-xs font-normal text-gray-400">(últimos 30 días)</span>
                        </h3>
                        <div class="h-56">
                            <Line v-if="registrationChartData.datasets.length" :data="registrationChartData" :options="lineOptions" />
                            <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">Sin datos de registros recientes</div>
                        </div>
                    </div>
                </section>

                <!-- ==========================================
                     ROW 3: MUNICIPIOS + MERCADO + PAGOS
                     ========================================== -->
                <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Conductores por Municipio -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border">
                        <h3 class="font-bold text-gray-800 mb-4">🗺️ Conductores por Municipio</h3>
                        <div class="h-56">
                            <Doughnut v-if="municipalityChartData.datasets.length" :data="municipalityChartData" :options="doughnutOptions" />
                            <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">Sin datos de municipios</div>
                        </div>
                    </div>

                    <!-- Demanda Moto vs Carro -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border">
                        <h3 class="font-bold text-gray-800 mb-4">🏍️🚗 Demanda por Vehículo</h3>
                        <div class="h-44">
                            <Bar v-if="marketChartData.datasets.length" :data="marketChartData" :options="barOptions" />
                            <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">Sin viajes completados</div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                            <div v-for="item in stats.market" :key="item.vehicle_type" class="p-2 bg-gray-50 rounded-lg">
                                <div class="text-xs text-gray-500">Ticket Prom.</div>
                                <div class="font-bold text-sm text-indigo-600">${{ parseFloat(item.avg_ticket).toFixed(2) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Métodos de Pago -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border">
                        <h3 class="font-bold text-gray-800 mb-4">💳 Métodos de Pago</h3>
                        <div class="h-56">
                            <Doughnut v-if="paymentChartData.datasets.length" :data="paymentChartData" :options="doughnutOptions" />
                            <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">Sin datos de pagos</div>
                        </div>
                    </div>
                </section>

                <!-- ==========================================
                     ROW 4: VIAJES x DÍA + RATINGS + FLOTA
                     ========================================== -->
                <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Viajes por Día de Semana -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border">
                        <h3 class="font-bold text-gray-800 mb-4">📅 Demanda por Día</h3>
                        <div class="h-52">
                            <Bar v-if="weekdayChartData.datasets.length" :data="weekdayChartData" :options="barOptions" />
                            <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">Sin datos suficientes</div>
                        </div>
                    </div>

                    <!-- Distribución de Calificaciones -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border">
                        <h3 class="font-bold text-gray-800 mb-4">⭐ Distribución de Ratings</h3>
                        <div class="h-52">
                            <Bar v-if="ratingsChartData.datasets.length" :data="ratingsChartData" :options="barOptions" />
                            <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">Sin calificaciones aún</div>
                        </div>
                    </div>

                    <!-- Panorama de Flota -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border">
                        <h3 class="font-bold text-gray-800 mb-4">🚗 Tu Flota</h3>
                        <div class="h-40">
                            <Doughnut v-if="fleetChartData.datasets.length" :data="fleetChartData" :options="doughnutOptions" />
                            <div v-else class="h-full flex items-center justify-center text-gray-400 text-sm">Sin vehículos registrados</div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-center text-xs">
                            <div class="p-2 bg-gray-50 rounded-lg">
                                <div class="text-gray-500">Total Vehículos</div>
                                <div class="font-bold text-gray-900">{{ stats.fleet.total }}</div>
                            </div>
                            <div class="p-2 bg-gray-50 rounded-lg">
                                <div class="text-gray-500">Año Promedio</div>
                                <div class="font-bold text-gray-900">{{ stats.fleet.avg_year }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ==========================================
                     ROW 5: ACTIVIDAD EN VIVO
                     ========================================== -->
                <section class="bg-gray-900 rounded-2xl p-6 text-white shadow-xl">
                    <h3 class="flex items-center gap-2 font-bold mb-4">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        </span>
                        Actividad en Tiempo Real
                    </h3>
                    <div class="space-y-2">
                        <div v-for="event in stats.live_feed" :key="event.id" class="flex items-center gap-3 text-sm p-2 hover:bg-gray-800 rounded-lg transition">
                            <div class="text-gray-400 font-mono text-xs shrink-0">{{ new Date(event.created_at).toLocaleTimeString() }}</div>
                            <div>
                                <span class="font-bold text-indigo-400">{{ event.event_type }}</span>
                                <span class="text-gray-300 ml-2" v-if="event.target">en {{ event.target }}</span>
                                <span class="text-gray-500 ml-2 text-xs" v-if="event.user_id">Usuario #{{ event.user_id }}</span>
                            </div>
                        </div>
                        <div v-if="!stats.live_feed?.length" class="text-gray-500 italic text-center py-4">Esperando actividad...</div>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
