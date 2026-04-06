<script setup>
import { ref } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    tripData: { type: Object, required: true }
});

const emit = defineEmits(['close', 'confirm']);

const selectedMethod = ref('Efectivo');

const paymentMethods = [
    { id: 'Efectivo', label: 'Efectivo', icon: '💵', color: 'green' },
    { id: 'Pago Móvil', label: 'Pago Móvil', icon: '📱', color: 'blue' },
    { id: 'Tarjeta', label: 'Tarjeta', icon: '💳', color: 'purple' }
];

const confirmPaymentMethod = () => {
    emit('confirm', selectedMethod.value);
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
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-6 text-white">
                            <h3 class="text-2xl font-bold">💳 Método de Pago</h3>
                            <p class="text-sm text-blue-100 mt-1">Selecciona cómo deseas pagar</p>
                        </div>

                        <div class="p-6">
                            <div class="bg-gray-50 rounded-xl p-4 mb-6">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Monto a pagar</span>
                                    <span class="text-2xl font-black text-green-600">${{ tripData.price }}</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div 
                                    v-for="method in paymentMethods" 
                                    :key="method.id"
                                    @click="selectedMethod = method.id"
                                    class="cursor-pointer border-2 rounded-xl p-4 flex items-center gap-4 transition-all hover:shadow-md"
                                    :class="selectedMethod === method.id 
                                        ? `border-${method.color}-500 bg-${method.color}-50 ring-2 ring-${method.color}-200` 
                                        : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <div class="text-4xl">{{ method.icon }}</div>
                                    <div class="flex-1">
                                        <p class="font-bold text-gray-800">{{ method.label }}</p>
                                        <p class="text-xs text-gray-500" v-if="method.id === 'Pago Móvil'">
                                            Escanea el QR al finalizar
                                        </p>
                                        <p class="text-xs text-gray-500" v-else-if="method.id === 'Efectivo'">
                                            Paga directamente al conductor
                                        </p>
                                        <p class="text-xs text-gray-500" v-else>
                                            Pago con tarjeta de débito/crédito
                                        </p>
                                    </div>
                                    <div v-if="selectedMethod === method.id" class="text-green-500 text-2xl">✓</div>
                                </div>
                            </div>

                            <div class="flex gap-3 mt-6">
                                <button 
                                    @click="close"
                                    class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-300 transition"
                                >
                                    Cancelar
                                </button>
                                <button 
                                    @click="confirmPaymentMethod"
                                    class="flex-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold py-3 rounded-xl hover:from-blue-600 hover:to-purple-700 transition shadow-lg"
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

<style scoped>
/* Tailwind classes dinámicas necesitan estar en el safelist o usar clases completas */
.border-green-500 { border-color: rgb(34 197 94); }
.bg-green-50 { background-color: rgb(240 253 244); }
.ring-green-200 { --tw-ring-color: rgb(187 247 208); }
.border-blue-500 { border-color: rgb(59 130 246); }
.bg-blue-50 { background-color: rgb(239 246 255); }
.ring-blue-200 { --tw-ring-color: rgb(191 219 254); }
.border-purple-500 { border-color: rgb(168 85 247); }
.bg-purple-50 { background-color: rgb(250 245 255); }
.ring-purple-200 { --tw-ring-color: rgb(233 213 255); }
</style>
