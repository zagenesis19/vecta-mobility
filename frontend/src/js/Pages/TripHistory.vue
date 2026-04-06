<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import html2canvas from 'html2canvas';

const props = defineProps({
    trips: { type: Array, default: () => [] },
    userRole: { type: String, required: true }
});

const selectedTrip = ref(null);
const showDetailModal = ref(false);
const showReceiptModal = ref(false);
const receiptRef = ref(null);

// Agrupar viajes por fecha
const groupedTrips = computed(() => {
    const groups = {};
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    const weekAgo = new Date(today);
    weekAgo.setDate(weekAgo.getDate() - 7);
    
    props.trips.forEach(trip => {
        const tripDate = new Date(trip.updated_at);
        tripDate.setHours(0, 0, 0, 0);
        
        let groupKey;
        if (tripDate.getTime() === today.getTime()) {
            groupKey = 'Hoy';
        } else if (tripDate.getTime() === yesterday.getTime()) {
            groupKey = 'Ayer';
        } else if (tripDate >= weekAgo) {
            groupKey = 'Esta semana';
        } else {
            groupKey = tripDate.toLocaleDateString('es-VE', { month: 'long', year: 'numeric' });
        }
        
        if (!groups[groupKey]) {
            groups[groupKey] = [];
        }
        groups[groupKey].push(trip);
    });
    
    return groups;
});

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('es-VE', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatFullDate = (date) => {
    return new Date(date).toLocaleDateString('es-VE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDuration = (minutes) => {
    if (!minutes) return 'N/A';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours > 0) {
        return `${hours}h ${mins}min`;
    }
    return `${mins} min`;
};

const openTripDetail = (trip) => {
    selectedTrip.value = trip;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedTrip.value = null;
};

const openReceipt = () => {
    showReceiptModal.value = true;
};

const closeReceipt = () => {
    showReceiptModal.value = false;
};

const saveReceiptAsImage = async () => {
    const element = document.getElementById('receipt-content');
    if (!element) return;
    
    try {
        const canvas = await html2canvas(element, {
            backgroundColor: '#ffffff',
            scale: 2
        });
        
        const link = document.createElement('a');
        link.download = `recibo-vecta-${selectedTrip.value.id}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    } catch (error) {
        console.error('Error al guardar recibo:', error);
        alert('Error al guardar el recibo como imagen');
    }
};

const getOtherPerson = (trip) => {
    if (props.userRole === 'passenger') {
        return trip.driver || { name: 'Conductor no asignado', profile_photo_path: null };
    } else {
        return trip.passenger || { name: 'Pasajero', profile_photo_path: null };
    }
};

const getCancellationLabel = (reason) => {
    const labels = {
        changed_mind: 'Cambié de opinión',
        long_wait: 'Mucho tiempo de espera',
        wrong_destination: 'Destino incorrecto',
        found_alternative: 'Encontré otra opción',
        price_too_high: 'Precio muy alto',
        too_far: 'Muy lejos',
        traffic: 'Mucho tráfico',
        passenger_no_response: 'Pasajero no responde',
        wrong_location: 'Ubicación incorrecta',
        vehicle_issue: 'Problema con el vehículo',
        other: 'Otro motivo'
    };
    return labels[reason] || reason;
};
</script>

<template>
    <Head title="Historial de Viajes" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📚 Historial de Viajes
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="trips.length === 0" class="text-center py-16 px-6">
                        <div class="text-6xl mb-4">📭</div>
                        <h3 class="text-xl font-bold text-gray-700 mb-2">No hay viajes en el historial</h3>
                        <p class="text-gray-500">Tus viajes completados y cancelados aparecerán aquí</p>
                    </div>

                    <div v-else>
                        <!-- Grupos por fecha -->
                        <div v-for="(trips, groupName) in groupedTrips" :key="groupName" class="border-b last:border-b-0">
                            <!-- Header del grupo -->
                            <div class="bg-gray-100 px-6 py-3 sticky top-0 z-10">
                                <h3 class="font-bold text-gray-700 text-sm uppercase">{{ groupName }}</h3>
                            </div>

                            <!-- Lista de viajes -->
                            <div class="divide-y">
                                <div 
                                    v-for="trip in trips" 
                                    :key="trip.id"
                                    @click="openTripDetail(trip)"
                                    class="px-6 py-4 hover:bg-gray-50 cursor-pointer transition-colors flex items-center gap-4"
                                >
                                    <!-- Foto de perfil -->
                                    <img 
                                        :src="getOtherPerson(trip).profile_photo_path ? '/storage/'+getOtherPerson(trip).profile_photo_path : 'https://ui-avatars.com/api/?name='+getOtherPerson(trip).name" 
                                        class="w-12 h-12 rounded-full object-cover border-2"
                                        :class="trip.status === 'completed' ? 'border-green-300' : 'border-red-300'"
                                    >

                                    <!-- Información -->
                                    <div class="flex-1 min-w-0">
                                        <p class="font-bold text-gray-900 truncate">{{ getOtherPerson(trip).name }}</p>
                                        <div class="flex gap-3 text-xs text-gray-500 mt-1">
                                            <span>🕐 {{ formatTime(trip.updated_at) }}</span>
                                            <span v-if="trip.distance">📏 {{ trip.distance }} km</span>
                                            <span v-if="trip.duration_minutes">⏱️ {{ formatDuration(trip.duration_minutes) }}</span>
                                        </div>
                                    </div>

                                    <!-- Monto y estado -->
                                    <div class="text-right">
                                        <p class="font-black text-lg" :class="trip.status === 'completed' ? 'text-green-600' : 'text-red-600'">
                                            ${{ trip.price }}
                                        </p>
                                        <span 
                                            class="text-xs px-2 py-1 rounded-full font-semibold"
                                            :class="trip.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                        >
                                            {{ trip.status === 'completed' ? '✓' : '✗' }}
                                        </span>
                                    </div>

                                    <!-- Icono de flecha -->
                                    <div class="text-gray-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalles -->
        <Teleport to="body">
            <Transition leave-active-class="duration-200">
                <div v-show="showDetailModal" class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50">
                    <Transition
                        enter-active-class="ease-out duration-300"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div v-show="showDetailModal" class="fixed inset-0 transform transition-all" @click="closeDetailModal">
                            <div class="absolute inset-0 bg-black opacity-70 backdrop-blur-sm" />
                        </div>
                    </Transition>

                    <Transition
                        enter-active-class="ease-out duration-300"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div
                            v-if="selectedTrip"
                            v-show="showDetailModal"
                            class="mb-6 bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:w-full sm:mx-auto sm:max-w-2xl max-h-[90vh] flex flex-col"
                        >
                            <!-- Header -->
                            <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white flex-shrink-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-2xl font-bold mb-1">Detalles del Viaje</h3>
                                        <p class="text-sm text-blue-200">ID: #{{ selectedTrip.id }}</p>
                                    </div>
                                    <button @click="closeDetailModal" class="text-white hover:bg-white/20 rounded-full p-2 transition">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Contenido -->
                            <div class="p-6 overflow-y-auto flex-1">
                                <!-- Estado y Fecha -->
                                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-xs text-gray-500 mb-1">Estado</p>
                                            <span 
                                                class="px-3 py-1 rounded-full text-sm font-bold"
                                                :class="selectedTrip.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                            >
                                                {{ selectedTrip.status === 'completed' ? '✓ Completado' : '✗ Cancelado' }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500 mb-1">Fecha y Hora</p>
                                            <p class="text-sm font-semibold text-gray-900">{{ formatFullDate(selectedTrip.updated_at) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Persona (Conductor o Pasajero) -->
                                <div class="bg-blue-50 rounded-xl p-4 mb-6">
                                    <p class="text-xs text-gray-600 font-semibold mb-3">{{ userRole === 'passenger' ? 'CONDUCTOR' : 'PASAJERO' }}</p>
                                    <div class="flex items-center gap-4">
                                        <img 
                                            :src="getOtherPerson(selectedTrip).profile_photo_path ? '/storage/'+getOtherPerson(selectedTrip).profile_photo_path : 'https://ui-avatars.com/api/?name='+getOtherPerson(selectedTrip).name" 
                                            class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-md"
                                        >
                                        <div>
                                            <p class="font-bold text-lg text-gray-900">{{ getOtherPerson(selectedTrip).name }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recorrido -->
                                <div class="mb-6">
                                    <p class="text-xs text-gray-600 font-semibold mb-3">RECORRIDO</p>
                                    <div class="space-y-3">
                                        <div class="flex gap-3">
                                            <div class="flex flex-col items-center">
                                                <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                                <div class="w-0.5 h-full bg-gray-300 my-1"></div>
                                            </div>
                                            <div class="flex-1 pb-4">
                                                <p class="text-xs text-gray-500 font-semibold">Origen</p>
                                                <p class="text-sm text-gray-900 font-medium">{{ selectedTrip.origin_address }}</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-3">
                                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                            <div class="flex-1">
                                                <p class="text-xs text-gray-500 font-semibold">Destino</p>
                                                <p class="text-sm text-gray-900 font-medium">{{ selectedTrip.destination_address }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detalles del Viaje -->
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500 mb-1">Vehículo</p>
                                        <p class="font-bold text-gray-900">{{ selectedTrip.vehicle_type === 'car' ? '🚗 Carro' : '🏍️ Moto' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-500 mb-1">Pago</p>
                                        <p class="font-bold text-gray-900">{{ selectedTrip.payment_method }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3" v-if="selectedTrip.distance">
                                        <p class="text-xs text-gray-500 mb-1">Distancia</p>
                                        <p class="font-bold text-gray-900">📏 {{ selectedTrip.distance }} km</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3" v-if="selectedTrip.duration_minutes">
                                        <p class="text-xs text-gray-500 mb-1">Duración</p>
                                        <p class="font-bold text-gray-900">⏱️ {{ formatDuration(selectedTrip.duration_minutes) }}</p>
                                    </div>
                                    <div class="bg-green-50 rounded-lg p-3 col-span-2">
                                        <p class="text-xs text-gray-500 mb-1">Monto Total</p>
                                        <p class="font-black text-3xl text-green-600">${{ selectedTrip.price }}</p>
                                    </div>
                                </div>

                                <!-- Motivo de cancelación si aplica -->
                                <div v-if="selectedTrip.status === 'cancelled' && selectedTrip.cancellation_reason" class="bg-red-50 border-l-4 border-red-400 rounded-lg p-4 mb-6">
                                    <p class="text-xs text-red-700 font-semibold mb-2">MOTIVO DE CANCELACIÓN</p>
                                    <p class="text-sm text-red-900 font-bold">{{ getCancellationLabel(selectedTrip.cancellation_reason) }}</p>
                                    <p class="text-xs text-red-600 mt-2">
                                        Cancelado por: {{ selectedTrip.cancelled_by === 'passenger' ? 'Pasajero' : 'Conductor' }}
                                    </p>
                                </div>

                                <!-- Botones -->
                                <div class="flex gap-3">
                                    <button 
                                        v-if="selectedTrip.status === 'completed'"
                                        @click="openReceipt"
                                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-bold py-3 rounded-xl hover:from-blue-600 hover:to-blue-700 transition shadow-lg"
                                    >
                                        📄 Ver Recibo
                                    </button>
                                    <button 
                                        @click="closeDetailModal"
                                        class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-300 transition"
                                    >
                                        Cerrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <!-- Modal de Recibo -->
        <Teleport to="body">
            <Transition leave-active-class="duration-200">
                <div v-show="showReceiptModal" class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50">
                    <Transition
                        enter-active-class="ease-out duration-300"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div v-show="showReceiptModal" class="fixed inset-0 transform transition-all" @click="closeReceipt">
                            <div class="absolute inset-0 bg-black opacity-70 backdrop-blur-sm" />
                        </div>
                    </Transition>

                    <Transition
                        enter-active-class="ease-out duration-300"
                        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-active-class="ease-in duration-200"
                        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <div
                            v-if="selectedTrip"
                            v-show="showReceiptModal"
                            class="mb-6 bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:w-full sm:mx-auto sm:max-w-md"
                        >
                            <!-- Recibo -->
                            <div id="receipt-content" class="p-8 bg-white">
                                <div class="text-center mb-6 border-b-2 border-dashed pb-6">
                                    <h2 class="text-3xl font-black text-gray-900 mb-2">VECTA MOBILITY</h2>
                                    <p class="text-sm text-gray-600">Recibo de Viaje</p>
                                    <p class="text-xs text-gray-500 mt-2">ID: #{{ selectedTrip.id }}</p>
                                </div>

                                <div class="space-y-4 mb-6">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Fecha:</span>
                                        <span class="font-semibold">{{ formatFullDate(selectedTrip.updated_at) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">{{ userRole === 'passenger' ? 'Conductor:' : 'Pasajero:' }}</span>
                                        <span class="font-semibold">{{ getOtherPerson(selectedTrip).name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Vehículo:</span>
                                        <span class="font-semibold">{{ selectedTrip.vehicle_type === 'car' ? 'Carro' : 'Moto' }}</span>
                                    </div>
                                    <div class="flex justify-between" v-if="selectedTrip.distance">
                                        <span class="text-gray-600">Distancia:</span>
                                        <span class="font-semibold">{{ selectedTrip.distance }} km</span>
                                    </div>
                                    <div class="flex justify-between" v-if="selectedTrip.duration_minutes">
                                        <span class="text-gray-600">Duración:</span>
                                        <span class="font-semibold">{{ formatDuration(selectedTrip.duration_minutes) }}</span>
                                    </div>
                                </div>

                                <div class="border-t-2 border-dashed pt-4 mb-4">
                                    <div class="bg-gray-50 rounded-lg p-3 mb-2">
                                        <p class="text-xs text-gray-600 mb-1">Origen</p>
                                        <p class="text-sm font-medium">{{ selectedTrip.origin_address }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-xs text-gray-600 mb-1">Destino</p>
                                        <p class="text-sm font-medium">{{ selectedTrip.destination_address }}</p>
                                    </div>
                                </div>

                                <div class="border-t-2 border-dashed pt-4 mb-6">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Método de Pago:</span>
                                        <span class="font-semibold">{{ selectedTrip.payment_method }}</span>
                                    </div>
                                    <div class="flex justify-between items-center mt-4 bg-green-50 p-4 rounded-lg">
                                        <span class="text-lg font-bold text-gray-900">TOTAL:</span>
                                        <span class="text-3xl font-black text-green-600">${{ selectedTrip.price }}</span>
                                    </div>
                                </div>

                                <div class="text-center text-xs text-gray-500 border-t-2 border-dashed pt-4">
                                    <p>Gracias por usar Vecta Mobility</p>
                                    <p class="mt-1">¡Que tengas un excelente día!</p>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="px-8 pb-6 flex gap-3">
                                <button 
                                    @click="saveReceiptAsImage"
                                    class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white font-bold py-3 rounded-xl hover:from-green-600 hover:to-green-700 transition shadow-lg"
                                >
                                    💾 Guardar Imagen
                                </button>
                                <button 
                                    @click="closeReceipt"
                                    class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-300 transition"
                                >
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
