<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Formulario
const form = useForm({
    origin_address: '',
    origin_lat: null,
    origin_long: null,
    dest_address: '',
    dest_lat: null,
    dest_long: null,
});

const originSuggestions = ref([]);
const destSuggestions = ref([]);
const isLocating = ref(false);

// Variable para el "freno" (Timer)
let debounceTimer = null;

// 1. FUNCIÓN DE BÚSQUEDA INTELIGENTE (Con Debounce)
const searchAddress = (query, type) => {
    // Si escribes menos de 3 letras, no buscamos nada
    if (query.length < 3) return;

    // Si ya había una búsqueda pendiente, la cancelamos (Reiniciamos el reloj)
    clearTimeout(debounceTimer);

    // Esperamos 1 segundo (1000ms) antes de buscar
    debounceTimer = setTimeout(async () => {
        // Limitamos a Venezuela y pedimos respuesta en JSON
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=ve&limit=5&addressdetails=1`;
        
        try {
            const response = await fetch(url, {
                headers: {
                    'User-Agent': 'VectaMobilityProject/1.0' // Es bueno identificarse
                }
            });
            
            if (!response.ok) throw new Error("Error en API");

            const data = await response.json();
            
            if (type === 'origin') {
                originSuggestions.value = data;
            } else {
                destSuggestions.value = data;
            }
        } catch (error) {
            console.error("Esperando desbloqueo de API...", error);
        }
    }, 1000); // <--- TIEMPO DE ESPERA
};

// 2. SELECCIONAR DIRECCIÓN
const selectAddress = (item, type) => {
    // Formateamos el nombre para que se vea bonito (Solo calle y ciudad)
    const shortName = item.display_name.split(',').slice(0, 3).join(',');

    if (type === 'origin') {
        form.origin_address = shortName;
        form.origin_lat = item.lat;
        form.origin_long = item.lon;
        originSuggestions.value = []; // Limpiar lista
    } else {
        form.dest_address = shortName;
        form.dest_lat = item.lat;
        form.dest_long = item.lon;
        destSuggestions.value = [];
    }
};

// 3. GPS (Tu ubicación)
const getMyLocation = () => {
    if (!navigator.geolocation) return alert("Navegador no compatible");
    isLocating.value = true;

    navigator.geolocation.getCurrentPosition(async (pos) => {
        const { latitude, longitude } = pos.coords;
        form.origin_lat = latitude;
        form.origin_long = longitude;
        
        // Convertir Coordenadas -> Texto (Reverse Geocoding)
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
            const data = await res.json();
            form.origin_address = data.display_name.split(',').slice(0, 2).join(',') || "Mi Ubicación Exacta";
        } catch (e) {
            form.origin_address = `GPS: ${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
        }
        isLocating.value = false;
    }, () => {
        alert("No pudimos acceder al GPS. Revisa los permisos.");
        isLocating.value = false;
    });
};

const submit = () => {
    if (!form.origin_lat || !form.dest_lat) {
        alert("Por favor selecciona direcciones de la lista desplegable.");
        return;
    }
    form.post(route('trip.store'));
};
</script>

<template>
    <Head title="Pedir Viaje" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-gray-100 py-6 flex justify-center items-start">
            <div class="w-full max-w-md bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
                
                <div class="bg-black p-8 text-white relative overflow-hidden">
                    <div class="absolute -right-10 -top-10 bg-gray-800 rounded-full w-40 h-40 opacity-20"></div>
                    <h2 class="text-2xl font-bold relative z-10">Vecta Mobility</h2>
                    <p class="text-sm text-gray-400 relative z-10">Solicitud de Transporte Inteligente</p>
                </div>

                <div class="p-8 space-y-6">
                    <div class="relative group">
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">Punto de Partida</label>
                        <div class="flex items-center bg-gray-50 rounded-xl border border-gray-200 focus-within:border-black focus-within:ring-1 focus-within:ring-black transition-all">
                            <span class="pl-4 text-green-500 text-xl">●</span>
                            <input 
                                v-model="form.origin_address" 
                                @input="searchAddress($event.target.value, 'origin')"
                                type="text" 
                                class="w-full bg-transparent border-none focus:ring-0 text-sm py-4 text-gray-700 font-medium placeholder-gray-400" 
                                placeholder="¿Dónde estás?"
                            >
                            <button @click="getMyLocation" type="button" class="pr-4 text-gray-400 hover:text-black transition">
                                <span v-if="isLocating" class="animate-spin">⌛</span>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>
                        
                        <ul v-if="originSuggestions.length" class="absolute z-50 left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border border-gray-100 max-h-60 overflow-y-auto">
                            <li v-for="item in originSuggestions" :key="item.place_id" @click="selectAddress(item, 'origin')" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b last:border-0 flex items-start gap-3 transition">
                                <span class="text-green-500 mt-1">📍</span>
                                <span class="text-xs text-gray-600 leading-snug">{{ item.display_name }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="ml-5 border-l-2 border-dashed border-gray-200 h-6"></div>

                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase tracking-wide">Destino</label>
                        <div class="flex items-center bg-gray-50 rounded-xl border border-gray-200 focus-within:border-black focus-within:ring-1 focus-within:ring-black transition-all">
                            <span class="pl-4 text-red-500 text-xl">●</span>
                            <input 
                                v-model="form.dest_address" 
                                @input="searchAddress($event.target.value, 'dest')"
                                type="text" 
                                class="w-full bg-transparent border-none focus:ring-0 text-sm py-4 text-gray-700 font-medium placeholder-gray-400" 
                                placeholder="Ej. Centro Comercial..."
                            >
                        </div>

                        <ul v-if="destSuggestions.length" class="absolute z-50 left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border border-gray-100 max-h-60 overflow-y-auto">
                            <li v-for="item in destSuggestions" :key="item.place_id" @click="selectAddress(item, 'dest')" class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b last:border-0 flex items-start gap-3 transition">
                                <span class="text-red-500 mt-1">🏁</span>
                                <span class="text-xs text-gray-600 leading-snug">{{ item.display_name }}</span>
                            </li>
                        </ul>
                    </div>

                    <button 
                        @click="submit" 
                        :disabled="form.processing"
                        class="w-full bg-black text-white font-bold py-4 rounded-xl shadow-lg hover:bg-gray-800 transition transform active:scale-[0.98] mt-4 flex justify-center items-center gap-2"
                    >
                        <span v-if="form.processing">Calculando Tarifa...</span>
                        <span v-else>Confirmar Ruta</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    
                    <p class="text-center text-[10px] text-gray-400 mt-2">
                        Tarifas calculadas con GPS en tiempo real.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>