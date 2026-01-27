<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// --- PROPS (DATOS DEL BACKEND) ---
const props = defineProps({
    availableTrips: {
        type: Array,
        default: () => [] 
    },
    myTrips: {
        type: Array,
        default: () => []
    },
    pendingDrivers: {
        type: Array,
        default: () => []
    },
    // 🔥 IMPORTANTE: Lista de viajes del pasajero para saber si tiene uno activo
    trips: {
        type: Array,
        default: () => []
    },
    userRole: String,
    isApproved: { 
        type: Boolean, 
        default: false 
    } 
});

const page = usePage();
const currentUserRole = props.userRole || page.props.auth.user.role;

// --- VARIABLES Y LÓGICA DEL PASAJERO ---

// 1. Detectar si el pasajero ya tiene un viaje activo (el más reciente)
const currentTrip = props.trips && props.trips.length > 0 ? props.trips[0] : null;

// 2. Formulario para pedir viaje
const form = useForm({
    origin: '',
    destination: '',
    payment_method: 'Efectivo',
    // Coordenadas simuladas para que funcione el controlador
    origin_lat: 10.4806,
    origin_lng: -66.9036,
    destination_lat: 10.5000,
    destination_lng: -66.9100,
});

const submitTrip = () => {
    form.post(route('trips.store'), {
        onSuccess: () => form.reset(),
    });
};

const cancelTrip = (id) => {
    if(confirm('¿Seguro que deseas cancelar la solicitud?')) {
        router.delete(route('trip.cancel', id));
    }
};

const statusColor = (status) => {
    if (status === 'pending') return 'bg-yellow-100 text-yellow-800';
    if (status === 'accepted') return 'bg-blue-100 text-blue-800';
    if (status === 'in_progress') return 'bg-purple-100 text-purple-800';
    return 'bg-green-100 text-green-800';
};

// --- VARIABLES MODAL DE PAGO ---
const showPaymentModal = ref(false);
const completedTrip = ref(null);

// --- FUNCIONES DE CHOFER ---
const acceptTrip = (tripId) => {
    if (confirm('¿Confirmas que quieres tomar este viaje?')) {
        router.put(route('trip.accept', tripId), {}, {
            onSuccess: () => alert('✅ ¡Viaje asignado! Ve por el pasajero.'),
            onError: () => alert('❌ El viaje ya no está disponible.')
        });
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

const closePaymentModal = () => {
    showPaymentModal.value = false;
    completedTrip.value = null;
};

// --- FUNCIONES DE ADMIN ---
const approveDriver = (driverId) => {
    if (confirm('¿Aprobar a este conductor?')) {
        router.put(route('admin.approve', driverId));
    }
};

const rejectDriver = (driverId) => {
    if (confirm('¿Rechazar solicitud permanentemente?')) {
        router.delete(route('admin.reject', driverId));
    }
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
                    <h3 class="text-lg font-bold mb-4 text-indigo-700 border-b pb-2">👮‍♂️ Solicitudes de Conductores</h3>
                    
                    <div v-if="!pendingDrivers || pendingDrivers.length === 0" class="text-gray-500 py-10 text-center">
                        <p>✅ Todo al día. No hay conductores esperando aprobación.</p>
                    </div>

                    <table v-else class="min-w-full divide-y divide-gray-200 mt-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehículo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Placa</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="driver in pendingDrivers" :key="driver.id">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-gray-800">{{ driver.name }}</p>
                                    <p class="text-xs text-gray-500">{{ driver.email }}</p>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ driver.vehicle_model || 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded">
                                        {{ driver.vehicle_plate || '---' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button @click="approveDriver(driver.id)" class="text-green-600 font-bold hover:underline">✔ Aprobar</button>
                                    <button @click="rejectDriver(driver.id)" class="text-red-600 font-bold hover:underline ml-2">✖ Rechazar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else-if="currentUserRole === 'driver' && !isApproved" class="bg-yellow-50 border-l-4 border-yellow-400 p-8 shadow-md rounded-r-lg flex items-center justify-center min-h-[400px]">
                    <div class="text-center">
                        <div class="text-6xl mb-4">⏳</div>
                        <h3 class="text-2xl font-bold text-yellow-800 mb-2">Cuenta en Revisión</h3>
                        <p class="text-yellow-700 max-w-md mx-auto">
                            Tus documentos han sido enviados correctamente. Un administrador debe aprobar tu vehículo y licencia antes de que puedas empezar a trabajar.
                        </p>
                        <div class="mt-6 inline-block bg-white px-4 py-2 rounded-full text-sm font-bold text-yellow-600 shadow-sm">
                            Estado: Pendiente
                        </div>
                    </div>
                </div>

                <div v-else-if="currentUserRole === 'driver' && isApproved" class="space-y-8">
                    
                    <div class="bg-white p-4 rounded-xl shadow-sm flex justify-between items-center border-l-4 border-green-500">
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg">Estás Conectado</h3>
                            <p class="text-sm text-gray-500">Tu vehículo es visible para los pasajeros.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-600 font-bold text-sm">EN LÍNEA</span>
                        </div>
                    </div>

                    <div v-if="myTrips && myTrips.length > 0" class="space-y-4">
                        <h3 class="font-bold text-gray-800 text-lg border-b pb-2">🚖 Tu Viaje Actual</h3>
                        <div v-for="trip in myTrips" :key="trip.id" class="bg-white p-6 rounded-2xl shadow-md border border-blue-100 mb-4 transition hover:shadow-lg">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h4 class="font-bold text-lg text-gray-800">Pasajero #{{ trip.passenger_id }}</h4>
                                    <p class="text-sm text-gray-500">📍 {{ trip.origin }} ➝ {{ trip.destination }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="block text-2xl font-bold text-gray-900">${{ trip.price }}</span>
                                    <span :class="{
                                        'bg-blue-100 text-blue-800': trip.status === 'accepted',
                                        'bg-yellow-100 text-yellow-800': trip.status === 'in_progress',
                                        'bg-green-100 text-green-800': trip.status === 'completed'
                                    }" class="px-2 py-1 rounded text-xs font-bold uppercase">{{ trip.status }}</span>
                                </div>
                            </div>
                            <div v-if="trip.status === 'accepted'" class="mt-4">
                                <button @click="startTrip(trip.id)" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition shadow-lg">▶️ Iniciar Viaje</button>
                            </div>
                            <div v-else-if="trip.status === 'in_progress'" class="mt-4 space-y-3">
                                <div class="bg-yellow-50 text-yellow-800 p-3 rounded-lg text-center font-medium animate-pulse">🚕 Viaje en curso...</div>
                                <button @click="finishTrip(trip)" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-xl transition shadow-lg">🏁 Finalizar y Cobrar</button>
                            </div>
                            <div v-else-if="trip.status === 'completed'" class="mt-4 bg-green-50 p-4 rounded-xl text-center">
                                <p class="text-green-800 font-bold">✅ Completado</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="availableTrips.length === 0" class="flex flex-col items-center justify-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100">
                        <span class="text-4xl">📡</span>
                        <h3 class="text-xl font-bold text-gray-800">Escaneando zona...</h3>
                        <p class="text-gray-500 mt-2">Te avisaremos cuando alguien pida un viaje.</p>
                    </div>

                    <div v-else>
                        <h3 class="text-xl font-bold text-gray-800 mb-4">🔥 Oportunidades Cercanas</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="trip in availableTrips" :key="trip.id" class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden group">
                                <div class="bg-gray-900 p-4 flex justify-between items-center text-white">
                                    <span class="font-bold text-lg text-green-400">${{ trip.price }}</span>
                                    <span class="text-xs bg-gray-700 px-2 py-1 rounded">Efectivo</span>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div><p class="text-xs text-gray-400 uppercase">Recoger</p><p class="font-bold text-gray-800">{{ trip.origin }}</p></div>
                                    <div><p class="text-xs text-gray-400 uppercase">Destino</p><p class="font-bold text-gray-800">{{ trip.destination }}</p></div>
                                    <button @click="acceptTrip(trip.id)" class="w-full py-3 bg-gray-100 text-gray-800 font-bold rounded-xl group-hover:bg-green-500 group-hover:text-white transition-colors duration-300">Aceptar Viaje ⚡</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-1 space-y-6">
                        
                        <div v-if="!currentTrip" class="bg-white rounded-2xl p-6 shadow-lg border border-gray-100">
                            <h2 class="text-2xl font-bold mb-4 text-gray-800">Pedir un viaje</h2>
                            <form @submit.prevent="submitTrip" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">📍 Origen</label>
                                    <input v-model="form.origin" type="text" placeholder="Ej. Mi Casa" class="w-full rounded-lg border-gray-300 mt-1 p-2 focus:ring-black focus:border-black" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">🏁 Destino</label>
                                    <input v-model="form.destination" type="text" placeholder="Ej. Centro Comercial" class="w-full rounded-lg border-gray-300 mt-1 p-2 focus:ring-black focus:border-black" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">💳 Método de Pago</label>
                                    <select v-model="form.payment_method" class="w-full rounded-lg border-gray-300 mt-1 p-2 focus:ring-black focus:border-black">
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
                                    <h2 class="text-xl font-bold text-gray-800">Viaje en Curso</h2>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase" :class="statusColor(currentTrip.status)">
                                        {{ currentTrip.status }}
                                    </span>
                                </div>

                                <div class="space-y-4 mb-6">
                                    <div class="flex items-start gap-3">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full mt-1.5"></div>
                                        <div>
                                            <p class="text-xs text-gray-400">Desde</p>
                                            <p class="font-bold text-gray-800">{{ currentTrip.origin }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <div class="w-3 h-3 bg-red-500 rounded-full mt-1.5"></div>
                                        <div>
                                            <p class="text-xs text-gray-400">Hasta</p>
                                            <p class="font-bold text-gray-800">{{ currentTrip.destination }}</p>
                                        </div>
                                    </div>
                                </div>

                                <hr class="border-gray-100 my-4">

                                <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                    <div>
                                        <p class="text-xs text-gray-500">Total a Pagar</p>
                                        <p class="text-2xl font-black text-gray-900">${{ currentTrip.price }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500">Método</p>
                                        <p class="font-bold text-gray-700 flex items-center gap-1 justify-end">
                                            {{ currentTrip.payment_method }}
                                        </p>
                                    </div>
                                </div>

                                <button v-if="currentTrip.status === 'pending'" @click="cancelTrip(currentTrip.id)" class="w-full mt-4 text-red-500 text-sm font-bold hover:underline">
                                    Cancelar solicitud
                                </button>
                             </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 h-full flex flex-col min-h-[500px]">
                            <h3 class="font-bold text-gray-800 mb-4 flex justify-between">
                                <span>🗺️ Ruta en vivo</span>
                                <span v-if="currentTrip" class="text-blue-600 text-sm">📍 Siguiendo trayecto...</span>
                            </h3>
                            
                            <div class="flex-1 bg-gray-100 rounded-xl relative overflow-hidden flex items-center justify-center border border-gray-200">
                                 <div v-if="currentTrip" class="text-center">
                                    <div class="text-6xl mb-2 animate-bounce">🚖</div>
                                    <p class="text-gray-500 font-medium">Calculando ruta hacia {{ currentTrip.destination }}...</p>
                                 </div>
                                 <div v-else class="text-center opacity-50">
                                    <span class="text-4xl">🗺️</span>
                                    <p>Ingresa un destino para ver la ruta</p>
                                 </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden relative transform transition-all scale-100">
                <div class="bg-green-500 p-6 text-center text-white">
                    <div class="bg-white text-green-500 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg">
                        <span class="text-3xl">💵</span>
                    </div>
                    <h3 class="text-2xl font-bold">¡Viaje Finalizado!</h3>
                    <p class="text-green-100 text-sm">Cobro al pasajero</p>
                </div>

                <div class="p-8 text-center space-y-6">
                    <div>
                        <p class="text-gray-500 uppercase text-xs font-bold tracking-wider">Total a Pagar</p>
                        <p class="text-5xl font-extrabold text-gray-900 mt-2">${{ completedTrip?.price }}</p>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-600 space-y-2">
                        <div class="flex justify-between">
                            <span>Pasajero:</span>
                            <span class="font-bold text-gray-800">#{{ completedTrip?.passenger_id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Método:</span>
                            <span class="font-bold text-gray-800">{{ completedTrip?.payment_method }}</span>
                        </div>
                    </div>

                    <button @click="closePaymentModal" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-4 rounded-xl shadow-lg transition transform hover:scale-[1.02]">
                        ✅ Confirmar Pago Recibido
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>