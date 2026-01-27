<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    users: Array,
});

const showingModal = ref(false);
const selectedUser = ref(null);
const rejectReason = ref('');

// Función para limpiar la fecha (quita la hora)
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    // Toma solo los primeros 10 caracteres (YYYY-MM-DD)
    return dateString.substring(0, 10);
};

const inspectUser = (user) => {
    selectedUser.value = user;
    rejectReason.value = '';
    showingModal.value = true;
};

const closeModal = () => {
    showingModal.value = false;
    selectedUser.value = null;
};

const form = useForm({});

const approve = () => {
    if (!confirm('¿Estás seguro de APROBAR esta identidad?')) return;
    form.post(route('admin.verifications.approve', selectedUser.value.id), {
        onSuccess: () => closeModal(),
    });
};

const reject = () => {
    if (!rejectReason.value) return alert('Debes escribir una razón para rechazar.');
    const rejectForm = useForm({ reason: rejectReason.value });
    rejectForm.post(route('admin.verifications.reject', selectedUser.value.id), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head title="Verificaciones de Identidad" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">👮 Centro de Verificaciones</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-bold mb-4">Solicitudes Pendientes ({{ users.length }})</h3>

                        <div v-if="users.length === 0" class="text-center py-10 text-gray-500">
                            🎉 No hay solicitudes pendientes. ¡Todo al día!
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Solicitud</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in users" :key="user.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full object-cover" :src="user.profile_photo_path ? '/storage/' + user.profile_photo_path : 'https://ui-avatars.com/api/?name='+user.name" />
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ user.id_card_number || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(user.updated_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="inspectUser(user)" class="text-indigo-600 hover:text-indigo-900 font-bold">
                                                🔍 Inspeccionar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showingModal" @close="closeModal">
            <div class="p-6" v-if="selectedUser">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    Auditoría de Identidad: {{ selectedUser.name }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-3 rounded border">
                            <span class="block text-xs text-gray-500 uppercase">Cédula Declarada</span>
                            <span class="text-lg font-bold">{{ selectedUser.id_card_number }}</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded border">
                            <span class="block text-xs text-gray-500 uppercase">Fecha Nacimiento</span>
                            <span class="text-lg font-bold">{{ formatDate(selectedUser.birth_date) }}</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded border">
                            <span class="block text-xs text-gray-500 uppercase">Teléfono</span>
                            <span class="text-lg font-bold">{{ selectedUser.phone_number }}</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded border">
                            <span class="block text-xs text-gray-500 uppercase">Vencimiento Documento</span>
                            <span :class="{'text-red-600 font-bold': new Date(selectedUser.id_card_expires_at) < new Date(), 'text-green-600 font-bold': new Date(selectedUser.id_card_expires_at) >= new Date()}">
                                {{ formatDate(selectedUser.id_card_expires_at) }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <span class="block text-xs text-gray-500 uppercase mb-1">Foto Documento (Cédula)</span>
                            <div class="border rounded p-1 bg-gray-100 flex justify-center">
                                <img :src="'/storage/' + selectedUser.id_card_photo_path" class="h-40 object-contain cursor-pointer hover:scale-105 transition" onclick="window.open(this.src)" title="Click para ampliar">
                            </div>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500 uppercase mb-1">Biometría (Selfie)</span>
                            <div class="border rounded p-1 bg-gray-100 flex justify-center">
                                <img :src="'/storage/' + selectedUser.biometric_photo_path" class="h-40 object-contain cursor-pointer hover:scale-105 transition" onclick="window.open(this.src)" title="Click para ampliar">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-bold text-gray-900 mb-2">Veredicto del Admin</h3>
                    
                    <div class="flex flex-col md:flex-row gap-4">
                        <PrimaryButton @click="approve" class="bg-green-600 hover:bg-green-700 justify-center w-full md:w-auto h-12">
                            ✅ APROBAR IDENTIDAD
                        </PrimaryButton>

                        <div class="flex-1 flex gap-2">
                            <TextInput 
                                v-model="rejectReason" 
                                placeholder="Razón del rechazo (Ej: Foto borrosa...)" 
                                class="w-full"
                            />
                            <DangerButton @click="reject" class="h-12 whitespace-nowrap">
                                ❌ Rechazar
                            </DangerButton>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cerrar / Cancelar </SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>