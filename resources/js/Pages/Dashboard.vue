<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import VueApexCharts from "vue3-apexcharts";
import { onMounted } from 'vue'; // Importante para cargar el mapa

// Recibimos los datos
const props = defineProps({
    trips: Array,
    stats: Object,
    isAdmin: Boolean,
    heatmapData: Array // Coordenadas para el mapa
});

// Configuración Gráfico Dona
const chartOptions = {
    labels: ['Completados', 'Cancelados', 'Activos'],
    colors: ['#10B981', '#EF4444', '#3B82F6'],
    legend: { position: 'bottom' },
    plotOptions: { pie: { donut: { size: '65%' } } }
};
const chartSeries = props.stats ? [props.stats.completed, props.stats.cancelled, props.stats.active] : [];

// FUNCIÓN PARA DIBUJAR EL MAPA
onMounted(() => {
    if (props.isAdmin && document.getElementById('map')) {
        // Importamos las librerías de mapa dinámicamente
        const L = window.L;
        if (!L) return; // Si no cargó Leaflet, salir

        // 1. Crear Mapa centrado en Charallave
        var map = L.map('map').setView([10.2460, -66.8620], 13);

        // 2. Cargar capa visual (OpenStreetMap - Gratis)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // 3. Dibujar Puntos de Calor (Si hay datos)
        if (props.heatmapData.length > 0) {
            // Usamos un plugin simple de calor (simulado con círculos por ahora para no complicar dependencias)
            props.heatmapData.forEach(coord => {
                L.circle(coord, {
                    color: 'red',
                    fillColor: '#f03',
                    fillOpacity: 0.5,
                    radius: 150 // Radio en metros
                }).addTo(map);
            });
        }
    }
});
</script>

<template>
    <Head>
        <title>Dashboard</title>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <component :is="'script'" src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" async></component>
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ isAdmin ? 'Centro de Operaciones' : 'Mis Viajes' }}
                </h2>
                <Link :href="route('trip.create')" class="bg-black text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-gray-800 transition">
                    + Nuevo Viaje
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div v-if="isAdmin && stats" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-green-500">
                        <div class="text-xs text-gray-500 uppercase font-bold">Ingresos</div>
                        <div class="text-2xl font-bold text-gray-800">${{ stats.earnings }}</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
                        <div class="text-xs text-gray-500 uppercase font-bold">Activos</div>
                        <div class="text-2xl font-bold text-gray-800">{{ stats.active }}</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-red-500">
                        <div class="text-xs text-gray-500 uppercase font-bold">Cancelados</div>
                        <div class="text-2xl font-bold text-gray-800">{{ stats.cancelled }}</div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow border-l-4 border-purple-500">
                        <div class="text-xs text-gray-500 uppercase font-bold">Total Viajes</div>
                        <div class="text-2xl font-bold text-gray-800">{{ props.trips.length + 20 }}</div>
                    </div>
                </div>

                <div v-if="isAdmin" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden relative h-80 z-0">
                        <div class="absolute top-2 left-2 z-10 bg-white/90 px-2 py-1 rounded text-xs font-bold shadow">
                            🗺️ Zonas de Alta Demanda (Charallave)
                        </div>
                        <div id="map" class="h-full w-full z-0"></div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4 flex flex-col justify-center items-center">
                        <h3 class="text-sm font-bold text-gray-500 mb-2">Efectividad</h3>
                        <VueApexCharts width="100%" type="donut" :options="chartOptions" :series="chartSeries" />
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                        <h3 class="font-bold text-gray-700">Bitácora de Viajes</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">{{ isAdmin ? 'Cliente' : 'Conductor' }}</th>
                                    <th class="px-4 py-3">Distancia</th>
                                    <th class="px-4 py-3">Precio</th>
                                    <th class="px-4 py-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="trip in trips" :key="trip.id" class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3">#{{ trip.id }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ isAdmin ? (trip.passenger?.name || 'Anon') : (trip.driver?.name || 'Asignando...') }}
                                    </td>
                                    <td class="px-4 py-3">{{ trip.distance }} Km</td>
                                    <td class="px-4 py-3 font-bold text-green-600">${{ trip.fare }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="trip.status === 'completed'" class="text-green-600 font-bold text-xs">● Completado</span>
                                        <span v-else-if="trip.status === 'active'" class="text-blue-600 font-bold text-xs">● En Curso</span>
                                        <span v-else class="text-red-600 font-bold text-xs">● Cancelado</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>