<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    trip: Object,
    userRole: String
});

const emit = defineEmits(['close']);
const rating = ref(0);
const hoverRating = ref(0);

const form = useForm({
    rating: null,
    comment: ''
});

const submit = () => {
    // Validación de seguridad extra
    if (rating.value === 0) return;
    
    form.rating = rating.value;
    
    form.post(route('trip.rate', props.trip.id), {
        onSuccess: () => {
            emit('close'); 
        }
    });
};
</script>

<template>
    <div class="fixed inset-0 bg-black/80 flex items-center justify-center z-[100] p-4 backdrop-blur-md">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm text-center border-4 border-yellow-400">
            
            <div class="text-4xl mb-2 animate-bounce">⭐</div>
            
            <h3 class="text-xl font-bold text-gray-800 mb-2">
                {{ userRole === 'driver' ? 'Califica al Pasajero' : 'Califica a tu Conductor' }}
            </h3>
            
            <p class="text-sm text-gray-600 mb-6">
                Tu opinión es obligatoria para mantener la seguridad en la comunidad.
            </p>

            <div class="flex justify-center gap-2 mb-4" @mouseleave="hoverRating = 0">
                <button 
                    v-for="star in 5" 
                    :key="star"
                    @mouseover="hoverRating = star"
                    @click="rating = star"
                    type="button"
                    class="text-5xl transition-transform hover:scale-125 focus:outline-none"
                    :class="(hoverRating || rating) >= star ? 'text-yellow-400' : 'text-gray-300'"
                >
                    ★
                </button>
            </div>
            
            <div class="text-xs font-bold text-yellow-600 mb-6 h-4 uppercase tracking-widest">
                <span v-if="rating === 0" class="text-red-500 italic">Selecciona una puntuación</span>
                <span v-else-if="hoverRating === 1 || rating === 1">Malo 😡</span>
                <span v-else-if="hoverRating === 2 || rating === 2">Regular 😐</span>
                <span v-else-if="hoverRating === 3 || rating === 3">Bueno 🙂</span>
                <span v-else-if="hoverRating === 4 || rating === 4">Muy Bueno 😄</span>
                <span v-else-if="hoverRating === 5 || rating === 5">¡Excelente! 🤩</span>
            </div>

            <textarea 
                v-model="form.comment" 
                placeholder="Escribe un comentario (opcional)..." 
                class="w-full border-gray-300 rounded-lg text-sm mb-6 focus:ring-yellow-500 focus:border-yellow-500"
                rows="3"
            ></textarea>

            <div class="flex gap-2">
                <button 
                    @click="submit" 
                    :disabled="form.processing || rating === 0"
                    class="w-full font-bold py-4 rounded-xl transition shadow-lg transform active:scale-95"
                    :class="rating > 0 
                        ? 'bg-black text-white hover:bg-gray-800' 
                        : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                >
                    <span v-if="form.processing">Enviando...</span>
                    <span v-else>Confirmar Calificación</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Evitamos que el usuario pueda usar el teclado para cerrar (Escape) */
.animate-bounce-in { animation: bounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
@keyframes bounceIn {
    0% { opacity: 0; transform: scale(0.3); }
    100% { opacity: 1; transform: scale(1); }
}
</style>