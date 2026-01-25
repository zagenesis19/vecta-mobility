<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

// Estado del formulario
const form = useForm({
    origin: '',
    destination: '',
});

// Variables para el autocompletado
const suggestions = ref([]);     // Lista de direcciones encontradas
const activeField = ref(null);   // Qué campo se está editando ('origin' o 'destination')
const isLoadingLocation = ref(false); // Para mostrar "Cargando..." en el botón GPS
let debounceTimeout = null;      // Para no saturar la API al escribir

// --- 1. FUNCIÓN PARA OBTENER TU UBICACIÓN (GPS) ---
const getMyLocation = () => {
    if (!navigator.geolocation) {
        alert("Tu navegador no soporta geolocalización.");
        return;
    }

    isLoadingLocation.value = true;

    navigator.geolocation.getCurrentPosition(async (position) => {
        const { latitude, longitude } = position.coords;
        
        // Convertimos Coordenadas -> Texto (Geocodificación Inversa)
        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
            const data = await response.json();
            form.origin = data.display_name; // Rellenamos el campo Origen
        } catch (error) {
            alert("Error al obtener la dirección.");
        } finally {
            isLoadingLocation.value = false;
        }
    }, () => {
        alert("No pudimos acceder a tu ubicación. Revisa los permisos.");
        isLoadingLocation.value = false;
    });
};

// --- 2. FUNCIÓN DE AUTOCOMPLETADO (Buscador) ---
const searchAddress = (event, field) => {
    const query = event.target.value;
    activeField.value = field; // Recordamos qué input estamos escribiendo

    // Limpiamos sugerencias si está vacío
    if (query.length < 3) {
        suggestions.value = [];
        return;
    }

    // "Debounce": Esperamos 300ms a que termines de escribir para buscar
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(async () => {
        try {
            // Buscamos en OpenStreetMap (Limitado a Venezuela para ser más preciso, o general)
            // countrycodes=ve limita a Venezuela. Quítalo si quieres buscar en todo el mundo.
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=5&countrycodes=ve`);
            suggestions.value = await response.json();
        } catch (error) {
            console.error("Error buscando direcciones", error);
        }
    }, 300);
};

// --- 3. SELECCIONAR UNA SUGERENCIA ---
const selectSuggestion = (place) => {
    if (activeField.value === 'origin') {
        form.origin = place.display_name;
    } else {
        form.destination = place.display_name;
    }
    suggestions.value = []; // Ocultar lista
};

// --- ENVIAR FORMULARIO ---
const submit = () => {
    form.post(route('trips.store'), {
        onSuccess: () => alert('¡Solicitud enviada! Buscando conductor...'),
    });
};
</script>

<template>
    <Head title="Pedir un Viaje" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📍 Nuevo Viaje
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen" @click="suggestions = []">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-visible shadow-xl sm:rounded-2xl border border-gray-100 relative">
                    
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">¿A dónde te llevo?</h3>

                        <form @submit.prevent="submit" class="space-y-6 relative">
                            
                            <div class="relative">
                                <label class="block font-medium text-sm text-gray-700 mb-1">Punto de Recogida</label>
                                <div class="relative flex items-center">
                                    <div class="absolute left-3 text-blue-500">🔵</div>
                                    
                                    <input 
                                        v-model="form.origin"
                                        @input="searchAddress($event, 'origin')"
                                        type="text" 
                                        placeholder="Buscar dirección..."
                                        class="pl-10 pr-12 w-full border-gray-300 focus:border-black focus:ring-black rounded-lg shadow-sm"
                                        required
                                        autocomplete="off"
                                    >

                                    <button 
                                        type="button"
                                        @click="getMyLocation"
                                        class="absolute right-2 text-gray-400 hover:text-blue-600 p-1"
                                        title="Usar mi ubicación actual"
                                    >
                                        <span v-if="isLoadingLocation" class="animate-spin">🔄</span>
                                        <span v-else>📍</span>
                                    </button>
                                </div>

                                <ul v-if="suggestions.length > 0 && activeField === 'origin'" class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto">
                                    <li 
                                        v-for="place in suggestions" 
                                        :key="place.place_id"
                                        @click.stop="selectSuggestion(place)"
                                        class="px-4 py-3 hover:bg-gray-100 cursor-pointer text-sm border-b last:border-0 flex items-start gap-2"
                                    >
                                        <span>📍</span> {{ place.display_name }}
                                    </li>
                                </ul>
                            </div>

                            <div class="relative">
                                <label class="block font-medium text-sm text-gray-700 mb-1">Destino Final</label>
                                <div class="relative">
                                    <div class="absolute left-3 text-red-500">🚩</div>
                                    <input 
                                        v-model="form.destination"
                                        @input="searchAddress($event, 'destination')"
                                        type="text" 
                                        placeholder="Ej: Centro Comercial..."
                                        class="pl-10 w-full border-gray-300 focus:border-black focus:ring-black rounded-lg shadow-sm"
                                        required
                                        autocomplete="off"
                                    >
                                </div>

                                <ul v-if="suggestions.length > 0 && activeField === 'destination'" class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto">
                                    <li 
                                        v-for="place in suggestions" 
                                        :key="place.place_id"
                                        @click.stop="selectSuggestion(place)"
                                        class="px-4 py-3 hover:bg-gray-100 cursor-pointer text-sm border-b last:border-0 flex items-start gap-2"
                                    >
                                        <span>🏁</span> {{ place.display_name }}
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center border border-gray-200">
                                <span class="text-gray-500 text-sm">Tarifa estimada</span>
                                <span class="font-bold text-xl text-green-600">$5.00 - $8.00</span>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="w-full bg-black text-white font-bold py-4 rounded-xl hover:bg-gray-800 transition shadow-lg flex justify-center items-center gap-2"
                            >
                                <span v-if="form.processing">Enviando...</span>
                                <span v-else>✅ Confirmar Solicitud</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>