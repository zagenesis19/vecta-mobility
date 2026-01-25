<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage, Link } from '@inertiajs/vue3';

// Recibimos los datos desde el Backend
const props = defineProps({
    availableTrips: {
        type: Array,
        default: () => [] 
    },
    userRole: String 
});

const page = usePage();
// Detectamos el rol (si no viene en props, lo sacamos del usuario autenticado)
const currentUserRole = props.userRole || page.props.auth.user.role;

// Función para Aceptar Viaje (Conductores)
const acceptTrip = (tripId) => {
    if (confirm('¿Confirmas que quieres tomar este viaje?')) {
        router.put(route('trip.accept', tripId), {}, {
            onSuccess: () => alert('✅ ¡Viaje asignado! Ve por el pasajero.'),
            onError: () => alert('❌ El viaje ya no está disponible.')
        });
    }
};

// Función temporal para el botón de Pasajero (Evita el error de pantalla blanca)
const startNewTrip = () => {
    alert("¡Botón funcionando! 🚧 Aquí abriremos el formulario de viaje en la Fase 4.");
    // Cuando creemos la ruta en el backend, cambiaremos esto por:
    // router.visit(route('trips.create'));
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ currentUserRole === 'driver' ? '🚖 Panel de Conductor' : '👋 Panel de Pasajero' }}
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div v-if="currentUserRole === 'driver'" class="space-y-8">
                    
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
                        <div class="absolute w-64 h-64 bg-blue-100 rounded-full animate-pulse delay-75 opacity-60"></div>
                        
                        <div class="z-10 text-center relative">
                            <div class="bg-white p-4 rounded-full shadow-lg inline-block mb-4">
                                <span class="text-4xl">📡</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Escaneando zona...</h3>
                            <p class="text-gray-500 mt-2">Te avisaremos cuando alguien pida un viaje cerca.</p>
                            <p class="text-xs text-gray-400 mt-4 animate-bounce">Esperando solicitud...</p>
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
                            
                            <button 
                                @click="startNewTrip"
                                class="w-full bg-white text-black font-bold text-center py-4 rounded-xl hover:bg-gray-100 transition shadow-lg transform hover:scale-[1.01] flex justify-center items-center gap-2"
                            >
                                🔍  Ingresar destino
                            </button>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3 cursor-pointer hover:bg-gray-50 transition">
                                <div class="bg-blue-100 p-2 rounded-lg text-xl">🏠</div>
                                <div>
                                    <p class="font-bold text-gray-800">Casa</p>
                                    <p class="text-xs text-gray-500">Guardar dirección</p>
                                </div>
                            </div>
                            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-3 cursor-pointer hover:bg-gray-50 transition">
                                <div class="bg-orange-100 p-2 rounded-lg text-xl">💼</div>
                                <div>
                                    <p class="font-bold text-gray-800">Trabajo</p>
                                    <p class="text-xs text-gray-500">Guardar dirección</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 h-full flex flex-col min-h-[300px]">
                        <h3 class="font-bold text-gray-800 mb-4">Mapa en vivo</h3>
                        <div class="flex-1 bg-blue-50 rounded-xl relative overflow-hidden flex items-center justify-center border border-blue-100">
                            <div class="absolute inset-0 opacity-30 bg-[url('https://www.transparenttextures.com/patterns/map-marker.png')]"></div>
                            <div class="text-center z-10">
                                <span class="text-4xl block mb-2">🗺️</span>
                                <span class="text-sm text-gray-500 font-medium">Ubicación Actual</span>
                            </div>
                            <div class="absolute top-1/4 left-1/4 w-3 h-3 bg-black rounded-full animate-bounce"></div>
                            <div class="absolute bottom-1/3 right-1/4 w-3 h-3 bg-black rounded-full animate-bounce delay-150"></div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>