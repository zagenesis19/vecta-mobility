<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    tripId: { type: Number, required: true }
});

const emit = defineEmits(['close', 'confirm']);

const selectedReason = ref('');

const rejectionOptions = [
    { id: 'too_far', label: 'Distancia muy larga', icon: '📏' },
    { id: 'dangerous_zone', label: 'Zona peligrosa', icon: '💀' },
    { id: 'low_fare', label: 'Tarifa muy baja', icon: '💰' },
    { id: 'already_has_passenger', label: 'Ya tengo pasajero', icon: '🚕' },
    { id: 'wrong_vehicle', label: 'Vehículo no apto', icon: '🔧' },
    { id: 'other', label: 'Otro motivo', icon: '📝' }
];

const confirmRejection = () => {
    if (!selectedReason.value) {
        alert('Por favor selecciona un motivo de rechazo');
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
                        <div class="bg-gradient-to-r from-gray-800 to-gray-900 p-6 text-white text-center">
                            <div class="text-5xl mb-2">🚫</div>
                            <h3 class="text-2xl font-bold">Rechazar Viaje</h3>
                            <p class="text-sm text-gray-400 mt-1">Ayúdanos a mejorar el emparejamiento</p>
                        </div>

                        <div class="p-6">
                            <p class="text-gray-700 mb-4 text-center">
                                ¿Por qué no deseas tomar este viaje?
                            </p>

                            <!-- Opciones -->
                            <div class="grid grid-cols-1 gap-2 mb-6">
                                <div 
                                    v-for="option in rejectionOptions" 
                                    :key="option.id"
                                    @click="selectedReason = option.label"
                                    class="cursor-pointer border-2 rounded-xl p-3 flex items-center gap-3 transition-all hover:border-indigo-200 hover:bg-indigo-50"
                                    :class="selectedReason === option.label 
                                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200' 
                                        : 'border-gray-100 hover:shadow-sm'"
                                >
                                    <div class="text-2xl">{{ option.icon }}</div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-800 text-sm">{{ option.label }}</p>
                                    </div>
                                    <div v-if="selectedReason === option.label" class="text-indigo-500 text-xl">✓</div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex gap-3">
                                <button 
                                    @click="close"
                                    class="flex-1 bg-gray-100 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-200 transition"
                                >
                                    Volver
                                </button>
                                <button 
                                    @click="confirmRejection"
                                    class="flex-1 bg-black text-white font-bold py-3 rounded-xl hover:bg-gray-800 transition shadow-lg"
                                    :class="!selectedReason ? 'opacity-50 cursor-not-allowed' : ''"
                                >
                                    Rechazar
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
