<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import 'leaflet/dist/leaflet.css'; 
import L from 'leaflet';

// --- VARIABLES ---
const mapContainer = ref(null);
const map = ref(null);
const marker = ref(null);

// Formulario con campos ocultos para coordenadas
const form = useForm({
    origin: '',
    origin_lat: null, // 🔥 Nuevo
    origin_lng: null, // 🔥 Nuevo
    destination: '',
    destination_lat: null, // 🔥 Nuevo
    destination_lng: null, // 🔥 Nuevo
    price: 0,
});

const suggestions = ref([]);      
const activeField = ref(null);    
const isLoadingLocation = ref(false); 
let debounceTimeout = null;       

// --- 1. INICIALIZAR MAPA ---
onMounted(() => {
    map.value = L.map(mapContainer.value).setView([10.244, -66.860], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);
});

const updateMap = (lat, lng) => {
    if (map.value) {
        map.value.setView([lat, lng], 15);
        if (marker.value) {
            marker.value.setLatLng([lat, lng]);
        } else {
            marker.value = L.marker([lat, lng]).addTo(map.value);
        }
        marker.value.bindPopup("📍 Ubicación seleccionada").openPopup();
    }
};

// --- 2. GPS (Captura Coordenadas) ---
const getMyLocation = () => {
    if (!navigator.geolocation) {
        alert("Tu navegador no soporta geolocalización.");
        return;
    }
    isLoadingLocation.value = true;
    navigator.geolocation.getCurrentPosition(async (position) => {
        const { latitude, longitude } = position.coords;
        
        // Guardamos coordenadas en el formulario
        form.origin_lat = latitude;
        form.origin_lng = longitude;

        updateMap(latitude, longitude);

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
            const data = await response.json();
            form.origin = data.display_name;
        } catch (error) {
            form.origin = `${latitude}, ${longitude}`;
        } finally {
            isLoadingLocation.value = false;
        }
    }, () => {
        alert("No pudimos acceder a tu ubicación.");
        isLoadingLocation.value = false;
    });
};

// --- 3. BUSCADOR ---
const searchAddress = (event, field) => {
    const query = event.target.value;
    activeField.value = field; 

    if (query.length < 3) {
        suggestions.value = [];
        return;
    }
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=5&countrycodes=ve`);
            suggestions.value = await response.json();
        } catch (error) {
            console.error(error);
        }
    }, 300);
};

// --- 4. SELECCIONAR SUGERENCIA (Captura Coordenadas) ---
const selectSuggestion = (place) => {
    // Convertimos las coordenadas de texto a números
    const lat = parseFloat(place.lat);
    const lon = parseFloat(place.lon);

    if (activeField.value === 'origin') {
        form.origin = place.display_name;
        form.origin_lat = lat; // 🔥 Guardar Latitud Origen
        form.origin_lng = lon; // 🔥 Guardar Longitud Origen
        updateMap(lat, lon);
    } else {
        form.destination = place.display_name;
        form.destination_lat = lat; // 🔥 Guardar Latitud Destino
        form.destination_lng = lon; // 🔥 Guardar Longitud Destino
    }
    suggestions.value = [];
};

// --- 5. ENVIAR ---
const submitTrip = () => {
    // Validación básica antes de enviar
    if (!form.origin_lat || !form.destination_lat) {
        alert("⚠️ Por favor selecciona direcciones válidas de la lista para poder calcular el precio.");
        return;
    }

    form.post(route('trips.store'), {
        onSuccess: () => console.log("Viaje enviado"),
        onError: () => alert('❌ Error. Verifica los datos.'),
    });
};
</script>

<template>
    <Head title="Pedir un Viaje" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📍 Nuevo Viaje
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen" @click="suggestions = []">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-visible shadow-xl sm:rounded-2xl border border-gray-100 relative p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">¿A dónde te llevo?</h3>

                        <form @submit.prevent="submitTrip" class="space-y-6 relative">
                            
                            <div class="relative">
                                <label class="block font-medium text-sm text-gray-700 mb-1">Punto de Recogida</label>
                                <div class="relative flex items-center">
                                    <div class="absolute left-3 text-blue-500">🔵</div>
                                    <input 
                                        v-model="form.origin"
                                        @input="searchAddress($event, 'origin')"
                                        type="text" 
                                        placeholder="Buscar dirección..."
                                        class="pl-10 pr-12 w-full border-gray-300 focus:border-black focus:ring-black rounded-lg shadow-sm"
                                        required
                                        autocomplete="off"
                                    >
                                    <button 
                                        type="button"
                                        @click="getMyLocation"
                                        class="absolute right-2 text-gray-400 hover:text-blue-600 p-1 transition"
                                        title="Usar mi ubicación actual"
                                    >
                                        <span v-if="isLoadingLocation" class="animate-spin block">🔄</span>
                                        <span v-else>📍</span>
                                    </button>
                                </div>
                                <ul v-if="suggestions.length > 0 && activeField === 'origin'" class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto">
                                    <li v-for="place in suggestions" :key="place.place_id" @click.stop="selectSuggestion(place)" class="px-4 py-3 hover:bg-gray-100 cursor-pointer text-sm border-b flex gap-2">
                                        <span>📍</span> {{ place.display_name }}
                                    </li>
                                </ul>
                            </div>

                            <div class="relative">
                                <label class="block font-medium text-sm text-gray-700 mb-1">Destino Final</label>
                                <div class="relative">
                                    <div class="absolute left-3 text-red-500">🚩</div>
                                    <input 
                                        v-model="form.destination"
                                        @input="searchAddress($event, 'destination')"
                                        type="text" 
                                        placeholder="Ej: Centro Comercial..."
                                        class="pl-10 w-full border-gray-300 focus:border-black focus:ring-black rounded-lg shadow-sm"
                                        required
                                        autocomplete="off"
                                    >
                                </div>
                                <ul v-if="suggestions.length > 0 && activeField === 'destination'" class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto">
                                    <li v-for="place in suggestions" :key="place.place_id" @click.stop="selectSuggestion(place)" class="px-4 py-3 hover:bg-gray-100 cursor-pointer text-sm border-b flex gap-2">
                                        <span>🏁</span> {{ place.display_name }}
                                    </li>
                                </ul>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full bg-black text-white font-bold py-4 rounded-xl hover:bg-gray-800 transition shadow-lg flex justify-center items-center gap-2"
                            >
                                <span v-if="form.processing">Calculando tarifa... ⏳</span>
                                <span v-else>✅ Confirmar y Ver Precio</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-2 hidden lg:block">
                    <div class="bg-white p-2 rounded-2xl shadow-lg h-[600px] relative z-0">
                        <div ref="mapContainer" class="w-full h-full rounded-xl z-0"></div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.leaflet-container { z-index: 0; }
</style>