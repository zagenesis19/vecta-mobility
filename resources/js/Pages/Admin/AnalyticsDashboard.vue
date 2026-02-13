<script setup>
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';
import { Bar, Pie } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ArcElement);

const stats = ref(null);
const loading = ref(true);

// Chart Data Configs
const marketChartData = ref({ labels: [], datasets: [] });
const marketChartOptions = { responsive: true, maintainAspectRatio: false };

const fetchStats = async (range = '30_days') => {
    loading.value = true;
    try {
        const response = await axios.get(route('admin.analytics.stats'), { params: { range } });
        stats.value = response.data;
        
        // Prepare Market Chart
        const labels = stats.value.market.map(m => m.vehicle_type === 'moto' ? 'Moto 🏍️' : 'Carro 🚗');
        const data = stats.value.market.map(m => m.total);
        const colors = ['#f59e0b', '#3b82f6']; // Orange (Moto), Blue (Car)
        
        marketChartData.value = {
            labels,
            datasets: [{ 
                label: 'Viajes por Vehículo', 
                backgroundColor: colors, 
                data 
            }]
        };

    } catch (error) {
        console.error("Error fetching analytics", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchStats();
    // Refresh Live Feed every 10s
    setInterval(() => fetchStats(), 10000); 
});
</script>

<template>
    <Head title="Analytics Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">📊 Centro de Comando Vecta</h2>
                <div class="flex gap-2">
                    <button @click="fetchStats('today')" class="px-3 py-1 text-sm bg-white border rounded hover:bg-gray-50">Hoy</button>
                    <button @click="fetchStats('7_days')" class="px-3 py-1 text-sm bg-white border rounded hover:bg-gray-50">7 Días</button>
                    <button @click="fetchStats('30_days')" class="px-3 py-1 text-sm bg-indigo-600 text-white border border-indigo-600 rounded">30 Días</button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div v-if="loading && !stats" class="max-w-7xl mx-auto px-4 text-center py-20">
                <div class="animate-spin h-10 w-10 border-4 border-indigo-500 border-t-transparent rounded-full mx-auto"></div>
                <p class="mt-4 text-gray-500">Analizando millones de datos...</p>
            </div>

            <div v-else class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- 1. OPERATIONAL HEALTH ❤️ -->
                <section>
                    <h3 class="text-lg font-bold text-gray-700 mb-3 px-1">Salud Operativa</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4" :class="stats.operational.completion_rate > 80 ? 'border-green-500' : 'border-red-500'">
                            <div class="text-gray-500 text-sm font-medium">Tasa Finalización</div>
                            <div class="text-3xl font-bold text-gray-900">{{ stats.operational.completion_rate }}%</div>
                            <div class="text-xs mt-1 text-gray-400">Meta: >80%</div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-yellow-500">
                            <div class="text-gray-500 text-sm font-medium">Cancelación</div>
                            <div class="text-3xl font-bold text-gray-900">{{ stats.operational.cancellation_rate }}%</div>
                            <div class="text-xs mt-1 text-gray-400">Total Viajes: {{ stats.operational.total_trips }}</div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                            <div class="text-gray-500 text-sm font-medium">Tiempo Espera (Promedio)</div>
                            <div class="text-3xl font-bold text-gray-900">{{ stats.operational.avg_wait_time }} <span class="text-sm text-gray-500">min</span></div>
                        </div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                            <div class="text-gray-500 text-sm font-medium">Usuarios Activos (Mes)</div>
                            <div class="text-3xl font-bold text-gray-900">{{ stats.growth.active_users }}</div>
                            <div class="text-xs mt-1 text-green-600">↑ Creciendo</div>
                        </div>
                    </div>
                </section>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- 2. MARKET SPLIT 🏍️🚗 -->
                    <div class="bg-white p-6 rounded-lg shadow sm:col-span-2">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">Demanda: Moto vs Carro</h3>
                        <div class="h-64">
                            <!-- Using conditional rendering to ensure data exists -->
                            <Bar v-if="marketChartData.datasets.length" :data="marketChartData" :options="marketChartOptions" />
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                            <div v-for="item in stats.market" :key="item.vehicle_type" class="p-3 bg-gray-50 rounded">
                                <div class="text-sm text-gray-500">Ticket Promedio ({{ item.vehicle_type }})</div>
                                <div class="font-bold text-indigo-600">${{ parseFloat(item.avg_ticket).toFixed(2) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. FINANCIAL SNAPSHOT 💰 -->
                    <div class="bg-white p-6 rounded-lg shadow space-y-6">
                        <h3 class="text-lg font-bold text-gray-700 mb-2">Finanzas</h3>
                        
                        <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                            <div class="text-green-800 font-medium">GMV (Ventas Brutas)</div>
                            <div class="text-3xl font-black text-green-900">${{ stats.financial.gmv.toLocaleString() }}</div>
                        </div>
                        
                        <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                            <div class="text-indigo-800 font-medium">Revenue (Tu 10%)</div>
                            <div class="text-3xl font-black text-indigo-900">${{ stats.financial.revenue.toLocaleString() }}</div>
                        </div>

                        <div class="text-xs text-gray-400">
                            * Calculado en base a viajes completados en el periodo seleccionado.
                        </div>
                    </div>
                </div>

                <!-- 5. LIVE FEED 🔴 -->
                <section class="bg-gray-900 rounded-xl p-6 text-white shadow-xl">
                    <h3 class="flex items-center gap-2 font-bold mb-4">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        </span>
                        Actividad en Tiempo Real
                    </h3>
                    <div class="space-y-3">
                        <div v-for="event in stats.live_feed" :key="event.id" class="flex items-center gap-3 text-sm p-2 hover:bg-gray-800 rounded transition">
                            <div class="text-gray-400 font-mono">{{ new Date(event.created_at).toLocaleTimeString() }}</div>
                            <div>
                                <span class="font-bold text-indigo-400">{{ event.event_type }}</span>
                                <span class="text-gray-300 ml-2" v-if="event.target">en {{ event.target }}</span>
                                <span class="text-gray-500 ml-2 text-xs" v-if="event.user_id">Usuario #{{ event.user_id }}</span>
                            </div>
                        </div>
                        <div v-if="stats.live_feed.length === 0" class="text-gray-500 italic">Esperando actividad...</div>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
