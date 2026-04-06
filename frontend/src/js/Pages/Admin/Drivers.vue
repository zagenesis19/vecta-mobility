<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    drivers: { type: Array, default: () => [] },
});

// --- LÓGICA DE INSPECCIÓN ---
const showingModal = ref(false);
const selectedUser = ref(null);

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return dateString.substring(0, 10);
};

const inspectDriver = (driver) => {
    selectedUser.value = driver;
    showingModal.value = true;
};

const closeModal = () => {
    showingModal.value = false;
    selectedUser.value = null;
};
</script>

<template>
    <Head title="Gestión de Conductores" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">🚖 Gestión de Conductores</h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold">Conductores Registrados ({{ drivers.length }})</h3>
                        </div>

                        <div v-if="drivers.length === 0" class="text-center py-10 text-gray-500 border-2 border-dashed rounded-xl bg-gray-50">
                            No hay conductores registrados en el sistema.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conductor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehículo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="driver in drivers" :key="driver.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ driver.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ driver.email }}</div>
                                                    <div class="text-xs text-blue-500">{{ driver.phone_number }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="driver.is_approved" class="px-2 py-1 text-xs rounded-full font-bold bg-green-100 text-green-800">
                                                ACTIVO
                                            </span>
                                            <span v-else class="px-2 py-1 text-xs rounded-full font-bold bg-yellow-100 text-yellow-800">
                                                PENDIENTE
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div v-if="driver.vehicle">
                                                <div class="font-bold">{{ driver.vehicle.plate }}</div>
                                                <div class="text-xs">{{ driver.vehicle.model }} ({{ driver.vehicle.color }})</div>
                                            </div>
                                            <span v-else class="text-red-500 text-xs">Sin Vehículo</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(driver.created_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="inspectDriver(driver)" class="text-indigo-600 hover:text-indigo-900 font-bold bg-indigo-50 px-3 py-1 rounded border border-indigo-200">
                                                📄 Ver Papeles
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

        <!-- MODAL DE INSPECCIÓN (SOLO LECTURA) -->
        <Modal :show="showingModal" @close="closeModal">
            <div class="p-6" v-if="selectedUser">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-lg font-medium text-gray-900">
                        Expediente: {{ selectedUser.name }}
                    </h2>
                    <span class="px-2 py-1 text-xs rounded font-mono bg-gray-100">ID: {{ selectedUser.id }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-96 overflow-y-auto pr-2">
                    <!-- COLUMNA 1: DATOS -->
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-3 rounded border">
                            <span class="block text-xs text-gray-500 uppercase">Cédula</span>
                            <span class="text-lg font-bold">{{ selectedUser.id_card_number || 'No registrada' }}</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded border">
                            <span class="block text-xs text-gray-500 uppercase">Teléfono</span>
                            <span class="text-lg font-bold">{{ selectedUser.phone_number || 'No registrado' }}</span>
                        </div>
                        
                        <!-- 🚙 DATOS DEL VEHÍCULO -->
                        <div v-if="selectedUser.vehicle" class="bg-yellow-50 p-3 rounded border border-yellow-200">
                            <h4 class="font-bold text-xs text-yellow-800 mb-2 border-b border-yellow-200 pb-1">🚙 VEHÍCULO ACTIVADO</h4>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Modelo:</span>
                                    <span class="font-bold">{{ selectedUser.vehicle.model }} ({{ selectedUser.vehicle.year }})</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Marca:</span>
                                    <span class="font-bold">{{ selectedUser.vehicle.type }}</span> <!-- Usamos type como Marca/Tipo por ahora -->
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Color:</span>
                                    <span class="font-bold">{{ selectedUser.vehicle.color }}</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-gray-500">Placa:</span>
                                    <span class="font-mono bg-white border px-2 py-0.5 rounded shadow-sm font-bold text-lg">{{ selectedUser.vehicle.plate }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="bg-red-50 p-3 rounded border border-red-200 text-red-700 text-sm">
                            ⚠️ Este conductor no tiene vehículo asociado.
                        </div>
                    </div>

                    <!-- COLUMNA 2: DOCUMENTOS -->
                    <div class="space-y-4">
                        <h4 class="font-bold text-xs text-gray-500 uppercase border-b pb-1">Documentación Digital</h4>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <!-- FOTO PERFIL -->
                            <div class="border rounded p-2 bg-gray-50 text-center">
                                <span class="text-[10px] uppercase block mb-1">Foto Perfil</span>
                                <img v-if="selectedUser.profile_photo_path" :src="'/storage/' + selectedUser.profile_photo_path" class="h-20 w-20 object-cover rounded-full mx-auto cursor-pointer hover:opacity-75" onclick="window.open(this.src)">
                                <span v-else class="text-xs text-gray-400">Sin foto</span>
                            </div>

                            <!-- FOTO CÉDULA -->
                            <div class="border rounded p-2 bg-gray-50 text-center">
                                <span class="text-[10px] uppercase block mb-1">Cédula</span>
                                <img v-if="selectedUser.id_card_photo_path" :src="'/storage/' + selectedUser.id_card_photo_path" class="h-20 object-contain mx-auto cursor-pointer hover:opacity-75" onclick="window.open(this.src)">
                                <span v-else class="text-xs text-red-400">Pendiente</span>
                            </div>
                        </div>

                        <!-- LICENCIA Y DOCUMENTOS EXTRA -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between border p-2 rounded hover:bg-gray-50">
                                <span class="text-sm">🪪 Licencia</span>
                                <a v-if="selectedUser.license_file" :href="'/storage/' + selectedUser.license_file" target="_blank" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold">VER ARCHIVO</a>
                                <span v-else class="text-xs text-gray-400">No cargado</span>
                            </div>
                            <div class="flex items-center justify-between border p-2 rounded hover:bg-gray-50">
                                <span class="text-sm">🏥 Cert. Médico</span>
                                <a v-if="selectedUser.medical_certificate_file" :href="'/storage/' + selectedUser.medical_certificate_file" target="_blank" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold">VER ARCHIVO</a>
                                <span v-else class="text-xs text-gray-400">No cargado</span>
                            </div>
                            <div class="flex items-center justify-between border p-2 rounded hover:bg-gray-50">
                                <span class="text-sm">📄 RIF</span>
                                <a v-if="selectedUser.rif_file" :href="'/storage/' + selectedUser.rif_file" target="_blank" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold">VER ARCHIVO</a>
                                <span v-else class="text-xs text-gray-400">No cargado</span>
                            </div>
                             <div class="flex items-center justify-between border p-2 rounded hover:bg-gray-50">
                                <span class="text-sm">🚗 Circulación</span>
                                <a v-if="selectedUser.circulation_permit_file_path" :href="'/storage/' + selectedUser.circulation_permit_file_path" target="_blank" class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold">VER ARCHIVO</a>
                                <span v-else class="text-xs text-gray-400">No cargado</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">Cerrar Expediente</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
