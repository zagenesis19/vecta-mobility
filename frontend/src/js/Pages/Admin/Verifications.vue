<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
});

// --- LÓGICA DE FILTRADO ---
const filterRole = ref('all'); // all, driver, passenger

const filteredUsers = computed(() => {
    const list = props.users || [];
    if (filterRole.value === 'all') return list;
    return list.filter(u => u.role === filterRole.value);
});

// --- LÓGICA DE IDENTIDAD ---
const showingModal = ref(false);
const selectedUser = ref(null);
const rejectReason = ref('');

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
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

const approveIdentity = () => {
    if (!confirm('¿Estás seguro de APROBAR esta identidad?')) return;
    form.post(route('admin.verifications.approve', selectedUser.value.id), {
        onSuccess: () => closeModal(),
    });
};

const rejectIdentity = () => {
    if (!rejectReason.value) return alert('Debes escribir una razón para rechazar.');
    const rejectForm = useForm({ reason: rejectReason.value });
    rejectForm.post(route('admin.verifications.reject', selectedUser.value.id), {
        onSuccess: () => closeModal(),
    });
};

// Helper: Secure URL
const getDocumentUrl = (path) => {
    if (!path) return '#';
    return `/secure-file/${path}`;
};

</script>

<template>
    <Head title="Verificaciones" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🛡️ Centro de Verificaciones</h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- LISTA DE IDENTIDAD (PASAJEROS Y CONDUCTORES) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                            <h3 class="text-lg font-bold flex items-center gap-2">
                                <span>🆔</span> Revisión de Documentos ({{ filteredUsers.length }})
                            </h3>
                            
                            <!-- FILTROS -->
                            <div class="flex gap-2 mt-4 md:mt-0">
                                <button @click="filterRole = 'all'" 
                                    class="px-4 py-1.5 rounded-full text-sm font-bold border transition-all" 
                                    :class="filterRole === 'all' ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'">
                                    Todos
                                </button>
                                <button @click="filterRole = 'driver'" 
                                    class="px-4 py-1.5 rounded-full text-sm font-bold border transition-all"
                                    :class="filterRole === 'driver' ? 'bg-yellow-100 text-yellow-800 border-yellow-200 shadow-sm' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'">
                                    🚖 Conductores
                                </button>
                                <button @click="filterRole = 'passenger'" 
                                    class="px-4 py-1.5 rounded-full text-sm font-bold border transition-all"
                                    :class="filterRole === 'passenger' ? 'bg-green-100 text-green-800 border-green-200 shadow-sm' : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'">
                                    👋 Pasajeros
                                </button>
                            </div>
                        </div>

                        <div v-if="filteredUsers.length === 0" class="text-center py-10 text-gray-500 border-2 border-dashed rounded-xl bg-gray-50">
                            {{ props.users.length > 0 ? 'No hay resultados con este filtro.' : '🎉 No hay documentos pendientes de revisión.' }}
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cédula</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in filteredUsers" :key="user.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                                    <div class="text-xs text-blue-500">{{ user.phone_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs rounded-full font-bold uppercase" 
                                                :class="user.role === 'driver' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'">
                                                {{ user.role === 'driver' ? 'Conductor' : 'Pasajero' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ user.id_card_number || 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(user.updated_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="inspectUser(user)" class="text-indigo-600 hover:text-indigo-900 font-bold bg-indigo-50 px-3 py-1 rounded">
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
                            <span class="text-lg font-bold">{{ selectedUser.phone_number }}</span>
                        </div>
                        
                        <!-- 📷 FOTO DE PERFIL (Solicitud #6) -->
                        <div class="bg-gray-50 p-3 rounded border">
                            <span class="block text-xs text-gray-500 uppercase mb-1">Foto de Perfil</span>
                             <div class="flex justify-center">
                                <img v-if="selectedUser.profile_photo_path" :src="'/storage/' + selectedUser.profile_photo_path" class="h-24 w-24 rounded-full object-cover border-2 border-indigo-200">
                                <span v-else class="text-xs text-gray-400">Sin foto</span>
                            </div>
                        </div>
                        
                        <!-- 🚙 DATOS DEL VEHÍCULO (Solo Conductores) -->
                        <div v-if="selectedUser.role === 'driver' && selectedUser.vehicle" class="bg-yellow-50 p-3 rounded border border-yellow-200">
                            <h4 class="font-bold text-xs text-yellow-800 mb-2 border-b border-yellow-200 pb-1">🚙 VEHÍCULO DECLARADO</h4>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Modelo:</span>
                                    <span class="font-bold">{{ selectedUser.vehicle.model }} ({{ selectedUser.vehicle.year }})</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Color:</span>
                                    <span class="font-bold">{{ selectedUser.vehicle.color }}</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-gray-500">Placa:</span>
                                    <span class="font-mono bg-white border px-2 py-0.5 rounded shadow-sm font-bold">{{ selectedUser.vehicle.plate }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- DOCUMENTOS COMUNES -->
                        <div>
                            <span class="block text-xs text-gray-500 uppercase mb-1">Foto Documento</span>
                            <div class="border rounded p-1 bg-gray-100 flex justify-center">
                                <img v-if="selectedUser.id_card_photo_path" :src="getDocumentUrl(selectedUser.id_card_photo_path)" class="h-32 object-contain cursor-pointer hover:scale-105 transition" onclick="window.open(this.src)">
                                <span v-else class="text-xs text-gray-400 py-4">Sin archivo</span>
                            </div>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-500 uppercase mb-1">Biometría</span>
                            <div class="border rounded p-1 bg-gray-100 flex justify-center">
                                <img v-if="selectedUser.biometric_photo_path" :src="getDocumentUrl(selectedUser.biometric_photo_path)" class="h-32 object-contain cursor-pointer hover:scale-105 transition" onclick="window.open(this.src)">
                                <span v-else class="text-xs text-gray-400 py-4">Sin archivo</span>
                            </div>
                        </div>

                        <!-- 🚖 DOCUMENTOS SOLO CONDUCTOR -->
                        <div v-if="selectedUser.role === 'driver'" class="pt-4 border-t border-dashed">
                             <h4 class="font-bold text-xs text-yellow-800 bg-yellow-100 px-2 py-1 rounded mb-2 inline-block">🚖 Documentos de Conductor</h4>
                             
                             <div class="grid grid-cols-4 gap-2">
                                 <div class="text-center">
                                     <span class="text-[10px] uppercase block mb-1">Licencia</span>
                                     <a v-if="selectedUser.license_file" :href="getDocumentUrl(selectedUser.license_file)" target="_blank" class="block bg-blue-50 text-blue-600 text-xs font-bold py-2 rounded hover:bg-blue-100 border border-blue-200">Ver 📄</a>
                                     <span v-else class="text-xs text-red-400">Falta</span>
                                 </div>
                                 <div class="text-center">
                                     <span class="text-[10px] uppercase block mb-1">C. Médico</span>
                                     <a v-if="selectedUser.medical_certificate_file" :href="getDocumentUrl(selectedUser.medical_certificate_file)" target="_blank" class="block bg-blue-50 text-blue-600 text-xs font-bold py-2 rounded hover:bg-blue-100 border border-blue-200">Ver 📄</a>
                                     <span v-else class="text-xs text-red-400">Falta</span>
                                 </div>
                                 <div class="text-center">
                                     <span class="text-[10px] uppercase block mb-1">RIF</span>
                                     <a v-if="selectedUser.rif_file" :href="getDocumentUrl(selectedUser.rif_file)" target="_blank" class="block bg-blue-50 text-blue-600 text-xs font-bold py-2 rounded hover:bg-blue-100 border border-blue-200">Ver 📄</a>
                                     <span v-else class="text-xs text-red-400">Falta</span>
                                 </div>
                                 <div class="text-center">
                                     <span class="text-[10px] uppercase block mb-1">Circulación</span>
                                     <a v-if="selectedUser.circulation_permit_file_path" :href="getDocumentUrl(selectedUser.circulation_permit_file_path)" target="_blank" class="block bg-blue-50 text-blue-600 text-xs font-bold py-2 rounded hover:bg-blue-100 border border-blue-200">Ver 📄</a>
                                     <span v-else class="text-xs text-red-400">Falta</span>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-gray-200">
                    <h3 class="text-sm font-bold text-gray-900 mb-2">Veredicto del Admin</h3>
                    <div class="flex flex-col md:flex-row gap-4">
                        <PrimaryButton @click="approveIdentity" class="bg-green-600 hover:bg-green-700 justify-center w-full md:w-auto h-12">
                            ✅ APROBAR IDENTIDAD
                        </PrimaryButton>
                        <div class="flex-1 flex gap-2">
                            <TextInput v-model="rejectReason" placeholder="Razón del rechazo..." class="w-full" />
                            <DangerButton @click="rejectIdentity" class="h-12 whitespace-nowrap">❌ Rechazar</DangerButton>
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