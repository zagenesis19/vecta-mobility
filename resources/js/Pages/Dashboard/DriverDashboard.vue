<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import DriverDocumentsModal from '@/Components/DriverDocumentsModal.vue';
import PaymentMethodModal from '@/Components/PaymentMethodModal.vue';
import MobilePaymentModal from '@/Components/MobilePaymentModal.vue';
import StarRating from '@/Components/StarRating.vue'; // Driver also rates passenger

const props = defineProps({
    availableTrips: { type: Array, default: () => [] },
    myTrips: { type: Array, default: () => [] },
    isApproved: { type: Boolean, default: false },
    pendingActionTrip: { type: Object, default: null } // 🔥 Inyectado desde el server
});

const page = usePage();
const showDriverDocsModal = ref(false);

const missingDocsCount = computed(() => {
    const u = page.props.auth.user;
    let missing = 0;
    if (!u.profile_photo_path) missing++;
    if (!u.license_file) missing++;
    if (!u.id_card_photo_path) missing++;
    if (!u.medical_certificate_file) missing++;
    if (!u.rif_file) missing++;
    if (!u.circulation_permit_file_path) missing++; // 🔥 Nuevo requisito
    return missing;
});

// --- ACCIONES DE VIAJE ---
const acceptTrip = (id) => { if (confirm('¿Tomar viaje?')) router.put(route('trip.accept', id)); };
const startTrip = (id) => { if (confirm('¿Pasajero a bordo?')) router.put(route('trips.start', id)); };
const cancelTrip = (id) => { if(confirm('¿Cancelar?')) router.delete(route('trip.cancel', id)); }; 

// --- FINALIZAR VIAJE Y PAGO ---
const showPaymentModal = ref(false);
const showMobilePaymentModal = ref(false);
const showRatingModal = ref(false);
const tripToRate = ref(null);
const completedTrip = ref(null);

// 🔥 WATCHER MAESTRO: Reacciona a lo que diga el servidor
watch(() => props.pendingActionTrip, (trip) => {
    if (!trip) {
        showPaymentModal.value = false;
        showMobilePaymentModal.value = false;
        showRatingModal.value = false;
        completedTrip.value = null;
        return;
    }

    // 🛑 EVITAR BUCLE: Si ya estamos trabajando en este trip (y es el mismo ID), no reiniciamos modales
    if (completedTrip.value && completedTrip.value.id === trip.id) {
        // Solo actualizamos si cambió el estado de pago (ej. de false a true)
        if (completedTrip.value.payment_confirmed !== trip.payment_confirmed) {
            completedTrip.value = trip; // Actualizamos datos
        } else {
             return; // Ya está abierto, no hacemos nada
        }
    } else {
        completedTrip.value = trip;
    }

    // 1. Si no ha confirmado pago
    if (!trip.payment_confirmed) {
        if (!showPaymentModal.value && !showMobilePaymentModal.value) { // Solo si no están ya abiertos
            if (trip.payment_method === 'Pago Móvil') {
                showMobilePaymentModal.value = true;
            } else {
                showPaymentModal.value = true;
            }
        }
        showRatingModal.value = false;
    } 
    // 2. Si ya pagó pero no ha calificado
    else {
        showPaymentModal.value = false;
        showMobilePaymentModal.value = false;
        tripToRate.value = trip;
        if (!showRatingModal.value) showRatingModal.value = true;
    }
}, { immediate: true, deep: true });

const finishTrip = (trip) => { 
    if (confirm('¿Llegada?')) {
        router.put(route('trips.finish', trip.id), {}, { 
            preserveState: true,
            preserveScroll: true
        }); 
    }
};

const confirmMobilePayment = () => {
    if (!props.pendingActionTrip) return;
    axios.post(route('trip.confirmPayment', props.pendingActionTrip.id))
        .then(() => {
            // El servidor actualizará payment_confirmed y el watcher hará el resto
            router.reload({ only: ['pendingActionTrip'] });
        });
};

const closePaymentModal = () => { 
    // Para efectivo, asumimos cobro al cerrar o podrías tener un endpoint similar
    if (!props.pendingActionTrip) return;
     axios.post(route('trip.confirmPayment', props.pendingActionTrip.id))
        .then(() => {
            router.reload({ only: ['pendingActionTrip'] });
        });
};

const handleRatingCompleted = () => {
    showRatingModal.value = false;
    router.reload(); // Limpiar todo
};

// --- RASTREO GPS (SOLO CONDUCTOR) ---
let gpsWatchId = null;
const activeTrip = computed(() => props.myTrips.find(t => t.status === 'in_progress'));

const startRealTimeTracking = () => {
    if (activeTrip.value && navigator.geolocation) {
        gpsWatchId = navigator.geolocation.watchPosition((pos) => {
            const { latitude, longitude } = pos.coords;
            axios.post(route('driver.location'), { lat: latitude, lng: longitude }).catch(()=>{});
        }, null, { enableHighAccuracy: true });
    }
};

onMounted(() => {
    startRealTimeTracking();
});

onUnmounted(() => {
    if (gpsWatchId) navigator.geolocation.clearWatch(gpsWatchId);
});
</script>

<template>
    <div class="space-y-6">
        <!-- 🚨 ALERTA DE DOCUMENTOS FALTANTES -->
        <div v-if="missingDocsCount > 0" class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 cursor-pointer hover:bg-red-100 transition shadow-sm rounded-r-lg" @click="showDriverDocsModal = true">
            <div class="flex items-center">
            <div class="flex-shrink-0 text-xl">⚠️</div>
            <div class="ml-3 w-full">
                <div class="flex justify-between items-center">
                    <p class="text-sm font-bold text-red-800">Documentación Incompleta</p>
                    <span class="text-xs bg-white px-2 py-1 rounded border text-red-600 font-bold shadow-sm">TAREA: {{ missingDocsCount }} Pendientes</span>
                </div>
                <p class="text-sm text-red-700 mt-1">Pulsa aquí para subir los archivos requeridos.</p>
            </div>
            </div>
        </div>
        
        <DriverDocumentsModal :show="showDriverDocsModal" @close="showDriverDocsModal = false" />

        <div v-if="!isApproved" class="bg-yellow-50 p-8 text-center rounded-lg border-l-4 border-yellow-400"><h3 class="text-xl font-bold text-yellow-800">Cuenta en Revisión ⏳</h3></div>
        <div v-else>
            <div class="bg-green-100 p-4 rounded-lg flex justify-between items-center mb-6">
                <span class="font-bold text-green-800">🟢 EN LÍNEA</span>
            </div>
            
            <div v-for="trip in myTrips" :key="trip.id" class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-500 mb-4">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <img 
                            :src="trip.passenger && trip.passenger.profile_photo_path ? '/storage/'+trip.passenger.profile_photo_path : 'https://ui-avatars.com/api/?name='+(trip.passenger ? trip.passenger.name : 'Pasajero')" 
                            class="w-10 h-10 rounded-full object-cover border"
                        >
                        <div>
                            <h4 class="font-bold">{{ trip.passenger ? trip.passenger.name : 'Pasajero #'+trip.passenger_id }}</h4>
                            <p v-if="trip.passenger && trip.passenger.phone_number" class="text-xs text-blue-600 font-bold">
                                📞 +58 {{ trip.passenger.phone_number }}
                            </p>
                            <p class="text-sm text-gray-500">{{trip.origin_address}} ➡ {{trip.destination_address}}</p>
                        </div>
                    </div>
                    <div class="text-right"><span class="text-2xl font-bold text-green-600">${{trip.price}}</span><p class="text-xs uppercase bg-gray-200 px-1 rounded">{{trip.status}}</p></div>
                </div>
                <button v-if="trip.status==='accepted'" @click="startTrip(trip.id)" class="w-full mt-4 bg-blue-600 text-white py-2 rounded-lg font-bold">▶️ Iniciar</button>
                <button v-if="trip.status==='in_progress'" @click="finishTrip(trip)" class="w-full mt-4 bg-red-500 text-white py-2 rounded-lg font-bold">🏁 Finalizar</button>
                <button v-if="trip.status==='accepted'" @click="cancelTrip(trip.id)" class="w-full mt-2 bg-red-50 text-red-600 text-sm font-bold py-2 rounded-lg hover:bg-red-100 transition border border-red-200">❌ Cancelar viaje</button>
                <a v-if="trip.status!=='pending'" :href="`waze://?ll=${trip.destination_lat},${trip.destination_lng}&navigate=yes`" target="_blank" class="block text-center mt-2 text-2xl">🚗</a>
            </div>

            <h3 v-if="availableTrips.length>0" class="font-bold text-lg">🔥 Disponibles</h3>
            <div v-for="trip in availableTrips" :key="trip.id" class="bg-white p-4 rounded-xl shadow mt-2 border">
                <!-- INFO PASAJERO -->
                <div v-if="trip.passenger" class="flex items-center gap-3 mb-3 border-b pb-2">
                    <img 
                        :src="trip.passenger.profile_photo_path ? '/storage/'+trip.passenger.profile_photo_path : 'https://ui-avatars.com/api/?name='+trip.passenger.name" 
                        class="w-10 h-10 rounded-full object-cover border"
                    >
                    <div>
                            <p class="font-bold text-sm text-gray-900">{{ trip.passenger.name }}</p>
                            <div class="flex items-center gap-1">
                                <span class="text-yellow-500 text-xs font-bold bg-yellow-50 px-1 rounded border border-yellow-200">
                                ⭐ {{ trip.passenger.average_rating }}
                                </span>
                            </div>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-2">
                    <span class="bg-black text-white px-2 py-1 text-xs rounded uppercase font-bold">{{trip.vehicle_type === 'car' ? '🚗 Carro' : '🏍️ Moto'}}</span>
                    <span class="font-bold text-green-600 text-xl">${{trip.price}}</span>
                </div>
                <p class="text-sm">📍 {{trip.origin_address}}</p>
                <p class="font-bold text-sm">🏁 {{trip.destination_address}}</p>
                <button @click="acceptTrip(trip.id)" class="w-full mt-3 bg-gray-100 hover:bg-green-500 hover:text-white py-2 rounded-lg font-bold">Aceptar</button>
            </div>
        </div>

        <!-- Modales de Pago -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden text-center p-8">
                <div class="text-6xl mb-4">💵</div>
                <h3 class="text-2xl font-bold mb-2">Cobrar ${{completedTrip?.price}}</h3>
                <p class="text-gray-500 mb-6">Método: {{completedTrip?.payment_method}}</p>
                <button @click="closePaymentModal" class="w-full bg-green-500 text-white font-bold py-3 rounded-xl hover:bg-green-600 transition">Confirmar Cobro ✅</button>
            </div>
        </div>

        <MobilePaymentModal 
            :show="showMobilePaymentModal"
            :tripData="completedTrip || { price: 0, payment_method: 'Pago Móvil' }"
            userRole="driver"
            @close="showMobilePaymentModal = false"
            @confirmPayment="confirmMobilePayment"
        />

        <StarRating 
            v-if="showRatingModal" 
            :trip="tripToRate" 
            userRole="driver"
            @close="handleRatingCompleted" 
        />
    </div>
</template>
