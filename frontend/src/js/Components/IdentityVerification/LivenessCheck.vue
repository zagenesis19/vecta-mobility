<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import * as faceapi from 'face-api.js';

const emit = defineEmits(['complete', 'cancel']);

const videoRef = ref(null);
const canvasRef = ref(null);
const modelsLoaded = ref(false);
const stream = ref(null);
const currentStep = ref('loading'); // loading, detecting, blink, turn, success
const feedbackMessage = ref('Cargando modelos de IA...');
const detectedFace = ref(false);

// Config
const BLINK_THRESHOLD = 0.35; // EAR threshold (Increased to make it easier)
const TURN_THRESHOLD_RATIO = 0.6; // Ratio for head turn

let intervalId = null;

// Load Models
const loadModels = async () => {
    const MODEL_URL = '/models';
    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            // faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL) // Optional
        ]);
        modelsLoaded.value = true;
        startCamera();
    } catch (e) {
        console.error("Error loading models", e);
        feedbackMessage.value = "Error cargando IA. Recarga la página.";
    }
};

const startCamera = async () => {
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ video: {} });
        videoRef.value.srcObject = stream.value;
        
        // Wait for metadata to know real dimensions
        videoRef.value.onloadedmetadata = () => {
            videoRef.value.play();
            
            // Create canvas matching video's real resolution
            const canvas = faceapi.createCanvasFromMedia(videoRef.value);
            canvasRef.value.innerHTML = ''; // Clear previous
            canvasRef.value.append(canvas);
            
            // Match dimensions to the VIDEO ELEMENT's rendered size if possible, 
            // OR best practice: Match to video stream and let CSS scale both.
            // Here we match to the stream resolution.
            const displaySize = { 
                width: videoRef.value.videoWidth, 
                height: videoRef.value.videoHeight 
            };
            faceapi.matchDimensions(canvas, displaySize);
            
            // Ensure canvas scales visually with CSS to match video
            canvas.style.width = '100%';
            canvas.style.height = '100%';

            startDetection(canvas, displaySize);
        };

    } catch (e) {
        feedbackMessage.value = "No se pudo acceder a la cámara.";
    }
};

// Key Logic: Eye Aspect Ratio (EAR)
const getEAR = (eye) => {
    const v1 = Math.hypot(eye[1].x - eye[5].x, eye[1].y - eye[5].y);
    const v2 = Math.hypot(eye[2].x - eye[4].x, eye[2].y - eye[4].y);
    const h = Math.hypot(eye[0].x - eye[3].x, eye[0].y - eye[3].y);
    return (v1 + v2) / (2.0 * h);
};

const startDetection = (canvas, displaySize) => {
    currentStep.value = 'detecting';
    feedbackMessage.value = "Centra tu rostro en la cámara";

    let blinkCounter = 0;
    
    intervalId = setInterval(async () => {
        if (!videoRef.value || !videoRef.value.paused && videoRef.value.ended) return;

        const detections = await faceapi.detectSingleFace(videoRef.value, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks();

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        if (detections) {
            detectedFace.value = true;
            const resizedDetections = faceapi.resizeResults(detections, displaySize);
            
            // Draw landmarks for debug
            faceapi.draw.drawFaceLandmarks(canvas, resizedDetections, { drawLines: true, color: '#00FF00' });

            const landmarks = resizedDetections.landmarks;
            const leftEye = landmarks.getLeftEye();
            const rightEye = landmarks.getRightEye();
            
            const leftEAR = getEAR(leftEye);
            const rightEAR = getEAR(rightEye);
            const avgEAR = (leftEAR + rightEAR) / 2;

            // Debug text
            const ctxText = canvas.getContext('2d');
            ctxText.save();
            ctxText.scale(-1, 1); // Flip text back since canvas is flipped
            ctxText.fillStyle = '#00FF00';
            ctxText.font = 'bold 24px Arial';
            ctxText.fillText(`EAR: ${avgEAR.toFixed(2)}`, -150, 30);
            ctxText.restore();
            
            // LOGIC FLOW
            
            // 1. Waiting for Face Center
            if (currentStep.value === 'detecting') {
                if (detections.detection.score > 0.5) {
                    currentStep.value = 'blink';
                    feedbackMessage.value = "😐 ¡Ahora parpadea lentamente!";
                }
            }
            
            // 2. Blink Challenge
            if (currentStep.value === 'blink') {
                // If eyes closed (Threshold 0.30 - lowered to reduce false positives with glasses)
                if (avgEAR < 0.30) {
                    blinkCounter++;
                    feedbackMessage.value = "😌 ¡Mantén cerrado... (" + blinkCounter + "/3)";
                    if (blinkCounter >= 3) { 
                        currentStep.value = 'turn_left';
                        feedbackMessage.value = "⬅️ Gira tu cabeza a la IZQUIERDA";
                        blinkCounter = 0;
                    }
                } else {
                    blinkCounter = 0; // Reset if eyes open too fast
                    feedbackMessage.value = "😐 ¡Ahora parpadea lentamente!";
                }
            }
            
            // 3. Turn Challenge (Relaxed Thresholds)
            // 3. Turn Left Challenge
            if (currentStep.value === 'turn_left') {
                const nose = landmarks.getNose();
                const jaw = landmarks.getJawOutline();
                const leftJaw = jaw[0];
                const rightJaw = jaw[16];
                
                const noseX = nose[0].x;
                const distToLeft = Math.abs(noseX - leftJaw.x);
                const distToRight = Math.abs(noseX - rightJaw.x);
                
                const totalDist = distToLeft + distToRight;
                const ratio = distToLeft / totalDist;
                
                const ctxText = canvas.getContext('2d');
                ctxText.fillText(`Ratio: ${ratio.toFixed(2)}`, 10, 60);

                // Relaxed Left Threshold: < 0.40 (Easier to reach)
                if (ratio < 0.40) {
                    blinkCounter++; 
                    if (blinkCounter > 5) { // Increased frames to ensure stability
                         currentStep.value = 'turn_right';
                         feedbackMessage.value = "➡️ Ahora gira a la DERECHA";
                         blinkCounter = 0;
                    }
                }
            }

            // 4. Turn Right Challenge
            if (currentStep.value === 'turn_right') {
                const nose = landmarks.getNose();
                const jaw = landmarks.getJawOutline();
                const leftJaw = jaw[0];
                const rightJaw = jaw[16];
                
                const noseX = nose[0].x;
                const distToLeft = Math.abs(noseX - leftJaw.x);
                const distToRight = Math.abs(noseX - rightJaw.x);
                
                const totalDist = distToLeft + distToRight;
                const ratio = distToLeft / totalDist;
                
                const ctxText = canvas.getContext('2d');
                ctxText.fillText(`Ratio: ${ratio.toFixed(2)}`, 10, 60);

                // Relaxed Right Threshold: > 0.60 (Easier to reach)
                if (ratio > 0.60) {
                    blinkCounter++;
                    if (blinkCounter > 5) {
                         completeVerification();
                    }
                }
            }

        } else {
            detectedFace.value = false;
        }
    }, 100);
};

const completeVerification = () => {
    clearInterval(intervalId);
    currentStep.value = 'success';
    feedbackMessage.value = "✅ ¡Verificación Exitosa!";
    
    // Capture final photo - use the original video resolution
    const canvas = document.createElement('canvas');
    canvas.width = videoRef.value.videoWidth;
    canvas.height = videoRef.value.videoHeight;
    canvas.getContext('2d').drawImage(videoRef.value, 0, 0);
    const photoData = canvas.toDataURL('image/png');
    
    setTimeout(() => {
        emit('complete', photoData);
    }, 1000);
};

const onPreCheckComplete = () => {
    currentStep.value = 'loading';
    loadModels();
};

const cancel = () => {
    stopCamera();
    emit('cancel');
};

const stopCamera = () => {
    if (intervalId) clearInterval(intervalId);
    if (stream.value) {
        stream.value.getTracks().forEach(t => t.stop());
    }
};

onMounted(() => {
    currentStep.value = 'pre_check';
    // loadModels(); // Now called after pre-check
});

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <div class="flex flex-col items-center bg-gray-900 text-white p-4 rounded-xl relative overflow-hidden min-h-[500px]">
        
        <!-- Pre-Check Screen -->
        <div v-if="currentStep === 'pre_check'" class="absolute inset-0 z-50 bg-gray-900 flex flex-col items-center justify-center p-6 text-center">
            <h3 class="text-2xl font-bold mb-6 text-yellow-400">⚠️ Antes de Iniciar</h3>
            
            <div class="space-y-6 mb-8">
                <div class="flex items-center gap-4 text-lg">
                    <span class="text-3xl">👓</span>
                    <span>Retira <strong>lentes</strong> o gafas oscuras</span>
                </div>
                <div class="flex items-center gap-4 text-lg">
                    <span class="text-3xl">🧢</span>
                    <span>No uses <strong>gorras</strong> ni sombreros</span>
                </div>
                <div class="flex items-center gap-4 text-lg">
                    <span class="text-3xl">💡</span>
                    <span>Busca buena <strong>iluminación</strong></span>
                </div>
            </div>

            <button @click="onPreCheckComplete" class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-lg font-bold shadow-lg transition-transform transform hover:scale-105">
                Estoy listo y sin accesorios
            </button>
            
            <button @click="cancel" class="mt-4 text-gray-500 hover:text-gray-300">
                Cancelar
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="currentStep === 'loading' && !modelsLoaded" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900 z-40">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mb-4"></div>
            <p>{{ feedbackMessage }}</p>
        </div>

        <!-- Video Feed 
             Removed aspect ratio constraint and object-cover to prevent cropping/misalignment.
             Letting video width drive the container size.
        -->
        <div class="relative w-full max-w-md bg-black rounded-lg overflow-hidden shadow-2xl border-2" 
             :class="detectedFace ? 'border-green-500' : 'border-gray-500'">
            
            <video ref="videoRef" autoplay playsinline muted 
                   class="w-full h-auto transform scale-x-[-1] block"
            ></video>
            
            <div ref="canvasRef" class="absolute inset-0 w-full h-full pointer-events-none transform scale-x-[-1]"></div>
            
            <!-- Instructions Overlay -->
            <div class="absolute bottom-0 left-0 right-0 bg-black/60 p-4 text-center backdrop-blur-sm transition-all duration-300">
                <p class="text-xl font-bold mb-1">{{ feedbackMessage }}</p>
                <div class="flex justify-center gap-1 mt-2">
                    <div class="h-2 w-2 rounded-full" :class="currentStep === 'detecting' ? 'bg-yellow-400' : 'bg-gray-600'"></div>
                    <div class="h-2 w-2 rounded-full" :class="currentStep === 'blink' ? 'bg-yellow-400' : 'bg-gray-600'"></div>
                    <div class="h-2 w-2 rounded-full" :class="['turn_left', 'turn_right'].includes(currentStep) ? 'bg-yellow-400' : 'bg-gray-600'"></div>
                    <div class="h-2 w-2 rounded-full" :class="currentStep === 'success' ? 'bg-green-500' : 'bg-gray-600'"></div>
                </div>
            </div>
        </div>

        <button @click="cancel" class="mt-4 text-gray-400 hover:text-white px-4 py-2">
            Cancelar / Salir
        </button>
    </div>
</template>
