<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

// --- IMPORTAR LIBRERÍA DE MAPAS (LEAFLET) ---
import "leaflet/dist/leaflet.css";
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

// --- VARIABLES DEL MAPA ---
const zoom = ref(15);
const center = ref([10.2443, -66.8622]); // Default: Charallave
const userLocation = ref(null);
const destinationLocation = ref(null);
const mapReady = ref(false);

// --- VARIABLES DE UI ---
const searchResults = ref([]);
const isSearching = ref(false);
const hideCompletedTrip = ref(false); // Para permitir pedir otro viaje
const showPaymentModal = ref(false);
const completedTrip = ref(null);
let debounceTimeout;

// --- BUSCAR VIAJE ACTIVO ---
// Buscamos si hay un viaje que no esté cancelado.
const currentTrip = computed(() => {
    return props.trips.find(t => ['pending', 'accepted', 'in_progress', 'completed'].includes(t.status));
});

// --- FORMULARIO ---
const form = useForm({
    origin: '📍 Localizando...',
    origin_lat: null,
    origin_lng: null,
    destination: '',
    destination_lat: null,
    destination_lng: null,
    payment_method: 'Efectivo', // Default para la BD
    price: 5.00 // Precio base estimado
});

// --- 1. GEOLOCALIZACIÓN AUTOMÁTICA (GPS) ---
const getAddressFromCoords = async (lat, lng) => {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await response.json();
        if (data && data.display_name) {
            // Tomamos solo las primeras 3 partes de la dirección para que no sea gigante
            const shortAddress = data.display_name.split(',').slice(0, 3).join(',');
            form.origin = "📍 " + shortAddress;
        }
    } catch (error) {
        form.origin = "📍 Ubicación GPS Detectada";
    }
};

onMounted(() => {
    // Retraso ligero para asegurar que el contenedor del mapa exista
    setTimeout(() => { mapReady.value = true; }, 100);

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Centrar mapa y guardar datos
                center.value = [lat, lng];
                userLocation.value = [lat, lng];
                
                // Llenar formulario
                form.origin_lat = lat;
                form.origin_lng = lng;
                getAddressFromCoords(lat, lng);
            },
            (error) => { console.error("Error de GPS:", error); form.origin = "📍 Error de GPS (Ingresa manual)"; }
        );
    }
});

// --- 2. BUSCADOR INTELIGENTE (Debounce) ---
const handleInput = (event) => {
    const query = event.target.value;
    form.destination = query;

    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        if (query.length < 3) {
            searchResults.value = [];
            return;
        }
        isSearching.value = true;
        try {
            // Buscamos solo en Venezuela (countrycodes=ve)
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ve&limit=5`);
            const data = await response.json();
            searchResults.value = data;
        } catch (error) {
            console.error("Error buscando:", error);
        } finally {
            isSearching.value = false;
        }
    }, 300);
};

// --- 3. SELECCIONAR DESTINO ---
const selectLocation = (place) => {
    form.destination = place.display_name.split(',')[0]; // Nombre corto
    const lat = parseFloat(place.lat);
    const lng = parseFloat(place.lon);
    
    form.destination_lat = lat;
    form.destination_lng = lng;
    
    // Movemos el mapa para mostrar el destino
    destinationLocation.value = [lat, lng];
    center.value = [lat, lng];
    zoom.value = 16;
    searchResults.value = []; // Limpiar lista
};

// --- ACCIONES DEL PASAJERO ---
const submitTrip = () => {
    if (!form.origin_lat || !form.destination_lat) {
        alert("⚠️ Espera a que el GPS te localice o selecciona un destino válido.");
        return;
    }

    form.post(route('trips.store'), {
        onSuccess: () => {
            form.reset();
            hideCompletedTrip.value = false; // Mostrar la tarjeta del nuevo viaje
        },
    });
};

const cancelTrip = (id) => {
    if(confirm('¿Seguro que deseas cancelar la solicitud?')) {
        router.delete(route('trip.cancel', id));
    }
};

const startNewTrip = () => {
    hideCompletedTrip.value = true; // Ocultamos la tarjeta de "Completado"
    form.reset();
    form.origin = "📍 Re-localizando...";
    
    // Volvemos a ubicar al usuario
    if (userLocation.value) {
        form.origin_lat = userLocation.value[0];
        form.origin_lng = userLocation.value[1];
        getAddressFromCoords(userLocation.value[0], userLocation.value[1]);
        center.value = userLocation.value;
    }
};

// --- ACCIONES DEL CONDUCTOR ---
const acceptTrip = (tripId) => {
    if (confirm('¿Confirmas que quieres tomar este viaje?')) {
        router.put(route('trip.accept', tripId));
    }
};

const startTrip = (tripId) => {
    if (confirm('¿El pasajero ya subió al vehículo?')) {
        router.put(route('trips.start', tripId));
    }
};

const finishTrip = (trip) => { 
    if (confirm('¿Han llegado al destino?')) {
        router.put(route('trips.finish', trip.id), {}, {
            onSuccess: () => {
                completedTrip.value = trip;
                showPaymentModal.value = true;
            }
        });
    }
};

// --- ACCIONES ADMIN ---
const approveDriver = (id) => { if(confirm('¿Aprobar?')) router.put(route('admin.approve', id)); };
const rejectDriver = (id) => { if(confirm('¿Rechazar?')) router.delete(route('admin.reject', id)); };

// --- UTILIDADES ---
const closePaymentModal = () => { showPaymentModal.value = false; completedTrip.value = null; };
const statusColor = (status) => {
    if (status === 'pending') return 'bg-yellow-100 text-yellow-800';
    if (status === 'accepted') return 'bg-blue-100 text-blue-800';
    if (status === 'in_progress') return 'bg-purple-100 text-purple-800';
    return 'bg-green-100 text-green-800';
};
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
                    <h3 class="font-bold text-lg mb-4">👮‍♂️ Solicitudes de Conductores</h3>
                    <table v-if="pendingDrivers.length > 0" class="min-w-full mt-4 border">
                        <thead class="bg-gray-100">
                            <tr><th class="px-4 py-2">Nombre</th><th class="px-4 py-2">Acción</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="driver in pendingDrivers" :key="driver.id" class="border-t">
                                <td class="px-4 py-2 text-center">{{driver.name}}</td>
                                <td class="px-4 py-2 text-center flex justify-center gap-4">
                                    <button @click="approveDriver(driver.id)" class="text-green-600 font-bold hover:underline">✔ Aprobar</button>
                                    <button @click="rejectDriver(driver.id)" class="text-red-600 font-bold hover:underline">❌ Rechazar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-gray-500 mt-4">No hay solicitudes pendientes.</p>
                </div>

                <div v-else-if="currentUserRole === 'driver'" class="space-y-8">
                    <div v-if="!isApproved" class="bg-yellow-50 border-l-4 border-yellow-400 p-8 shadow-md rounded-r-lg flex items-center justify-center min-h-[400px]">
                        <div class="text-center">
                            <div class="text-6xl mb-4">⏳</div>
                            <h3 class="text-2xl font-bold text-yellow-800 mb-2">Cuenta en Revisión</h3>
                            <p class="text-yellow-700 max-w-md mx-auto">Tus documentos han sido enviados. Espera aprobación del administrador.</p>
                        </div>
                    </div>

                    <div v-else>
                        <div class="bg-white p-4 rounded-xl shadow-sm flex justify-between items-center border-l-4 border-green-500 mb-6">
                            <div>
                                <h3 class="font-bold text-gray-800 text-lg">Estás Conectado</h3>
                                <p class="text-sm text-gray-500">Visible para pasajeros cercanos.</p>
                            </div>
                            <span class="text-green-600 font-bold text-sm bg-green-100 px-3 py-1 rounded-full">EN LÍNEA 🟢</span>
                        </div>

                        <div v-if="myTrips && myTrips.length > 0" class="space-y-4">
                            <h3 class="font-bold text-gray-800 text-lg border-b pb-2">🚖 Tu Viaje Activo</h3>
                            
                            <div v-for="trip in myTrips" :key="trip.id" class="bg-white p-6 rounded-2xl shadow-lg border border-blue-100 mb-4">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 class="font-bold text-lg text-gray-800">Pasajero #{{ trip.passenger_id }}</h4>
                                        <p class="text-sm text-gray-500">📍 {{ trip.origin }}</p>
                                        <p class="text-sm text-gray-900 font-bold">🏁 {{ trip.destination }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-2xl font-bold text-green-600">${{ trip.price }}</span>
                                        <span class="text-xs uppercase font-bold bg-gray-100 px-2 py-1 rounded">{{ trip.payment_method }}</span>
                                    </div>
                                </div>

                                <div v-if="trip.status === 'accepted'" class="mt-4">
                                    <button @click="startTrip(trip.id)" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-lg">
                                        ▶️ Iniciar Viaje (Pasajero a bordo)
                                    </button>
                                </div>

                                <div v-else-if="trip.status === 'in_progress'" class="mt-4 space-y-3">
                                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-3 rounded-lg text-center font-medium animate-pulse">
                                        🚕 Viaje en curso...
                                    </div>
                                    <button @click="finishTrip(trip)" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-xl transition shadow-lg">
                                        🏁 Finalizar y Cobrar
                                    </button>
                                </div>

                                <div v-else-if="trip.status === 'completed'" class="mt-4 bg-green-50 border border-green-200 p-4 rounded-xl text-center">
                                    <p class="text-green-800 font-bold text-lg">✅ ¡Cobrado!</p>
                                    <p class="text-gray-600 text-sm">Esperando nuevo viaje...</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="availableTrips.length === 0 && (!myTrips || myTrips.length === 0)" class="flex flex-col items-center justify-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100 mt-8">
                             <div class="text-4xl mb-4">📡</div>
                             <h3 class="text-xl font-bold text-gray-800">Escaneando zona...</h3>
                             <p class="text-gray-500">No hay pasajeros solicitando ahora.</p>
                        </div>

                        <div v-else-if="availableTrips.length > 0" class="mt-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">🔥 Solicitudes Pendientes</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="trip in availableTrips" :key="trip.id" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden group">
                                    <div class="bg-gray-900 p-4 flex justify-between items-center text-white">
                                        <span class="font-bold text-lg text-green-400">${{ trip.price }}</span>
                                        <span class="text-xs bg-gray-700 px-2 py-1 rounded">{{ trip.payment_method }}</span>
                                    </div>
                                    <div class="p-5 space-y-4">
                                        <div>
                                            <p class="text-xs text-gray-400 uppercase">Recoger en</p>
                                            <p class="font-bold text-gray-800 truncate">{{ trip.origin }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400 uppercase">Destino</p>
                                            <p class="font-bold text-gray-800 truncate">{{ trip.destination }}</p>
                                        </div>
                                        <button @click="acceptTrip(trip.id)" class="w-full py-3 bg-gray-100 text-gray-800 font-bold rounded-xl group-hover:bg-green-500 group-hover:text-white transition-colors">
                                            Aceptar Viaje ⚡
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1 space-y-6">
                        
                        <div v-if="!currentTrip || (currentTrip.status === 'completed' && hideCompletedTrip)" class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 relative z-10">
                            <h2 class="text-2xl font-bold mb-4 text-gray-800">Pedir un viaje</h2>
                            
                            <form @submit.prevent="submitTrip" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">📍 Origen (GPS)</label>
                                    <input v-model="form.origin" type="text" class="w-full rounded-lg border-gray-300 mt-1 bg-gray-50 text-gray-600 cursor-not-allowed" readonly>
                                    <p v-if="userLocation" class="text-xs text-green-600 mt-1 font-bold">✅ Ubicación satelital detectada</p>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700">🏁 Destino</label>
                                    <input 
                                        :value="form.destination" 
                                        @input="handleInput"
                                        type="text" 
                                        placeholder="Ej. Centro Comercial..." 
                                        class="w-full rounded-lg border-gray-300 mt-1 p-2 focus:ring-black focus:border-black" 
                                        autocomplete="off"
                                    >
                                    
                                    <ul v-if="searchResults.length > 0" class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto">
                                        <li v-for="place in searchResults" :key="place.place_id" @click="selectLocation(place)" class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b last:border-0 text-sm flex items-center gap-2">
                                            <span>🏁</span>
                                            <div>
                                                <p class="font-bold text-gray-800">{{ place.display_name.split(',')[0] }}</p>
                                                <p class="text-xs text-gray-500 truncate w-64">{{ place.display_name }}</p>
                                            </div>
                                        </li>
                                    </ul>
                                    <p v-if="isSearching" class="text-xs text-blue-500 mt-1 animate-pulse">🔎 Buscando...</p>
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
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-bold text-gray-800">Estado del Viaje</h2>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase" :class="statusColor(currentTrip.status)">
                                        {{ currentTrip.status }}
                                    </span>
                                </div>
                                <div class="space-y-4 mb-6">
                                    <div class="flex items-start gap-3"><div class="w-3 h-3 bg-blue-500 rounded-full mt-1.5"></div><div><p class="text-xs text-gray-400">Origen</p><p class="font-bold text-gray-800">{{ currentTrip.origin }}</p></div></div>
                                    <div class="flex items-start gap-3"><div class="w-3 h-3 bg-red-500 rounded-full mt-1.5"></div><div><p class="text-xs text-gray-400">Destino</p><p class="font-bold text-gray-800">{{ currentTrip.destination }}</p></div></div>
                                </div>
                                <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                    <div><p class="text-xs text-gray-500">Monto</p><p class="text-2xl font-black text-gray-900">${{ currentTrip.price }}</p></div>
                                    <div class="text-right"><p class="text-xs text-gray-500">Pago</p><p class="font-bold text-gray-700">{{ currentTrip.payment_method }}</p></div>
                                </div>

                                <button v-if="currentTrip.status === 'pending'" @click="cancelTrip(currentTrip.id)" class="w-full mt-4 text-red-500 text-sm font-bold hover:underline">
                                    Cancelar solicitud
                                </button>

                                <div v-if="currentTrip.status === 'completed'" class="mt-6">
                                    <button @click="startNewTrip" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3 rounded-xl transition flex items-center justify-center gap-2 shadow-lg">
                                        Pedir nuevo viaje 🔄
                                    </button>
                                </div>
                             </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 h-full flex flex-col min-h-[500px]">
                            <h3 class="font-bold text-gray-800 mb-4 flex justify-between">
                                <span>🗺️ Mapa en vivo</span>
                                <span v-if="userLocation" class="text-green-600 text-sm animate-pulse">📍 GPS Activo</span>
                                <span v-else class="text-red-500 text-sm">Buscando señal...</span>
                            </h3>
                            
                            <div class="flex-1 rounded-xl overflow-hidden border border-gray-200 relative z-0">
                                 <l-map v-if="mapReady" ref="map" v-model:zoom="zoom" :center="center" :use-global-leaflet="false">
                                    <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"></l-tile-layer>
                                    
                                    <l-marker v-if="userLocation" :lat-lng="userLocation">
                                        <l-popup>👋 ¡Estás aquí!</l-popup>
                                    </l-marker>

                                    <l-marker v-if="destinationLocation && (!currentTrip || hideCompletedTrip)" :lat-lng="destinationLocation">
                                        <l-popup>🏁 Destino: {{ form.destination }}</l-popup>
                                    </l-marker>

                                    <div v-if="currentTrip && !hideCompletedTrip">
                                        <l-marker v-if="currentTrip.destination_lat" :lat-lng="[parseFloat(currentTrip.destination_lat), parseFloat(currentTrip.destination_lng)]">
                                            <l-popup>🏁 Fin del Viaje</l-popup>
                                        </l-marker>

                                        <l-polyline 
                                            v-if="currentTrip.origin_lat && currentTrip.destination_lat" 
                                            :lat-lngs="[
                                                [parseFloat(currentTrip.origin_lat), parseFloat(currentTrip.origin_lng)], 
                                                [parseFloat(currentTrip.destination_lat), parseFloat(currentTrip.destination_lng)]
                                            ]" 
                                            color="#3B82F6" 
                                            :weight="5"
                                        />
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
                <div class="bg-green-500 p-6 text-center text-white">
                    <div class="bg-white text-green-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg"><span class="text-3xl">💵</span></div>
                    <h3 class="text-2xl font-bold">¡Viaje Finalizado!</h3>
                    <p class="text-green-100 text-sm">Cobro al pasajero</p>
                </div>
                <div class="p-8 text-center space-y-6">
                    <div>
                        <p class="text-gray-500 uppercase text-xs font-bold tracking-wider">Total a Pagar</p>
                        <p class="text-5xl font-extrabold text-gray-900 mt-2">${{ completedTrip?.price }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-600 space-y-2">
                        <div class="flex justify-between"><span>Pasajero:</span><span class="font-bold text-gray-800">#{{ completedTrip?.passenger_id }}</span></div>
                        <div class="flex justify-between"><span>Método:</span><span class="font-bold text-gray-800">{{ completedTrip?.payment_method }}</span></div>
                    </div>
                    <button @click="closePaymentModal" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:scale-[1.02]">
                        ✅ Confirmar Pago Recibido
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>