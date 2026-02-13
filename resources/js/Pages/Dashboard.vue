<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import { defineAsyncComponent } from 'vue';
import { router } from '@inertiajs/vue3';

import DriverDashboard from '@/Pages/Dashboard/DriverDashboard.vue';
// const DriverDashboard = defineAsyncComponent(() => import('./Dashboard/DriverDashboard.vue'));
const AdminDashboard = defineAsyncComponent(() => import('./Dashboard/AdminDashboard.vue'));
const PassengerDashboard = defineAsyncComponent(() => import('./Dashboard/PassengerDashboard.vue'));

const props = defineProps({
    userRole: String, // 'admin', 'driver', 'passenger'
    // Props específicas que vienen del controller y se pasan a los hijos
    trips: { type: Array, default: () => [] },
    driverLocations: { type: Array, default: () => [] },
    
    availableTrips: { type: Array, default: () => [] },
    myTrips: { type: Array, default: () => [] },
    isApproved: { type: Boolean, default: false },

    currentTrip: { type: Object, default: null },
    pendingActionTrip: { type: Object, default: null } // 🔥 Nueva señal pro-activa
});

const page = usePage();
// Fallback si userRole no viene (aunque debería venir del controller)
const role = props.userRole || page.props.auth.user.role;

// --- POLLING GLOBAL (Opcional, si queremos mantener el refresco automático) ---
// Cada dashboard podría manejar su propio polling, pero tenerlo aquí centralizado también es válido para recargar datos inertes.
let pollingInterval = null;

onMounted(() => {
    pollingInterval = setInterval(() => {
        router.reload({ 
            preserveScroll: true,
            preserveState: true,
            only: ['trips', 'currentTrip', 'availableTrips', 'driverLocations', 'myTrips', 'pendingActionTrip'] // 🔥 Incluir pendingActionTrip
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center">
                <span v-if="role === 'admin'">👮‍♂️ Panel Admin</span>
                <span v-else-if="role === 'driver'">🚖 Conductor</span>
                <span v-else>👋 Pasajero</span>
                
                <!-- ⭐ Calificación en la cabecera (Común para todos menos admin) -->
                <span v-if="role !== 'admin'" class="ml-4 text-sm font-bold bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full border border-yellow-200">
                    ⭐ {{ $page.props.auth.user.average_rating > 0 ? $page.props.auth.user.average_rating : '5.0' }}
                    <span class="text-xs font-normal text-yellow-600 ml-1">({{ $page.props.auth.user.total_ratings }})</span>
                </span>
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <AdminDashboard 
                    v-if="role === 'admin'" 
                    :trips="trips" 
                    :driver-locations="driverLocations" 
                />

                <DriverDashboard 
                    v-if="role === 'driver'" 
                    :available-trips="availableTrips"
                    :my-trips="myTrips"
                    :is-approved="isApproved"
                    :pending-action-trip="pendingActionTrip"
                />

                <PassengerDashboard 
                    v-if="role === 'passenger'"
                    :current-trip="currentTrip"
                    :pending-action-trip="pendingActionTrip"
                    :trips="trips"
                />

            </div>
        </div>
    </AuthenticatedLayout>
</template>