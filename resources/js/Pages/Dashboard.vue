<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';

// Recibimos los datos del Backend
const props = defineProps({
    availableTrips: {
        type: Array,
        default: () => [] 
    },
    // Lista de conductores pendientes (Solo para Admin)
    pendingDrivers: {
        type: Array,
        default: () => []
    },
    userRole: String,
    // Variable nueva para controlar el candado
    isApproved: { 
        type: Boolean, 
        default: false 
    } 
});

const page = usePage();
const currentUserRole = props.userRole || page.props.auth.user.role;

// --- FUNCIONES DE CHOFER ---
const acceptTrip = (tripId) => {
    if (confirm('¿Confirmas que quieres tomar este viaje?')) {
        router.put(route('trip.accept', tripId), {}, {
            onSuccess: () => alert('✅ ¡Viaje asignado! Ve por el pasajero.'),
            onError: () => alert('❌ El viaje ya no está disponible.')
        });
    }
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
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <span class="text-green-600 font-bold text-sm">EN LÍNEA</span>
                        </div>
                    </div>

                    <div v-if="availableTrips.length === 0" class="flex flex-col items-center justify-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                        <div class="absolute w-96 h-96 bg-blue-50 rounded-full animate-pulse opacity-50"></div>
                        <div class="z-10 text-center relative">
                            <div class="bg-white p-4 rounded-full shadow-lg inline-block mb-4">
                                <span class="text-4xl">📡</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Escaneando zona...</h3>
                            <p class="text-gray-500 mt-2">Te avisaremos cuando alguien pida un viaje cerca.</p>
                        </div>
                    </div>

                    <div v-else>
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            🔥 Oportunidades Cercanas
                            <span class="bg-red-100 text-red-600 text-xs px-2 py-1 rounded-full animate-pulse">
                                {{ availableTrips.length }} Nuevos
                            </span>
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="trip in availableTrips" :key="trip.id" class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden group">
                                <div class="bg-gray-900 p-4 flex justify-between items-center text-white">
                                    <span class="font-bold text-lg text-green-400">${{ trip.price }}</span>
                                    <span class="text-xs bg-gray-700 px-2 py-1 rounded">Efectivo</span>
                                </div>
                                <div class="p-5 space-y-4">
                                    <div class="flex flex-col gap-3 relative">
                                        <div class="absolute left-[7px] top-3 bottom-3 w-0.5 bg-gray-200"></div>
                                        <div class="flex items-start gap-3 relative z-10">
                                            <div class="w-4 h-4 rounded-full bg-blue-500 border-2 border-white shadow mt-1"></div>
                                            <div>
                                                <p class="text-xs text-gray-400 uppercase">Recoger</p>
                                                <p class="font-bold text-gray-800">{{ trip.origin }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-3 relative z-10">
                                            <div class="w-4 h-4 rounded-full bg-red-500 border-2 border-white shadow mt-1"></div>
                                            <div>
                                                <p class="text-xs text-gray-400 uppercase">Destino</p>
                                                <p class="font-bold text-gray-800">{{ trip.destination }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <button @click="acceptTrip(trip.id)" class="w-full py-3 bg-gray-100 text-gray-800 font-bold rounded-xl group-hover:bg-green-500 group-hover:text-white transition-colors duration-300 flex items-center justify-center gap-2">
                                        Aceptar Viaje ⚡
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl p-8 shadow-xl text-white relative overflow-hidden">
                            <div class="absolute right-0 top-0 w-64 h-64 bg-white opacity-5 rounded-full -mr-16 -mt-16"></div>
                            <h2 class="text-3xl font-bold mb-2">¿A dónde vamos hoy?</h2>
                            <p class="text-gray-400 mb-8">Conductores cerca listos para llevarte.</p>
                            <Link :href="route('trips.create')" class="w-full bg-white text-black font-bold text-center py-4 rounded-xl hover:bg-gray-100 transition shadow-lg transform hover:scale-[1.01] flex justify-center items-center gap-2">
                                🔍  Ingresar destino
                            </Link>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3 cursor-pointer hover:bg-gray-50 transition">
                                <div class="bg-blue-100 p-2 rounded-lg text-xl">🏠</div>
                                <div><p class="font-bold text-gray-800">Casa</p><p class="text-xs text-gray-500">Guardar dirección</p></div>
                            </div>
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3 cursor-pointer hover:bg-gray-50 transition">
                                <div class="bg-orange-100 p-2 rounded-lg text-xl">💼</div>
                                <div><p class="font-bold text-gray-800">Trabajo</p><p class="text-xs text-gray-500">Guardar dirección</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 h-full flex flex-col min-h-[300px]">
                        <h3 class="font-bold text-gray-800 mb-4">Mapa en vivo</h3>
                        <div class="flex-1 bg-blue-50 rounded-xl relative overflow-hidden flex items-center justify-center border border-blue-100">
                             <div class="text-center z-10"><span class="text-4xl block mb-2">🗺️</span><span class="text-sm text-gray-500 font-medium">Ubicación Actual</span></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>