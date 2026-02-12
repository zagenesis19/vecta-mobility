<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const user = usePage().props.auth.user;
const form = useForm({
    profile_photo: null, 
    license_file: null,
    id_card_photo: null,
    medical_certificate: null,
    rif_file: null,
    // Endpoint real
    // Nota: Inertia maneja multipart forms automáticamente cuando hay archivos.
});

const documentsUploadedCount = computed(() => {
    let count = 0;
    // Chequear si YA tiene documentos subidos (del backend)
    // Esto es complicado porque no tengo booleanos separados, solo las rutas.
    // Asumiré que si hay path en el user, cuenta.
    if (user.profile_photo_path || form.profile_photo) count++;
    if (user.license_file || form.license_file) count++;
    if (user.id_card_photo_path || form.id_card_photo) count++;
    if (user.medical_certificate_file || form.medical_certificate) count++;
    if (user.rif_file || form.rif_file) count++;
    return count;
});

const uploadPercentage = computed(() => {
    return (documentsUploadedCount.value / 5) * 100;
});

const submitDocuments = () => {
    form.post(route('driver.documents.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Inertia recarga props.auth.user automáticamente al volver,
            // así que los checks "✅ Subido" se actualizarán solos.
            emit('close');
        },
        onError: (err) => {
            console.error(err);
            let msg = "Error al subir:\n";
            Object.values(err).forEach(e => msg += "- " + e + "\n");
            alert(msg);
        }
    });
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
            <div class="fixed inset-0 bg-gray-500 opacity-75" @click="closeModal"></div>
            <div class="bg-white rounded-lg shadow-xl relative z-20 sm:w-full sm:max-w-xl max-h-[90vh] flex flex-col">
                <div class="bg-indigo-600 px-4 py-3 text-white flex justify-between items-center rounded-t-lg">
                    <h3 class="font-bold">📄 Documentos Pendientes</h3>
                    <span class="bg-white/20 px-2 py-1 rounded text-xs">{{ documentsUploadedCount }}/5 Completados</span>
                </div>
                
                <div class="w-full bg-gray-200 h-2">
                    <div class="bg-indigo-500 h-2 transition-all duration-300" :style="{ width: uploadPercentage + '%' }"></div>
                </div>

                <div class="p-6 space-y-4 overflow-y-auto bg-gray-50">
                    <p class="text-sm text-gray-500 mb-4">Solo se permiten archivos de imagen (JPG, PNG) o PDF.</p>

                    <!-- Campos de Archivos -->
                    <!-- 1. Foto Perfil -->
                    <div class="border-b pb-4 bg-white p-4 rounded shadow-sm">
                        <div class="flex justify-between items-center mb-2">
                            <InputLabel value="1. Foto de Perfil" />
                            <span v-if="user.profile_photo_path" class="text-green-600 text-xs font-bold">✅ Subido</span>
                            <span v-else class="text-red-500 text-xs font-bold">❌ Pendiente</span>
                        </div>
                        <input type="file" @change="form.profile_photo = $event.target.files[0]" class="file-input" accept=".jpg,.jpeg,.png" />
                         <InputError :message="form.errors.profile_photo" class="mt-1" />
                    </div>

                    <!-- 2. Licencia -->
                    <div class="border-b pb-4 bg-white p-4 rounded shadow-sm">
                        <div class="flex justify-between items-center mb-2">
                            <InputLabel value="2. Licencia" />
                            <span v-if="user.license_file" class="text-green-600 text-xs font-bold">✅ Subido</span>
                            <span v-else class="text-red-500 text-xs font-bold">❌ Pendiente</span>
                        </div>
                        <input type="file" @change="form.license_file = $event.target.files[0]" class="file-input" accept=".jpg,.jpeg,.png,.pdf" />
                        <InputError :message="form.errors.license_file" class="mt-1" />
                    </div>

                    <!-- 3. Cédula -->
                    <div class="border-b pb-4 bg-white p-4 rounded shadow-sm">
                        <div class="flex justify-between items-center mb-2">
                            <InputLabel value="3. Cédula" />
                            <span v-if="user.id_card_photo_path" class="text-green-600 text-xs font-bold">✅ Subido</span>
                            <span v-else class="text-red-500 text-xs font-bold">❌ Pendiente</span>
                        </div>
                        <input type="file" @change="form.id_card_photo = $event.target.files[0]" class="file-input" accept=".jpg,.jpeg,.png,.pdf" />
                        <InputError :message="form.errors.id_card_photo" class="mt-1" />
                    </div>

                    <!-- 4. Médico -->
                    <div class="border-b pb-4 bg-white p-4 rounded shadow-sm">
                         <div class="flex justify-between items-center mb-2">
                            <InputLabel value="4. Certificado Médico" />
                            <span v-if="user.medical_certificate_file" class="text-green-600 text-xs font-bold">✅ Subido</span>
                            <span v-else class="text-red-500 text-xs font-bold">❌ Pendiente</span>
                        </div>
                        <input type="file" @change="form.medical_certificate = $event.target.files[0]" class="file-input" accept=".jpg,.jpeg,.png,.pdf" />
                        <InputError :message="form.errors.medical_certificate" class="mt-1" />
                    </div>

                    <!-- 5. RIF -->
                    <div class="bg-white p-4 rounded shadow-sm">
                         <div class="flex justify-between items-center mb-2">
                            <InputLabel value="5. RIF" />
                            <span v-if="user.rif_file" class="text-green-600 text-xs font-bold">✅ Subido</span>
                            <span v-else class="text-red-500 text-xs font-bold">❌ Pendiente</span>
                        </div>
                        <input type="file" @change="form.rif_file = $event.target.files[0]" class="file-input" accept=".jpg,.jpeg,.png,.pdf" />
                        <InputError :message="form.errors.rif_file" class="mt-1" />
                    </div>
                </div>

                <div class="bg-gray-100 px-4 py-3 flex justify-end gap-3 rounded-b-lg">
                    <button @click="closeModal" class="text-gray-600">Cancelar</button>
                    <PrimaryButton @click="submitDocuments">Guardar Cambios</PrimaryButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.file-input {
    @apply block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100;
}
</style>
