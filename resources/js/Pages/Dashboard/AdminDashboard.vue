<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import "leaflet/dist/leaflet.css";
import L from "leaflet"; 
import "leaflet.heat";   
import { LMap, LTileLayer } from "@vue-leaflet/vue-leaflet";

const props = defineProps({
    trips: { type: Array, default: () => [] },
    driverLocations: { type: Array, default: () => [] } 
});

// --- MAPA ---
const zoom = ref(14); 
const center = ref([10.2443, -66.8622]); 
const mapReady = ref(false); 
const mapRef = ref(null); 

// --- SIMULACIÓN ---
const isSimulating = ref(false);

const onMapReady = (mapObject) => {
    console.log("🗺️ Mapa Admin listo.");
    if (props.driverLocations.length > 0) {
        L.heatLayer(props.driverLocations, { radius: 45, blur: 25, maxZoom: 14 }).addTo(mapObject);
        center.value = props.driverLocations[0];
    }
};

const runSimulation = async () => {
    if(!confirm("¿Crear 300 conductores falsos?")) return;
    isSimulating.value = true;
    try {
        await axios.get('/simular-conductores');
        window.location.reload(); 
    } catch (error) { alert("Error simulación"); } finally { isSimulating.value = false; }
};

const clearSimulation = async () => {
    if(!confirm("¿Borrar fantasmas?")) return;
    isSimulating.value = true;
    try {
        await axios.get('/limpiar-simulacion');
        window.location.reload();
    } catch (error) { alert("Error limpieza"); } finally { isSimulating.value = false; }
};

onMounted(() => {
    setTimeout(() => { mapReady.value = true; }, 100);
});
</script>

<template>
    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-lg">Resumen Global</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-blue-50 p-6 rounded-xl border border-blue-100 shadow-sm">
                    <h4 class="font-bold text-blue-800 text-sm uppercase">Total Viajes</h4>
                    <p class="text-3xl font-black text-gray-800 mt-2">{{ trips.length }}</p>
                </div>
                <div class="bg-green-50 p-6 rounded-xl border border-green-100 shadow-sm">
                    <h4 class="font-bold text-green-800 text-sm uppercase">Viajes Activos</h4>
                    <p class="text-3xl font-black text-gray-800 mt-2">{{ trips.filter(t => t.status === 'in_progress').length }}</p>
                </div>
                
                <div class="bg-yellow-50 p-6 rounded-xl border border-yellow-200 shadow-sm flex flex-col justify-between relative overflow-hidden">
                    <div class="relative z-10">
                        <h4 class="font-bold text-yellow-900 text-lg">📁 Verificaciones</h4>
                        <p class="text-sm text-yellow-700 mt-1">Aprobar conductores e identidad</p>
                    </div>
                    <a :href="route('admin.verifications')" class="mt-4 inline-block text-center bg-yellow-400 text-yellow-900 font-bold py-2 px-4 rounded-lg shadow hover:bg-yellow-500 transition relative z-10">
                        Ir a Solicitudes 👉
                    </a>
                    <div class="absolute -right-4 -bottom-4 text-8xl opacity-10">📄</div>
                </div>
            </div>

            <div class="mb-8">
                <div class="flex justify-between items-end mb-2">
                    <h4 class="font-bold text-gray-700 flex items-center gap-2">🔥 Mapa de Calor (Conductores)</h4>
                    <div class="flex gap-2">
                        <button @click="runSimulation" :disabled="isSimulating" class="text-xs bg-green-600 hover:bg-green-700 text-white font-bold py-1 px-3 rounded shadow transition">
                            ⚡ Simular Tráfico
                        </button>
                        <button @click="clearSimulation" :disabled="isSimulating" class="text-xs bg-gray-400 hover:bg-gray-500 text-white font-bold py-1 px-3 rounded shadow transition">
                            🧹 Limpiar
                        </button>
                    </div>
                </div>
                
                <div class="h-80 rounded-xl border overflow-hidden shadow-inner bg-gray-100 relative">
                    <l-map v-if="mapReady" ref="mapRef" v-model:zoom="zoom" :center="center" :use-global-leaflet="false" @ready="onMapReady" class="h-full w-full z-0">
                        <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"></l-tile-layer>
                    </l-map>
                    
                    <div v-if="driverLocations.length === 0" class="absolute inset-0 flex items-center justify-center pointer-events-none z-[1000]">
                        <div class="bg-white/80 backdrop-blur px-4 py-2 rounded-lg shadow text-xs text-gray-500 font-bold">
                            Sin datos. Pulsa "Simular Tráfico" ↗️
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1 text-center">Zonas rojas indican alta concentración de conductores en línea.</p>
            </div>

            <h4 class="font-bold text-gray-700 mb-4">Últimos movimientos</h4>
            <div class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="bg-gray-50 text-gray-700 uppercase">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Pasajero</th>
                            <th class="px-4 py-3">Conductor</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in trips.slice(0, 5)" :key="t.id" class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-bold">#{{ t.id }}</td>
                            <td class="px-4 py-3">{{ t.passenger?.name || 'Anon' }}</td>
                            <td class="px-4 py-3">{{ t.driver?.name || '-' }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 rounded text-xs font-bold uppercase" :class="{'bg-green-100 text-green-800': t.status==='completed','bg-blue-100 text-blue-800': t.status==='in_progress'}">{{ t.status }}</span></td>
                            <td class="px-4 py-3 font-mono text-gray-900 font-bold">${{ t.price }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
