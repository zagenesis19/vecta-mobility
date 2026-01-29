<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage, useForm, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';

// --- LEAFLET ---
import "leaflet/dist/leaflet.css";
import { LMap, LTileLayer, LMarker, LPolyline, LPopup } from "@vue-leaflet/vue-leaflet";

const props = defineProps({
    availableTrips: { type: Array, default: () => [] },
    myTrips: { type: Array, default: () => [] },
    pendingDrivers: { type: Array, default: () => [] },
    trips: { type: Array, default: () => [] },
    userRole: String,
    currentTrip: { type: Object, default: null },
    isApproved: { type: Boolean, default: false } 
});

const page = usePage();
const currentUserRole = props.userRole || page.props.auth.user.role;

// --- MAPA ---
const zoom = ref(15);
const center = ref([10.2443, -66.8622]); 
const userLocation = ref(null);
const destinationLocation = ref(null);
const mapReady = ref(false);

// --- UI ---
const searchResults = ref([]);
const isSearching = ref(false);
const hideCompletedTrip = ref(false);
const showPaymentModal = ref(false);
const completedTrip = ref(null);
let debounceTimeout;

// --- 💰 VARIABLES DE NEGOCIO ---
const calculatedDistance = ref(0);
const priceCar = ref(0);
const priceMoto = ref(0);
const selectedVehicle = ref(null); 

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

// --- 🧮 CÁLCULOS (Internos para el precio) ---
const calculateDistanceKm = (lat1, lon1, lat2, lon2) => {
    const R = 6371; 
    const dLat = (lat2 - lat1) * (Math.PI / 180);
    const dLon = (lon2 - lon1) * (Math.PI / 180);
    const a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*(Math.PI/180))*Math.cos(lat2*(Math.PI/180))*Math.sin(dLon/2)*Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c; 
};

watch([() => form.origin_lat, () => form.destination_lat], () => {
    if (form.origin_lat && form.destination_lat) {
        const distRecta = calculateDistanceKm(form.origin_lat, form.origin_lng, form.destination_lat, form.destination_lng);
        const distReal = distRecta * 1.4; // Factor tráfico/curvas
        calculatedDistance.value = distReal.toFixed(2);

        // Tarifas
        let calcCar = 2.50 + (distReal * 0.90);
        if (calcCar < 3.50) calcCar = 3.50;
        priceCar.value = calcCar.toFixed(2);

        let calcMoto = 1.20 + (distReal * 0.50);
        if (calcMoto < 1.50) calcMoto = 1.50;
        priceMoto.value = calcMoto.toFixed(2);
    }
});

// --- GEO ---
const getAddressFromCoords = async (lat, lng) => {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
        const data = await response.json();
        if (data && data.display_name) form.origin = "📍 " + data.display_name.split(',')[0];
    } catch (e) { form.origin = "📍 Ubicación detectada"; }
};

onMounted(() => {
    setTimeout(() => { mapReady.value = true; }, 100);
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            const { latitude, longitude } = pos.coords;
            center.value = [latitude, longitude];
            userLocation.value = [latitude, longitude];
            form.origin_lat = latitude;
            form.origin_lng = longitude;
            getAddressFromCoords(latitude, longitude);
        });
    }
});

// --- BUSCADOR ---
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
        } catch (e) { console.error(e); } finally { isSearching.value = false; }
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

// --- SUBMIT ---
const submitRide = () => {
    if (!selectedVehicle.value) {
        alert("⚠️ Selecciona un tipo de vehículo.");
        return;
    }
    form.vehicle_type = selectedVehicle.value;
    form.price = selectedVehicle.value === 'car' ? priceCar.value : priceMoto.value;

    form.post(route('trips.store'), {
        onSuccess: () => {
            form.reset();
            hideCompletedTrip.value = false;
            selectedVehicle.value = null; 
        },
    });
};

// --- ACCIONES ---
const activeTrip = computed(() => props.currentTrip || props.trips.find(t => ['pending', 'accepted', 'in_progress', 'completed'].includes(t.status)));
const cancelTrip = (id) => { if(confirm('¿Seguro?')) router.delete(route('trip.cancel', id)); };
const startNewTrip = () => { hideCompletedTrip.value = true; form.reset(); form.origin="📍 Re-localizando..."; selectedVehicle.value = null; if(userLocation.value){ form.origin_lat=userLocation.value[0]; form.origin_lng=userLocation.value[1]; } };
const acceptTrip = (id) => { if (confirm('¿Tomar viaje?')) router.put(route('trip.accept', id)); };
const startTrip = (id) => { if (confirm('¿Pasajero a bordo?')) router.put(route('trips.start', id)); };
const finishTrip = (trip) => { if (confirm('¿Llegada?')) router.put(route('trips.finish', trip.id), {}, { onSuccess: () => { completedTrip.value = trip; showPaymentModal.value = true; } }); };
const approveDriver = (id) => { if(confirm('¿Aprobar?')) router.put(route('admin.approve', id)); };
const rejectDriver = (id) => { if(confirm('¿Rechazar?')) router.delete(route('admin.reject', id)); };
const closePaymentModal = () => { showPaymentModal.value = false; completedTrip.value = null; };
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
                    <h3 class="font-bold text-lg mb-4">Solicitudes</h3>
                    <div v-for="d in pendingDrivers" :key="d.id" class="flex justify-between border-b py-2 items-center">
                        <span class="font-bold">{{d.name}}</span>
                        <div><button @click="approveDriver(d.id)" class="text-green-600 font-bold bg-green-50 px-2 rounded mr-2">✔</button><button @click="rejectDriver(d.id)" class="text-red-600 font-bold bg-red-50 px-2 rounded">❌</button></div>
                    </div>
                </div>

                <div v-else-if="currentUserRole === 'driver'" class="space-y-6">
                    <div v-if="!isApproved" class="bg-yellow-50 p-8 text-center rounded-lg border-l-4 border-yellow-400"><h3 class="text-xl font-bold text-yellow-800">Cuenta en Revisión ⏳</h3></div>
                    <div v-else>
                        <div class="bg-green-100 p-4 rounded-lg flex justify-between items-center mb-6"><span class="font-bold text-green-800">🟢 EN LÍNEA</span></div>
                        
                        <div v-for="trip in myTrips" :key="trip.id" class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500 mb-4">
                            <div class="flex justify-between">
                                <div><h4 class="font-bold">Pasajero #{{trip.passenger_id}}</h4><p class="text-sm text-gray-500">{{trip.origin_address}} ➡ {{trip.destination_address}}</p></div>
                                <div class="text-right"><span class="text-2xl font-bold text-green-600">${{trip.price}}</span><p class="text-xs uppercase bg-gray-200 px-1 rounded">{{trip.payment_method}}</p></div>
                            </div>
                            <button v-if="trip.status==='accepted'" @click="startTrip(trip.id)" class="w-full mt-4 bg-blue-600 text-white py-2 rounded-lg font-bold">▶️ Iniciar</button>
                            <button v-if="trip.status==='in_progress'" @click="finishTrip(trip)" class="w-full mt-4 bg-red-500 text-white py-2 rounded-lg font-bold">🏁 Finalizar</button>
                        </div>

                        <h3 v-if="availableTrips.length>0" class="font-bold text-lg">🔥 Disponibles</h3>
                        <div v-for="trip in availableTrips" :key="trip.id" class="bg-white p-4 rounded-xl shadow mt-2 border">
                            <div class="flex justify-between items-center mb-2">
                                <span class="bg-black text-white px-2 py-1 text-xs rounded uppercase font-bold">{{trip.vehicle_type === 'car' ? '🚗 Carro' : '🏍️ Moto'}}</span>
                                <span class="font-bold text-green-600 text-xl">${{trip.price}}</span>
                            </div>
                            <p class="text-sm">📍 {{trip.origin_address}}</p>
                            <p class="font-bold text-sm">🏁 {{trip.destination_address}}</p>
                            <button @click="acceptTrip(trip.id)" class="w-full mt-3 bg-gray-100 hover:bg-green-500 hover:text-white py-2 rounded-lg font-bold">Aceptar</button>
                        </div>
                        <div v-if="availableTrips.length===0" class="text-center py-10 bg-white rounded-lg border-dashed border"><p class="text-gray-500">Buscando pasajeros...</p></div>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1 space-y-6">
                        
                        <div v-if="!activeTrip || (activeTrip.status === 'completed' && hideCompletedTrip)" class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100 relative z-10">
                            <h2 class="text-2xl font-bold mb-4 text-gray-800">¿A dónde vamos?</h2>
                            
                            <div class="space-y-4 mb-6">
                                <div><label class="text-xs font-bold text-gray-400 uppercase">Origen</label><input v-model="form.origin" type="text" class="w-full rounded-lg border-gray-300 bg-gray-50 text-sm" readonly></div>
                                <div class="relative">
                                    <label class="text-xs font-bold text-gray-400 uppercase">Destino</label>
                                    <input :value="form.destination" @input="handleInput" type="text" placeholder="Buscar destino..." class="w-full rounded-lg border-gray-300 text-sm p-2">
                                    <ul v-if="searchResults.length>0" class="absolute z-50 w-full bg-white border rounded-lg shadow-xl mt-1 max-h-40 overflow-y-auto">
                                        <li v-for="p in searchResults" :key="p.place_id" @click="selectLocation(p)" class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm truncate border-b">🏁 {{p.display_name}}</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Método de Pago</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <div @click="form.payment_method = 'Efectivo'" class="cursor-pointer border rounded-lg p-2 text-center text-xs font-bold transition-all" :class="form.payment_method === 'Efectivo' ? 'bg-green-100 border-green-500 text-green-800' : 'hover:bg-gray-50'">
                                        💵 Efectivo
                                    </div>
                                    <div @click="form.payment_method = 'Pago Móvil'" class="cursor-pointer border rounded-lg p-2 text-center text-xs font-bold transition-all" :class="form.payment_method === 'Pago Móvil' ? 'bg-blue-100 border-blue-500 text-blue-800' : 'hover:bg-gray-50'">
                                        📱 Pago Móvil
                                    </div>
                                    <div @click="form.payment_method = 'Tarjeta'" class="cursor-pointer border rounded-lg p-2 text-center text-xs font-bold transition-all" :class="form.payment_method === 'Tarjeta' ? 'bg-purple-100 border-purple-500 text-purple-800' : 'hover:bg-gray-50'">
                                        💳 Tarjeta
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.origin_lat && form.destination_lat" class="space-y-3 animate-fade-in">
                                
                                <div @click="selectedVehicle = 'motorcycle'" 
                                     class="cursor-pointer bg-white border-2 rounded-xl p-3 flex justify-between items-center transition"
                                     :class="selectedVehicle === 'motorcycle' ? 'border-black ring-2 ring-black bg-gray-50' : 'border-gray-100 hover:border-gray-300'">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-yellow-100 p-2 rounded-lg text-2xl">🏍️</div>
                                        <div><p class="font-bold text-gray-800 text-sm">Moto</p><p class="text-xs text-gray-500">Rápido • 1 persona</p></div>
                                    </div>
                                    <span class="text-xl font-black text-gray-900">${{ priceMoto }}</span>
                                </div>

                                <div @click="selectedVehicle = 'car'" 
                                     class="cursor-pointer bg-white border-2 rounded-xl p-3 flex justify-between items-center transition"
                                     :class="selectedVehicle === 'car' ? 'border-black ring-2 ring-black bg-gray-50' : 'border-gray-100 hover:border-gray-300'">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-blue-100 p-2 rounded-lg text-2xl">🚗</div>
                                        <div><p class="font-bold text-gray-800 text-sm">Carro</p><p class="text-xs text-gray-500">Cómodo • 4 personas</p></div>
                                    </div>
                                    <span class="text-xl font-black text-gray-900">${{ priceCar }}</span>
                                </div>

                                <button v-if="selectedVehicle" @click="submitRide" :disabled="form.processing" class="w-full bg-black text-white font-bold py-3 rounded-xl mt-4 hover:bg-gray-800 transition shadow-lg transform active:scale-95 animate-fade-in">
                                    Confirmar Viaje en {{ selectedVehicle === 'car' ? '🚗' : '🏍️' }}
                                </button>
                            </div>
                            
                            <div v-else class="text-center py-6 bg-gray-50 rounded-lg border-dashed border text-gray-400 text-sm">Elige destino para ver precios 👆</div>
                        </div>

                        <div v-else class="bg-white rounded-2xl p-6 shadow-xl border-l-4 border-blue-500">
                             <h2 class="text-xl font-bold mb-4">Viaje en curso</h2>
                             <div class="flex justify-between mb-4">
                                 <span class="font-bold text-lg text-gray-800 uppercase bg-gray-100 px-2 rounded flex items-center gap-2">
                                     {{ activeTrip.vehicle_type === 'motorcycle' ? '🏍️ MOTO' : '🚗 CARRO' }}
                                 </span>
                                 <span class="font-bold text-green-600 text-xl">${{activeTrip.price}}</span>
                             </div>
                             <div class="space-y-2 text-sm text-gray-600 mb-4"><p>📍 {{activeTrip.origin_address}}</p><p>🏁 {{activeTrip.destination_address}}</p></div>
                             <div class="bg-blue-50 text-blue-800 p-2 rounded text-center text-xs font-bold uppercase mb-4">{{activeTrip.status}}</div>
                             <button v-if="activeTrip.status==='pending'" @click="cancelTrip(activeTrip.id)" class="w-full text-red-500 text-sm font-bold hover:underline">Cancelar solicitud</button>
                             <button v-if="activeTrip.status==='completed'" @click="startNewTrip" class="w-full bg-black text-white py-3 rounded-xl font-bold hover:bg-gray-800 transition">Pedir Nuevo Viaje 🔄</button>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border p-2 h-[500px] lg:h-auto lg:min-h-[600px] flex flex-col">
                             <l-map v-if="mapReady" ref="map" v-model:zoom="zoom" :center="center" :use-global-leaflet="false" class="flex-1 rounded-xl z-0">
                                <l-tile-layer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"></l-tile-layer>
                                <l-marker v-if="userLocation" :lat-lng="userLocation"><l-popup>Tú</l-popup></l-marker>
                                <l-marker v-if="destinationLocation" :lat-lng="destinationLocation"><l-popup>Destino</l-popup></l-marker>
                                <l-polyline v-if="activeTrip && activeTrip.origin_lat" :lat-lngs="[[activeTrip.origin_lat, activeTrip.origin_lng], [activeTrip.destination_lat, activeTrip.destination_lng]]" color="blue"></l-polyline>
                             </l-map>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden text-center p-8">
                <div class="text-6xl mb-4">💵</div>
                <h3 class="text-2xl font-bold mb-2">Cobrar ${{completedTrip?.price}}</h3>
                <p class="text-gray-500 mb-6">Método: {{completedTrip?.payment_method}}</p>
                <button @click="closePaymentModal" class="w-full bg-green-500 text-white font-bold py-3 rounded-xl hover:bg-green-600 transition">Confirmar Cobro ✅</button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>