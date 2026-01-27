<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

// IMPORTS DEL MAPA
import { LMap, LTileLayer, LMarker, LPolyline, LPopup } from "@vue-leaflet/vue-leaflet";

const props = defineProps({
    availableTrips: { type: Array, default: () => [] },
    myTrips: { type: Array, default: () => [] },
    pendingDrivers: { type: Array, default: () => [] },
    trips: { type: Array, default: () => [] }, // Historial del pasajero
    userRole: String,
    isApproved: { type: Boolean, default: false } 
});

const page = usePage();
const currentUserRole = props.userRole || page.props.auth.user.role;

// --- CONFIGURACIÓN DEL MAPA ---
const zoom = ref(14); 
const center = ref([10.2443, -66.8622]); // Charallave
const map = ref(null);

// --- 🔥 CORRECCIÓN DEL BUG "ATRAPADO" ---
// Buscamos solo si existe un viaje "Activo" (Pendiente, Aceptado o En Progreso).
// Si el último viaje fue "cancelado" o "completado", currentTrip será undefined y mostrará el formulario.
const currentTrip = props.trips.find(t => ['pending', 'accepted', 'in_progress'].includes(t.status));

const form = useForm({
    origin: '',
    destination: '',
    payment_method: 'Efectivo',
    origin_lat: null, 
    origin_lng: null,
    destination_lat: null,
    destination_lng: null,
});

// --- VARIABLES PARA EL AUTOCOMPLETADO ---
const addressSuggestions = ref([]); // Lista de sugerencias
const showSuggestions = ref(null); // 'origin' o 'destination' o null
const searchTimeout = ref(null); // Para no saturar la API

// --- 🔥 1. FUNCIÓN: GPS CON DIRECCIÓN TEXTUAL (Reverse Geocoding) ---
const locating = ref(false);

const getCurrentLocation = () => {
    if (!navigator.geolocation) {
        alert("Tu navegador no soporta geolocalización");
        return;
    }

    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        async (position) => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            form.origin_lat = lat;
            form.origin_lng = lng;
            form.origin = "Buscando dirección exacta..."; // Placeholder temporal
            
            center.value = [lat, lng];
            zoom.value = 16;

            // CONSULTA A LA API: ¿Qué dirección es esta?
            try {
                const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;
                const response = await fetch(url);
                const data = await response.json();
                
                // Si la API responde, ponemos la dirección real
                if (data && data.display_name) {
                    // Cortamos la dirección para que no sea ETERNA (tomamos las primeras 3 partes)
                    const shortAddress = data.display_name.split(',').slice(0, 3).join(',');
                    form.origin = "📍 " + shortAddress;
                } else {
                    form.origin = "📍 Ubicación detectada (Coordenadas)";
                }
            } catch (e) {
                form.origin = "📍 Mi Ubicación Actual";
            } finally {
                locating.value = false;
            }
        },
        (error) => {
            alert("Error al obtener ubicación. Revisa permisos.");
            locating.value = false;
        }
    );
};

// --- 🔥 2. FUNCIÓN: AUTOCOMPLETADO (Menú Desplegable) ---
const handleInput = (type) => {
    // Limpiamos timeout anterior
    if (searchTimeout.value) clearTimeout(searchTimeout.value);

    const query = type === 'origin' ? form.origin : form.destination;

    // Si borró todo, ocultamos menú
    if (query.length < 3) {
        addressSuggestions.value = [];
        showSuggestions.value = null;
        return;
    }

    // Esperamos 500ms a que termine de escribir antes de llamar a la API
    searchTimeout.value = setTimeout(async () => {
        try {
            // Buscamos en Venezuela (countrycodes=ve) y pedimos 5 resultados
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ve&limit=5&addressdetails=1`;
            const response = await fetch(url);
            const data = await response.json();
            
            addressSuggestions.value = data;
            showSuggestions.value = type; // Mostramos el menú para este input
        } catch (error) {
            console.error("Error buscando:", error);
        }
    }, 500);
};

const selectSuggestion = (suggestion, type) => {
    const lat = parseFloat(suggestion.lat);
    const lng = parseFloat(suggestion.lon);
    
    // Formateamos nombre (Ej: "Terminal Charallave, Miranda")
    const name = suggestion.display_name.split(',').slice(0, 3).join(',');

    if (type === 'origin') {
        form.origin = name;
        form.origin_lat = lat;
        form.origin_lng = lng;
        center.value = [lat, lng];
        zoom.value = 15;
    } else {
        form.destination = name;
        form.destination_lat = lat;
        form.destination_lng = lng;
        // Si ya hay origen, intentamos centrar el mapa para ver ambos (opcional)
        if(!form.origin_lat) center.value = [lat, lng];
    }

    // Ocultamos menú y limpiamos
    showSuggestions.value = null;
    addressSuggestions.value = [];
};

const submitTrip = () => {
    if (!form.origin_lat || !form.destination_lat) {
        alert("⚠️ Por favor selecciona una dirección de la lista o usa el GPS para que el mapa funcione correctamente.");
        return;
    }
    form.post(route('trips.store'), {
        onSuccess: () => form.reset(),
    });
};

const cancelTrip = (id) => {
    if(confirm('¿Seguro que deseas cancelar?')) {
        router.delete(route('trip.cancel', id));
    }
};

const statusColor = (status) => {
    if (status === 'pending') return 'bg-yellow-100 text-yellow-800';
    if (status === 'accepted') return 'bg-blue-100 text-blue-800';
    if (status === 'in_progress') return 'bg-purple-100 text-purple-800';
    return 'bg-green-100 text-green-800';
};

// --- MODAL Y OTROS ---
const showPaymentModal = ref(false);
const completedTrip = ref(null);
const closePaymentModal = () => { showPaymentModal.value = false; completedTrip.value = null; };

const acceptTrip = (id) => { if(confirm('¿Aceptar viaje?')) router.put(route('trip.accept', id)); };
const startTrip = (id) => { if(confirm('¿Iniciar?')) router.put(route('trips.start', id)); };
const finishTrip = (trip) => { if(confirm('¿Finalizar?')) router.put(route('trips.finish', trip.id), {}, { onSuccess: () => { completedTrip.value = trip; showPaymentModal.value = true; } }); };
const approveDriver = (id) => { if(confirm('¿Aprobar?')) router.put(route('admin.approve', id)); };
const rejectDriver = (id) => { if(confirm('¿Rechazar?')) router.delete(route('admin.reject', id)); };
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                <span v-if="currentUserRole === 'admin'">👮‍♂️ Panel de Administración</span>
                <span v-else-if="currentUserRole === 'driver'">🚖 Panel de Conductor</span>
                <span v-else>👋 Panel de Pasajero</span>
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="currentUserRole === 'admin'" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-bold text-lg mb-4">👮‍♂️ Solicitudes</h3>
                    <p class="text-gray-500">Panel administrativo funcional.</p>
                     <table v-if="pendingDrivers.length > 0" class="min-w-full mt-4">
                        <tbody class="bg-white"><tr v-for="driver in pendingDrivers" :key="driver.id"><td class="border px-4 py-2">{{driver.name}}</td><td class="border px-4 py-2"><button @click="approveDriver(driver.id)" class="text-green-600">✔</button></td></tr></tbody>
                    </table>
                </div>

                <div v-else-if="currentUserRole === 'driver'" class="space-y-8">
                     <div v-if="!isApproved" class="bg-yellow-100 p-4 rounded">Cuenta en revisión</div>
                     <div v-else>
                        <div class="bg-green-100 p-4 rounded mb-4">Conductor Activo ✅</div>
                        <div v-if="availableTrips.length > 0">
                            <div v-for="trip in availableTrips" :key="trip.id" class="bg-white p-4 shadow rounded mb-2 flex justify-between">
                                <span>{{ trip.origin }} -> {{ trip.destination }} (${{ trip.price }})</span>
                                <button @click="acceptTrip(trip.id)" class="bg-blue-500 text-white px-4 py-2 rounded">Aceptar</button>
                            </div>
                        </div>
                        <div v-else class="text-center p-10 bg-white rounded">📡 Escaneando...</div>
                     </div>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1 space-y-6">
                        
                        <div v-if="!currentTrip" class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 relative z-50">
                            <h2 class="text-2xl font-bold mb-4 text-gray-800">Pedir un viaje</h2>
                            
                            <form @submit.prevent="submitTrip" class="space-y-4">
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700">📍 Origen</label>
                                    <div class="flex gap-2">
                                        <input 
                                            v-model="form.origin" 
                                            @input="handleInput('origin')"
                                            type="text" 
                                            placeholder="Ej. Terminal Charallave" 
                                            class="w-full rounded-lg border-gray-300 mt-1 p-2 focus:ring-black focus:border-black" 
                                            autocomplete="off"
                                            required
                                        >
                                        <button type="button" @click="getCurrentLocation" class="mt-1 bg-blue-100 hover:bg-blue-200 text-blue-600 p-2 rounded-lg transition" title="Usar mi ubicación">
                                            {{ locating ? '⏳' : '🎯' }}
                                        </button>
                                    </div>
                                    
                                    <ul v-if="showSuggestions === 'origin' && addressSuggestions.length > 0" class="absolute left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto z-50">
                                        <li v-for="item in addressSuggestions" :key="item.place_id" 
                                            @click="selectSuggestion(item, 'origin')"
                                            class="p-3 hover:bg-gray-100 cursor-pointer text-sm border-b last:border-b-0 flex items-center gap-2">
                                            <span>📍</span> {{ item.display_name }}
                                        </li>
                                    </ul>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700">🏁 Destino</label>
                                    <input 
                                        v-model="form.destination" 
                                        @input="handleInput('destination')"
                                        type="text" 
                                        placeholder="Ej. Ocumare del Tuy" 
                                        class="w-full rounded-lg border-gray-300 mt-1 p-2 focus:ring-black focus:border-black" 
                                        autocomplete="off"
                                        required
                                    >
                                    
                                    <ul v-if="showSuggestions === 'destination' && addressSuggestions.length > 0" class="absolute left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-48 overflow-y-auto z-50">
                                        <li v-for="item in addressSuggestions" :key="item.place_id" 
                                            @click="selectSuggestion(item, 'destination')"
                                            class="p-3 hover:bg-gray-100 cursor-pointer text-sm border-b last:border-b-0 flex items-center gap-2">
                                            <span>🏁</span> {{ item.display_name }}
                                        </li>
                                    </ul>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">💳 Método de Pago</label>
                                    <select v-model="form.payment_method" class="w-full rounded-lg border-gray-300 mt-1 p-2">
                                        <option value="Efectivo">💵 Efectivo</option>
                                        <option value="Pago Móvil">📱 Pago Móvil</option>
                                        <option value="Tarjeta">💳 Tarjeta</option>
                                    </select>
                                </div>

                                <button type="submit" :disabled="form.processing" class="w-full bg-black text-white font-bold py-3 rounded-xl hover:bg-gray-800 transition shadow-lg transform active:scale-95">
                                    {{ form.processing ? 'Calculando...' : 'Confirmar Viaje 🚕' }}
                                </button>
                            </form>
                        </div>

                        <div v-else class="bg-white rounded-2xl p-6 shadow-xl border-l-4 border-blue-500 relative overflow-hidden">
                             <div class="absolute -right-10 -top-10 w-40 h-40 bg-blue-50 rounded-full opacity-50"></div>
                             <div class="relative z-10">
                                <div class="flex justify-between items-center mb-4"><h2 class="text-xl font-bold text-gray-800">Viaje en Curso</h2><span class="px-3 py-1 rounded-full text-xs font-bold uppercase" :class="statusColor(currentTrip.status)">{{ currentTrip.status }}</span></div>
                                <div class="space-y-4 mb-6">
                                    <div class="flex items-start gap-3"><div class="w-3 h-3 bg-blue-500 rounded-full mt-1.5"></div><div><p class="text-xs text-gray-400">Desde</p><p class="font-bold text-gray-800">{{ currentTrip.origin }}</p></div></div>
                                    <div class="flex items-start gap-3"><div class="w-3 h-3 bg-red-500 rounded-full mt-1.5"></div><div><p class="text-xs text-gray-400">Hasta</p><p class="font-bold text-gray-800">{{ currentTrip.destination }}</p></div></div>
                                </div>
                                <hr class="border-gray-100 my-4">
                                <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                    <div><p class="text-xs text-gray-500">Total a Pagar</p><p class="text-2xl font-black text-gray-900">${{ currentTrip.price }}</p></div>
                                    <div class="text-right"><p class="text-xs text-gray-500">Método</p><p class="font-bold text-gray-700">{{ currentTrip.payment_method }}</p></div>
                                </div>
                                <button v-if="currentTrip.status === 'pending'" @click="cancelTrip(currentTrip.id)" class="w-full mt-4 text-red-500 text-sm font-bold hover:underline">Cancelar solicitud</button>
                             </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 h-full flex flex-col min-h-[500px]">
                            <h3 class="font-bold text-gray-800 mb-4 flex justify-between">
                                <span>🗺️ Ruta en vivo (Valles del Tuy)</span>
                                <span v-if="form.origin_lat" class="text-green-600 text-sm">📍 Punto detectado</span>
                            </h3>
                            
                            <div class="flex-1 rounded-xl overflow-hidden border border-gray-200 relative z-0">
                                 <l-map ref="map" v-model:zoom="zoom" :center="center" :use-global-leaflet="false" style="height: 100%; width: 100%;">
                                    <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" layer-type="base" name="OpenStreetMap"></l-tile-layer>

                                    <l-marker v-if="form.origin_lat" :lat-lng="[form.origin_lat, form.origin_lng]"></l-marker>
                                    <l-marker v-if="form.destination_lat" :lat-lng="[form.destination_lat, form.destination_lng]"></l-marker>

                                    <div v-if="currentTrip">
                                        <l-marker v-if="currentTrip.origin_lat" :lat-lng="[currentTrip.origin_lat, currentTrip.origin_lng]"></l-marker>
                                        <l-marker v-if="currentTrip.destination_lat" :lat-lng="[currentTrip.destination_lat, currentTrip.destination_lng]"></l-marker>
                                        <l-polyline v-if="currentTrip.origin_lat && currentTrip.destination_lat" 
                                            :lat-lngs="[[currentTrip.origin_lat, currentTrip.origin_lng], [currentTrip.destination_lat, currentTrip.destination_lng]]" 
                                            color="#3B82F6" :weight="5">
                                        </l-polyline>
                                    </div>
                                 </l-map>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden relative transform transition-all scale-100">
                <div class="bg-green-500 p-6 text-center text-white"><span class="text-3xl">💵</span><h3 class="text-2xl font-bold mt-2">¡Viaje Finalizado!</h3></div>
                <div class="p-8 text-center space-y-6">
                    <div><p class="text-gray-500 uppercase text-xs font-bold tracking-wider">Total a Pagar</p><p class="text-5xl font-extrabold text-gray-900 mt-2">${{ completedTrip?.price }}</p></div>
                    <button @click="closePaymentModal" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-4 rounded-xl shadow-lg">✅ Confirmar Pago</button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>