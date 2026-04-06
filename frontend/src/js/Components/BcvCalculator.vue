<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const isOpen  = ref(false);
const dollars = ref('');

/** Tasa BCV oficial compartida globalmente desde el middleware */
const bcvRate = computed(() => page.props.bcv_rate ?? null);

/** Equivalente en Bs. */
const bolivares = computed(() => {
    const amount = parseFloat(dollars.value);
    if (!bcvRate.value || isNaN(amount) || amount <= 0) return null;
    return (amount * bcvRate.value).toLocaleString('es-VE', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
});

const toggle = () => { isOpen.value = !isOpen.value; };
</script>

<template>
    <!-- Widget fijo en esquina inferior derecha -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-2">

        <!-- Panel calculadora (se expande arriba del pill) -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-3 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-3 scale-95"
        >
            <div v-if="isOpen" class="bg-white rounded-2xl shadow-2xl border border-blue-100 w-72 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-[#001F5B] to-[#003580] px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-white font-bold text-sm">🏦 Calculadora BCV</p>
                        <p class="text-blue-300 text-xs">Tasa oficial del día</p>
                    </div>
                    <div v-if="bcvRate" class="bg-white/15 rounded-xl px-3 py-1 text-center">
                        <p class="text-white font-black text-base leading-none">{{ bcvRate.toFixed(2) }}</p>
                        <p class="text-blue-300 text-[10px]">Bs/USD</p>
                    </div>
                </div>

                <div class="p-4 space-y-3">
                    <div v-if="!bcvRate" class="text-center text-yellow-600 text-xs bg-yellow-50 rounded-lg p-2">
                        ⚠️ Tasa BCV no disponible
                    </div>
                    <template v-else>
                        <!-- Input USD -->
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">$</span>
                            <input
                                v-model="dollars"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                autofocus
                                class="w-full pl-7 pr-3 py-2.5 border-2 border-gray-200 rounded-xl text-gray-900 font-bold focus:outline-none focus:border-blue-400 transition text-sm placeholder:font-normal placeholder:text-gray-300"
                            />
                        </div>

                        <div class="text-center text-gray-400 text-xs">↓ equivalente en bolívares</div>

                        <!-- Output Bs -->
                        <div class="bg-blue-50 rounded-xl px-4 py-3 text-center border-2"
                             :class="bolivares ? 'border-blue-200' : 'border-gray-100'">
                            <p class="font-black text-xl"
                               :class="bolivares ? 'text-[#001F5B]' : 'text-gray-300'">
                                Bs. {{ bolivares ?? '0,00' }}
                            </p>
                        </div>

                        <!-- Nota tasa -->
                        <p class="text-center text-[10px] text-gray-400">
                            Tasa BCV oficial: <strong class="text-gray-600">{{ bcvRate.toFixed(2) }} Bs/USD</strong>
                        </p>
                    </template>
                </div>
            </div>
        </Transition>

        <!-- Pill / botón flotante -->
        <button
            @click="toggle"
            class="flex items-center gap-2 px-4 py-2.5 rounded-full shadow-xl font-bold text-sm transition-all duration-200 active:scale-95 hover:shadow-2xl hover:brightness-110"
            :class="isOpen
                ? 'bg-[#80C5DE] text-[#001F5B] ring-2 ring-white ring-offset-2 ring-offset-[#80C5DE]'
                : 'bg-[#80C5DE] text-[#001F5B]'"
        >
            <span class="text-base">🏦</span>
            <span v-if="bcvRate">
                <span class="font-normal text-[#001F5B]/70 text-xs">BCV</span>
                {{ bcvRate.toFixed(2) }} Bs
            </span>
            <span v-else class="text-[#001F5B]/70 text-xs">Tasa BCV</span>
            <span class="text-[#001F5B]/60 text-xs transition-transform duration-200" :class="isOpen ? 'rotate-180' : ''">▲</span>
        </button>
    </div>
</template>

<style scoped>
input[type='number']::-webkit-inner-spin-button,
input[type='number']::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type='number'] { -moz-appearance: textfield; appearance: textfield; }
</style>
