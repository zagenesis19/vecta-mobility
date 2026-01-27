<script setup>
import { ref, onUnmounted } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

// Evento para enviar la foto al padre
const emit = defineEmits(['photo-captured']);

const videoRef = ref(null);
const canvasRef = ref(null);
const isCameraOpen = ref(false);
const photoData = ref(null);
const stream = ref(null);

// 1. Abrir Cámara
const startCamera = async () => {
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'user' }, // 'user' para selfie, 'environment' para trasera
            audio: false 
        });
        videoRef.value.srcObject = stream.value;
        isCameraOpen.value = true;
    } catch (error) {
        alert("No se pudo acceder a la cámara: " + error.message);
    }
};

// 2. Tomar Foto
const takePhoto = () => {
    const video = videoRef.value;
    const canvas = canvasRef.value;
    
    // Configurar tamaño del canvas igual al video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Dibujar el frame actual del video en el canvas
    const context = canvas.getContext('2d');
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Convertir a DataURL (base64)
    photoData.value = canvas.toDataURL('image/png');
    
    // Emitir al componente padre
    emit('photo-captured', photoData.value);
    
    stopCamera(); // Opcional: Cerrar cámara al tomar la foto
};

// 3. Cerrar Cámara
const stopCamera = () => {
    if (stream.value) {
        const tracks = stream.value.getTracks();
        tracks.forEach(track => track.stop());
        isCameraOpen.value = false;
    }
};

// Asegurar que se apague si el usuario sale de la página
onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <div class="flex flex-col items-center space-y-4">
        <div v-show="isCameraOpen" class="relative w-full max-w-sm overflow-hidden rounded-lg shadow-lg bg-black">
            <video ref="videoRef" autoplay playsinline class="w-full h-64 object-cover transform scale-x-[-1]"></video> </div>

        <div v-if="photoData && !isCameraOpen" class="w-full max-w-sm rounded-lg shadow-lg overflow-hidden">
             <img :src="photoData" alt="Captura" class="w-full h-64 object-cover" />
        </div>
        
        <div class="flex gap-2">
            <PrimaryButton v-if="!isCameraOpen" @click.prevent="startCamera" type="button">
                📸 Activar Cámara
            </PrimaryButton>
            
            <PrimaryButton v-if="isCameraOpen" @click.prevent="takePhoto" type="button" class="bg-green-600 hover:bg-green-700">
                ⚡ Capturar
            </PrimaryButton>
            
            <DangerButton v-if="isCameraOpen" @click.prevent="stopCamera" type="button">
                Cancelar
            </DangerButton>
            
            <SecondaryButton v-if="photoData && !isCameraOpen" @click.prevent="photoData = null; startCamera()" type="button">
                Intentar de nuevo
            </SecondaryButton>
        </div>
        
        <canvas ref="canvasRef" class="hidden"></canvas>
    </div>
</template>