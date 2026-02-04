<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    userRole: { type: String, required: true }, // 'passenger' o 'driver'
    tripId: { type: Number, required: true }
});

const emit = defineEmits(['close', 'confirm']);

const selectedReason = ref('');

// Motivos específicos para pasajeros
const passengerReasons = [
    { id: 'changed_mind', label: 'Cambié de opinión', icon: '🤔' },
    { id: 'long_wait', label: 'Mucho tiempo de espera', icon: '⏰' },
    { id: 'wrong_destination', label: 'Destino incorrecto', icon: '📍' },
    { id: 'found_alternative', label: 'Encontré otra opción', icon: '🚕' },
    { id: 'price_too_high', label: 'Precio muy alto', icon: '💰' },
    { id: 'other', label: 'Otro motivo', icon: '📝' }
];

// Motivos específicos para conductores
const driverReasons = [
    { id: 'too_far', label: 'Muy lejos', icon: '📏' },
    { id: 'traffic', label: 'Mucho tráfico', icon: '🚦' },
    { id: 'passenger_no_response', label: 'Pasajero no responde', icon: '📵' },
    { id: 'wrong_location', label: 'Ubicación incorrecta', icon: '🗺️' },
    { id: 'vehicle_issue', label: 'Problema con el vehículo', icon: '🔧' },
    { id: 'other', label: 'Otro motivo', icon: '📝' }
];

const reasons = props.userRole === 'passenger' ? passengerReasons : driverReasons;

const confirmCancellation = () => {
    if (!selectedReason.value) {
        alert('Por favor selecciona un motivo de cancelación');
        return;
    }
    emit('confirm', selectedReason.value);
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition leave-active-class="duration-200">
            <div v-show="show" class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-50">
                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-show="show" class="fixed inset-0 transform transition-all" @click="close">
                        <div class="absolute inset-0 bg-black opacity-60 backdrop-blur-sm" />
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
                        v-show="show"
                        class="mb-6 bg-white rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:w-full sm:mx-auto sm:max-w-md"
                    >
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-red-500 to-orange-600 p-6 text-white text-center">
                            <div class="text-5xl mb-2">❌</div>
                            <h3 class="text-2xl font-bold">Cancelar Viaje</h3>
                            <p class="text-sm text-red-100 mt-1">Ayúdanos a mejorar</p>
                        </div>

                        <div class="p-6">
                            <p class="text-gray-700 mb-4 text-center">
                                ¿Por qué deseas cancelar este viaje?
                            </p>

                            <!-- Opciones de cancelación -->
                            <div class="space-y-2 mb-6">
                                <div 
                                    v-for="reason in reasons" 
                                    :key="reason.id"
                                    @click="selectedReason = reason.id"
                                    class="cursor-pointer border-2 rounded-xl p-3 flex items-center gap-3 transition-all hover:shadow-md"
                                    :class="selectedReason === reason.id 
                                        ? 'border-red-500 bg-red-50 ring-2 ring-red-200' 
                                        : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <div class="text-2xl">{{ reason.icon }}</div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-800 text-sm">{{ reason.label }}</p>
                                    </div>
                                    <div v-if="selectedReason === reason.id" class="text-red-500 text-xl">✓</div>
                                </div>
                            </div>

                            <!-- Nota informativa -->
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-6 rounded-lg">
                                <p class="text-xs text-yellow-800">
                                    <strong>💡 Nota:</strong> Tu feedback nos ayuda a mejorar el servicio para todos.
                                </p>
                            </div>

                            <!-- Botones -->
                            <div class="flex gap-3">
                                <button 
                                    @click="close"
                                    class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-300 transition"
                                >
                                    Volver
                                </button>
                                <button 
                                    @click="confirmCancellation"
                                    class="flex-1 bg-gradient-to-r from-red-500 to-orange-600 text-white font-bold py-3 rounded-xl hover:from-red-600 hover:to-orange-700 transition shadow-lg"
                                    :class="!selectedReason ? 'opacity-50 cursor-not-allowed' : ''"
                                >
                                    Confirmar
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
