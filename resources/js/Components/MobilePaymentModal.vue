<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    show:      { type: Boolean, default: false },
    tripData:  { type: Object,  required: true },
    userRole:  { type: String,  required: true }, // 'passenger' o 'driver'
    tripCost:  { type: Number,  default: null },   // Costo del viaje en USD
});

const emit = defineEmits(['close', 'confirmPayment']);

const page = usePage();

/** Tasa BCV oficial compartida globalmente desde el middleware de Inertia */
const bcvRate = computed(() => page.props.bcv_rate ?? null);

/** Equivalente en Bolívares, calculado sobre tripCost usando la tasa BCV */
const costInBs = computed(() => {
    if (!bcvRate.value || props.tripCost === null) return null;
    return (props.tripCost * bcvRate.value).toFixed(2);
});

const paymentInfo = {
    cedula:  '31.332.083',
    phone:   '0424-1928802',
    bank:    'Banesco',
    qrImage: '/qr-pago-movil.jpg'
};

const isPassenger = computed(() => props.userRole === 'passenger');
const isDriver    = computed(() => props.userRole === 'driver');

const close          = () => emit('close');
const confirmPayment = () => emit('confirmPayment');
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
                        v-show="show"
                        class="mb-6 bg-white rounded-3xl overflow-hidden shadow-2xl transform transition-all sm:w-full sm:mx-auto sm:max-w-lg max-h-[95vh] flex flex-col"
                    >
                        <!-- Header -->
                        <div class="bg-gradient-to-br from-blue-600 to-blue-800 p-4 text-white text-center flex-shrink-0">
                            <div class="text-4xl mb-1">📱</div>
                            <h3 class="text-xl font-bold">Pago Móvil</h3>
                            <p class="text-xs text-blue-200 mt-1" v-if="isPassenger">
                                Escanea el QR para pagar
                            </p>
                            <p class="text-xs text-blue-200 mt-1" v-else>
                                Espera la confirmación del pago
                            </p>
                        </div>

                        <div class="p-4 overflow-y-auto flex-1">
                            <!-- Monto -->
                            <div class="bg-green-50 border-2 border-green-200 rounded-xl p-3 mb-4 text-center">
                                <p class="text-xs text-green-700 font-semibold mb-1">Monto a {{ isPassenger ? 'pagar' : 'cobrar' }}</p>

                                <!-- Precio USD -->
                                <p class="text-3xl font-black text-green-600">
                                    ${{ tripCost ?? tripData.price }}
                                    <span class="text-base font-semibold text-green-500">USD</span>
                                </p>

                                <!-- Equivalente Bs. (una sola línea sutil) -->
                                <p v-if="costInBs" class="text-sm text-gray-500 mt-1">
                                    ≈ <strong class="text-gray-700">Bs. {{ Number(costInBs).toLocaleString('es-VE', { minimumFractionDigits: 2 }) }}</strong>
                                    <span class="text-xs text-gray-400">(BCV: {{ bcvRate?.toFixed(2) }})</span>
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <!-- QR Code -->
                                <div class="bg-gray-50 rounded-xl p-3 text-center">
                                    <img 
                                        :src="paymentInfo.qrImage" 
                                        alt="QR Pago Móvil" 
                                        class="w-40 h-40 sm:w-48 sm:h-48 mx-auto rounded-lg shadow-md border-2 border-white"
                                    />
                                </div>

                                <!-- Datos de Pago -->
                                <div class="bg-blue-50 rounded-xl p-3 space-y-2 flex flex-col justify-center">
                                    <h4 class="font-bold text-blue-900 text-center text-sm mb-2">📋 Datos de Pago</h4>
                                    
                                    <div class="flex justify-between items-center bg-white rounded-lg p-2">
                                        <span class="text-xs text-gray-600 font-semibold">🏦 Banco:</span>
                                        <span class="font-bold text-gray-900 text-sm">{{ paymentInfo.bank }}</span>
                                    </div>

                                    <div class="flex justify-between items-center bg-white rounded-lg p-2">
                                        <span class="text-xs text-gray-600 font-semibold">📱 Teléfono:</span>
                                        <span class="font-bold text-gray-900 text-sm font-mono">{{ paymentInfo.phone }}</span>
                                    </div>

                                    <div class="flex justify-between items-center bg-white rounded-lg p-2">
                                        <span class="text-xs text-gray-600 font-semibold">🆔 Cédula:</span>
                                        <span class="font-bold text-gray-900 text-sm font-mono">{{ paymentInfo.cedula }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Instrucciones -->
                            <div v-if="isPassenger" class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4 rounded-lg">
                                <p class="text-xs text-yellow-800">
                                    <strong>💡 Instrucción:</strong> Escanea el código QR desde tu app bancaria o usa los datos mostrados.
                                </p>
                            </div>

                            <div v-else class="bg-green-50 border-l-4 border-green-400 p-3 mb-4 rounded-lg">
                                <p class="text-xs text-green-800">
                                    <strong>💡 Instrucción:</strong> Muestra este código QR al pasajero. Confirma cuando recibas la transferencia.
                                </p>
                            </div>

                            <!-- Botones -->
                            <div class="flex gap-2">
                                <button 
                                    v-if="isDriver"
                                    @click="confirmPayment"
                                    class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white font-bold py-3 rounded-xl hover:from-green-600 hover:to-green-700 transition shadow-lg text-sm"
                                >
                                    ✅ Pago Recibido
                                </button>
                                <button 
                                    @click="close"
                                    class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-300 transition text-sm"
                                >
                                    {{ isPassenger ? 'Cerrar' : 'Cancelar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
