<script setup>
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import CameraCapture from '@/Components/CameraCapture.vue';
import LivenessCheck from '@/Components/IdentityVerification/LivenessCheck.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'completed']);

const step = ref(1); // 1: Intro, 2: ID Card, 3: Liveness, 4: Review
const idCardImage = ref(null);
const biometricImage = ref(null);

const nextStep = () => step.value++;
const prevStep = () => step.value--;

const handleIdCaptured = (image) => {
    idCardImage.value = image;
};

const handleLivenessComplete = (image) => {
    biometricImage.value = image;
    nextStep(); // Move to review
};

const finish = () => {
    emit('completed', {
        idCard: idCardImage.value,
        biometric: biometricImage.value
    });
    close();
};

const close = () => {
    step.value = 1;
    idCardImage.value = null;
    biometricImage.value = null;
    emit('close');
};

const canProceedFromId = computed(() => !!idCardImage.value);

</script>

<template>
    <Modal :show="show" maxWidth="2xl" @close="close">
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6 border-b pb-4">
                <h2 class="text-xl font-bold text-gray-900">
                    <span v-if="step === 1">Verificación de Identidad</span>
                    <span v-else-if="step === 2">Paso 1: Tu Documento</span>
                    <span v-else-if="step === 3">Paso 2: Prueba de Vida</span>
                    <span v-else-if="step === 4">Confirmación</span>
                </h2>
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                    <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500"
                        :style="`width: ${(step / 4) * 100}%`"></div>
                </div>
            </div>

            <!-- Content -->
            <div class="min-h-[300px]">
                
                <!-- Step 1: Intro -->
                <div v-if="step === 1" class="text-center space-y-4 py-8">
                    <div class="text-6xl mb-4">🛡️</div>
                    <h3 class="text-lg font-bold">Vamos a verificar tu identidad</h3>
                    <p class="text-gray-600 max-w-md mx-auto">
                        Para garantizar la seguridad de la plataforma, necesitamos validar que eres una persona real.
                        Solo tomará unos minutos.
                    </p>
                    <ul class="text-left max-w-xs mx-auto text-sm text-gray-600 space-y-2 bg-gray-50 p-4 rounded-lg">
                        <li>✅ Ten tu Cédula de Identidad a mano.</li>
                        <li>✅ Asegúrate de estar en un lugar iluminado.</li>
                        <li>✅ No uses lentes ni sombreros.</li>
                    </ul>
                </div>

                <!-- Step 2: ID Card -->
                <div v-if="step === 2" class="space-y-4">
                    <p class="text-sm text-gray-500 text-center mb-4">
                        Toma una foto clara de tu <strong>Cédula de Identidad</strong>. Asegúrate de que los textos sean legibles.
                    </p>
                    <div class="flex justify-center">
                        <CameraCapture @photo-captured="handleIdCaptured" />
                    </div>
                </div>

                <!-- Step 3: Liveness -->
                <div v-if="step === 3">
                    <LivenessCheck @complete="handleLivenessComplete" @cancel="close" />
                </div>

                <!-- Step 4: Review -->
                <div v-if="step === 4" class="text-center">
                    <h3 class="text-lg font-bold text-green-600 mb-2">¡Todo listo! 🌟</h3>
                    <p class="text-gray-600 mb-6">Hemos capturado tus datos correctamente.</p>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-xs font-bold mb-1">Tu Documento</p>
                            <img :src="idCardImage" class="w-full h-32 object-contain bg-gray-100 rounded border">
                        </div>
                        <div>
                            <p class="text-xs font-bold mb-1">Tu Biometría</p>
                            <img :src="biometricImage" class="w-full h-32 object-contain bg-gray-100 rounded border">
                        </div>
                    </div>
                </div>

            </div>

            <!-- Footer Buttons -->
            <div class="mt-6 flex justify-between pt-4 border-t">
                <SecondaryButton @click="close">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton v-if="step === 1" @click="nextStep">
                    Comenzar
                </PrimaryButton>

                <div v-if="step === 2" class="flex gap-2">
                    <SecondaryButton @click="prevStep">Atrás</SecondaryButton>
                    <PrimaryButton @click="nextStep" :disabled="!canProceedFromId">
                        Siguiente
                    </PrimaryButton>
                </div>

                <!-- Step 3 (Liveness) handles its own transitions -->

                <PrimaryButton v-if="step === 4" @click="finish" class="bg-green-600 hover:bg-green-700">
                    Confirmar y Enviar
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
