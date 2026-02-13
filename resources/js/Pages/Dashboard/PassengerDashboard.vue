<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import "leaflet/dist/leaflet.css";
import { LMap, LTileLayer, LMarker, LPolyline, LPopup, LIcon } from "@vue-leaflet/vue-leaflet";

import PaymentMethodModal from '@/Components/PaymentMethodModal.vue';
import CancellationSurvey from '@/Components/CancellationSurvey.vue';
import StarRating from '@/Components/StarRating.vue';

const props = defineProps({
    currentTrip: { type: Object, default: null },
    trips: { type: Array, default: () => [] }
});

// --- MAPA ---
const zoom = ref(14); 
const center = ref([10.2443, -66.8622]); 
const userLocation = ref(null);
const driverLocation = ref(null); 
const destinationLocation = ref(null);
const mapReady = ref(false); 
const mapRef = ref(null); 

const routeCoordinates = ref([]); 
const estimatedDuration = ref(0); 
const calculatedDistance = ref(0);
const priceCar = ref(0);
const priceMoto = ref(0);

// --- UI ESTADOS ---
const searchResults = ref([]); 
const originSearchResults = ref([]); 
const isSearching = ref(false);
const hideCompletedTrip = ref(false);
const selectedVehicle = ref(null); 

const showPaymentMethodModal = ref(false);
const showCancellationSurvey = ref(false);
const showRatingModal = ref(false);
const tripToRate = ref(null);
const tripToCancel = ref(null);
const pendingTripData = ref(null);

let debounceTimeout;
let pollingInterval = null; 

const form = useForm({
    origin: '📍 Localizando...',
    origin_lat: null,
    origin_lng: null,
    destination: '',
    destination_lat: null,
    destination_lng: null,
    vehicle_type: 'car',
    payment_method: 'Efectivo', 
    price: 0 
});

const activeTrip = computed(() => {
    return props.currentTrip || props.trips.find(t => ['pending', 'accepted', 'in_progress', 'completed'].includes(t.status));
});

// --- WATCHER FIN DE VIAJE ---
watch(() => props.currentTrip, (newTrip, oldTrip) => {
    if (newTrip && oldTrip) {
        if (oldTrip.status === 'in_progress' && newTrip.status === 'completed') {
            tripToRate.value = newTrip;
            showRatingModal.value = true;
        }
    }
}, { deep: true });

// --- OSRM ---
const getRouteAndDetails = async () => {
    if (!form.origin_lat || !form.destination_lat) return;
    const start = `${form.origin_lng},${form.origin_lat}`;
    const end = `${form.destination_lng},${form.destination_lat}`;
    try {
        const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${start};${end}?overview=full&geometries=geojson`);
        const data = await response.json();
        if (data.routes && data.routes.length > 0) {
            const route = data.routes[0];
            routeCoordinates.value = route.geometry.coordinates.map(coord => [coord[1], coord[0]]);
            const distKm = (route.distance / 1000);
            calculatedDistance.value = distKm.toFixed(2);
            estimatedDuration.value = Math.round(route.duration / 60);
            let calcCar = 2.50 + (distKm * 0.90);
            priceCar.value = (calcCar < 3.50 ? 3.50 : calcCar).toFixed(2);
            let calcMoto = 1.20 + (distKm * 0.50);
            priceMoto.value = (calcMoto < 1.50 ? 1.50 : calcMoto).toFixed(2);
        }
    } catch (e) { console.error("Error OSRM", e); }
};

watch([() => form.origin_lat, () => form.destination_lat], () => {
    if (form.origin_lat && form.destination_lat) getRouteAndDetails();
});

// --- UTILIDADES GEOLOCALIZACIÓN ---
const getAddressFromCoords = async (lat, lng) => {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await res.json();
        if (data?.display_name) form.origin = "📍 " + data.display_name.split(',')[0];
    } catch (e) { form.origin = "📍 Ubicación detectada"; }
};

const refreshCurrentLocation = () => {
    form.origin = '📍 Localizando...';
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                form.origin_lat = lat;
                form.origin_lng = lng;
                userLocation.value = [lat, lng];
                center.value = [lat, lng];
                getAddressFromCoords(lat, lng);
            },
            () => { form.origin = '❌ Error de ubicación'; }
        );
    } else {
        form.origin = '❌ GPS no disponible';
    }
};

const handleInputOrigin = (e) => {
    const query = e.target.value;
    form.origin = query;
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        if (query.length < 3) { originSearchResults.value = []; return; }
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ve&limit=5`);
            originSearchResults.value = await res.json();
        } catch (e) {}
    }, 300);
};

const selectOrigin = (place) => {
    form.origin = "📍 " + place.display_name.split(',')[0]; 
    const lat = parseFloat(place.lat);
    const lng = parseFloat(place.lon);
    form.origin_lat = lat;
    form.origin_lng = lng;
    center.value = [lat, lng];
    userLocation.value = [lat, lng];
    originSearchResults.value = []; 
};

const handleInput = (e) => {
    const query = e.target.value;
    form.destination = query;
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        if (query.length < 3) { searchResults.value = []; return; }
        isSearching.value = true;
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ve&limit=5`);
            searchResults.value = await res.json();
        } catch (e) {} finally { isSearching.value = false; }
    }, 300);
};

const selectLocation = (place) => {
    form.destination = place.display_name.split(',')[0]; 
    const lat = parseFloat(place.lat);
    const lng = parseFloat(place.lon);
    form.destination_lat = lat;
    form.destination_lng = lng;
    destinationLocation.value = [lat, lng];
    center.value = [lat, lng];
    searchResults.value = []; 
    selectedVehicle.value = null;
};

// --- ACCIONES FLUJO ---
const submitRide = () => {
    if (!selectedVehicle.value) return alert("Selecciona un vehículo");
    pendingTripData.value = {
        vehicle_type: selectedVehicle.value,
        price: selectedVehicle.value === 'car' ? priceCar.value : priceMoto.value
    };
    showPaymentMethodModal.value = true;
};

const confirmPaymentMethod = (method) => {
    showPaymentMethodModal.value = false;
    form.vehicle_type = pendingTripData.value.vehicle_type;
    form.price = pendingTripData.value.price;
    form.payment_method = method;
    
    form.post(route('trips.store'), {
        onSuccess: () => { 
            form.reset(); 
            hideCompletedTrip.value = false; 
            selectedVehicle.value = null;
            pendingTripData.value = null;
            startRealTimeTracking();
        },
    });
};

const cancelTrip = (id) => { 
    tripToCancel.value = activeTrip.value;
    showCancellationSurvey.value = true;
};

const confirmCancellation = (reason) => {
    if (!tripToCancel.value) return;
    axios.post(route('trip.cancelWithReason', tripToCancel.value.id), { reason })
        .then(() => {
            showCancellationSurvey.value = false;
            tripToCancel.value = null;
            router.reload();
        });
};

const startNewTrip = () => { hideCompletedTrip.value = true; form.reset(); refreshCurrentLocation(); selectedVehicle.value=null; };

const openRatingManual = () => {
    if (activeTrip.value && activeTrip.value.status === 'completed') {
        tripToRate.value = activeTrip.value;
        showRatingModal.value = true;
    }
};

const handleRatingCompleted = () => {
    showRatingModal.value = false;
    tripToRate.value = null;
    startNewTrip();
};

// --- RASTREO REAL ---
const startRealTimeTracking = () => {
    if (['accepted', 'in_progress'].includes(activeTrip.value?.status)) {
        pollingInterval = setInterval(() => {
            router.reload({
                only: ['currentTrip'], 
                onSuccess: () => {
                    if (props.currentTrip?.driver) {
                        const dLat = props.currentTrip.driver.current_lat;
                        const dLng = props.currentTrip.driver.current_lng;
                        if (dLat && dLng) driverLocation.value = [parseFloat(dLat), parseFloat(dLng)];
                    }
                }
            });
        }, 4000);
    }
};

onMounted(() => {
    setTimeout(() => { mapReady.value = true; }, 100);

    // Check for query params from Landing Page
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('origin_lat') && urlParams.has('destination_lat')) {
        form.origin = urlParams.get('origin_address') || form.origin;
        form.origin_lat = parseFloat(urlParams.get('origin_lat'));
        form.origin_lng = parseFloat(urlParams.get('origin_lng'));
        form.destination = urlParams.get('destination_address') || form.destination;
        form.destination_lat = parseFloat(urlParams.get('destination_lat'));
        form.destination_lng = parseFloat(urlParams.get('destination_lng'));

        if (form.origin_lat && form.origin_lng) {
            center.value = [form.origin_lat, form.origin_lng];
            userLocation.value = [form.origin_lat, form.origin_lng];
        }
        if (form.destination_lat && form.destination_lng) {
            destinationLocation.value = [form.destination_lat, form.destination_lng];
        }
        
        getRouteAndDetails();
        
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname);
    } else {
        refreshCurrentLocation();
    }

    if (activeTrip.value) {
        startRealTimeTracking();
        if(activeTrip.value.origin_lat && activeTrip.value.destination_lat) {
             form.origin_lat = activeTrip.value.origin_lat;
             form.origin_lng = activeTrip.value.origin_lng;
             form.destination_lat = activeTrip.value.destination_lat; 
             form.destination_lng = activeTrip.value.destination_lng;
             getRouteAndDetails();
        }
    }
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <!-- FORMULARIO DE SOLICITUD -->
                <div v-if="!activeTrip || (activeTrip.status === 'completed' && hideCompletedTrip)" class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 relative z-10">
                    <h2 class="text-2xl font-bold mb-4 text-gray-800">¿A dónde vamos?</h2>
                    <div class="space-y-4 mb-6">
                        <div class="relative">
                            <label class="text-xs font-bold text-gray-400 uppercase">Origen</label>
                            <div class="flex gap-2">
                                <input :value="form.origin" @input="handleInputOrigin" type="text" placeholder="📍 ¿Dónde estás?" class="flex-1 rounded-lg border-gray-300 text-sm p-2">
                                <button @click="refreshCurrentLocation" class="bg-blue-500 hover:bg-blue-600 text-white px-3 rounded-lg transition-all shadow-sm active:scale-95" title="Actualizar mi ubicación">📍</button>
                            </div>
                            <ul v-if="originSearchResults.length>0" class="absolute z-50 w-full bg-white border rounded-lg shadow-xl mt-1 max-h-40 overflow-y-auto">
                                <li v-for="p in originSearchResults" :key="p.place_id" @click="selectOrigin(p)" class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm truncate border-b">📍 {{p.display_name}}</li>
                            </ul>
                        </div>
                        <div class="relative">
                            <label class="text-xs font-bold text-gray-400 uppercase">Destino</label>
                            <input :value="form.destination" @input="handleInput" type="text" placeholder="🏁 Buscar destino..." class="w-full rounded-lg border-gray-300 text-sm p-2">
                            <ul v-if="searchResults.length>0" class="absolute z-50 w-full bg-white border rounded-lg shadow-xl mt-1 max-h-40 overflow-y-auto">
                                <li v-for="p in searchResults" :key="p.place_id" @click="selectLocation(p)" class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm truncate border-b">🏁 {{p.display_name}}</li>
                            </ul>
                        </div>
                    </div>
                    <div class="space-y-3 mt-4 border-t pt-4">
                        <div v-if="!form.origin_lat || !form.destination_lat" class="text-xs text-gray-400 text-center py-2">Selecciona origen y destino para ver precios...</div>
                        <div v-else>
                            <div class="flex justify-between text-xs text-gray-500 px-1 mb-2"><span>Distancia: <b>{{calculatedDistance}} km</b></span><span class="text-blue-600 font-bold">⏱️ {{estimatedDuration}} min</span></div>
                            <div @click="selectedVehicle = 'motorcycle'" class="cursor-pointer bg-white border-2 rounded-xl p-3 flex justify-between items-center transition hover:bg-gray-50 mb-2" :class="selectedVehicle === 'motorcycle' ? 'border-black ring-1 ring-black bg-gray-50' : 'border-gray-200'"><div class="flex items-center gap-3"><div class="bg-yellow-100 p-2 rounded-lg text-2xl">🏍️</div><div><p class="font-bold text-gray-800 text-sm">Moto</p><p class="text-xs text-gray-500">Rápido</p></div></div><span class="text-xl font-black text-gray-900">${{ priceMoto }}</span></div>
                            <div @click="selectedVehicle = 'car'" class="cursor-pointer bg-white border-2 rounded-xl p-3 flex justify-between items-center transition hover:bg-gray-50" :class="selectedVehicle === 'car' ? 'border-black ring-1 ring-black bg-gray-50' : 'border-gray-200'"><div class="flex items-center gap-3"><div class="bg-blue-100 p-2 rounded-lg text-2xl">🚗</div><div><p class="font-bold text-gray-800 text-sm">Carro</p><p class="text-xs text-gray-500">Cómodo</p></div></div><span class="text-xl font-black text-gray-900">${{ priceCar }}</span></div>
                            <button v-if="selectedVehicle" @click="submitRide" :disabled="form.processing" class="w-full bg-black text-white font-bold py-3 rounded-xl mt-4 hover:bg-gray-800 transition shadow-lg transform active:scale-95">Confirmar Viaje</button>
                        </div>
                    </div>
                </div>

                <!-- VIAJE EN CURSO -->
                <div v-else class="bg-white rounded-2xl p-6 shadow-xl border-l-4 border-blue-500">
                        <h2 class="text-xl font-bold mb-4">Viaje en curso</h2>
                        <div class="flex justify-between mb-4"><span class="font-bold text-lg text-gray-800 uppercase bg-gray-100 px-2 rounded flex items-center gap-2">{{ activeTrip.vehicle_type === 'motorcycle' ? '🏍️ MOTO' : '🚗 CARRO' }}</span><span class="font-bold text-green-600 text-xl">${{activeTrip.price}}</span></div>
                        <div class="space-y-2 text-sm text-gray-600 mb-4"><p>📍 {{activeTrip.origin_address}}</p><p>🏁 {{activeTrip.destination_address}}</p></div>
                        
                        <div v-if="activeTrip.driver" class="bg-green-50 p-3 rounded mb-4 flex items-center gap-3">
                        <img 
                            :src="activeTrip.driver.profile_photo_path ? '/storage/'+activeTrip.driver.profile_photo_path : 'https://ui-avatars.com/api/?name='+activeTrip.driver.name" 
                            class="w-12 h-12 rounded-full object-cover border-2 border-green-500"
                        >
                        <div>
                            <p class="font-bold text-sm flex items-center gap-1">{{activeTrip.driver.name}} <span class="text-yellow-500 text-xs ml-1 bg-yellow-50 px-1 rounded border border-yellow-200">★ {{ activeTrip.driver.average_rating }}</span></p>
                            
                            <p v-if="activeTrip.status === 'completed'" class="text-xs text-blue-600 font-bold">🏁 Viaje Finalizado</p>
                            <p v-else-if="activeTrip.status === 'in_progress'" class="text-xs text-green-700">En camino (Rastreando 📡)</p>
                            <p v-else-if="activeTrip.status === 'accepted'" class="text-xs text-indigo-700">Conductor llegando... 🚖</p>
                            
                            <p v-if="activeTrip.driver.phone_number" class="text-xs font-bold text-gray-600 mt-1">📞 +58 {{ activeTrip.driver.phone_number }}</p>
                        </div>
                        </div>
                        
                        <div v-else class="bg-yellow-50 p-3 rounded mb-4 text-center text-xs font-bold text-yellow-700 animate-pulse">Buscando conductor...</div>
                        <button v-if="activeTrip.status==='pending' || activeTrip.status==='accepted'" @click="cancelTrip(activeTrip.id)" class="w-full bg-red-50 text-red-600 text-sm font-bold py-2 rounded-lg hover:bg-red-100 transition border border-red-200">❌ Cancelar viaje</button>
                        
                        <div v-if="activeTrip.status==='completed'" class="flex gap-2">
                        <button @click="openRatingManual" class="flex-1 bg-yellow-400 text-yellow-900 py-3 rounded-xl font-bold hover:bg-yellow-500 transition">⭐ Calificar</button>
                        <button @click="startNewTrip" class="flex-1 bg-black text-white py-3 rounded-xl font-bold hover:bg-gray-800 transition">Nuevo Viaje 🔄</button>
                        </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border p-2 h-[500px] lg:h-auto lg:min-h-[600px] flex flex-col">
                        <l-map v-if="mapReady" ref="map" v-model:zoom="zoom" :center="center" :use-global-leaflet="false" class="flex-1 rounded-xl z-0">
                        <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"></l-tile-layer>
                        <l-marker v-if="userLocation" :lat-lng="userLocation"><l-popup>Tú</l-popup></l-marker>
                        <l-marker v-if="destinationLocation || activeTrip?.destination_lat" :lat-lng="activeTrip?.destination_lat ? [activeTrip.destination_lat, activeTrip.destination_lng] : destinationLocation"><l-popup>Destino</l-popup></l-marker>
                        <l-marker v-if="driverLocation" :lat-lng="driverLocation"><l-icon class-name="driver-icon" :icon-size="[40,40]" icon-url="https://cdn-icons-png.flaticon.com/512/1048/1048314.png" /><l-popup>Conductor</l-popup></l-marker>
                        <l-polyline v-if="routeCoordinates.length > 0" :lat-lngs="routeCoordinates" color="#3B82F6" :weight="5" :opacity="0.7"></l-polyline>
                        </l-map>
                </div>
            </div>
        </div>

        <PaymentMethodModal 
            :show="showPaymentMethodModal"
            :tripData="pendingTripData || { price: 0 }"
            @close="showPaymentMethodModal = false"
            @confirm="confirmPaymentMethod"
        />

        <CancellationSurvey 
            :show="showCancellationSurvey"
            userRole="passenger"
            :tripId="tripToCancel?.id || 0"
            @close="showCancellationSurvey = false"
            @confirm="confirmCancellation"
        />

        <StarRating 
            v-if="showRatingModal" 
            :trip="tripToRate" 
            userRole="passenger"
            @close="handleRatingCompleted" 
        />
    </div>
</template>
