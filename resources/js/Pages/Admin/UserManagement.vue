<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

// --- Filtros (Búsqueda y Rol) ---
const search = ref(props.filters.search || '');
const currentRole = ref(props.filters.role || 'passenger');

// Debounce search
let timeout = null;
watch(search, (value) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(
            route('admin.users.index'),
            { search: value, role: currentRole.value },
            { preserveState: true, replace: true }
        );
    }, 300);
});

// Cambio de pestaña (Rol)
const setRole = (role) => {
    currentRole.value = role;
    router.get(
        route('admin.users.index'),
        { role: role, search: search.value },
        { preserveState: true, replace: true }
    );
};

// --- Modales ---
const showBanModal = ref(false);
const showMessageModal = ref(false);
const selectedUser = ref(null);

const banForm = useForm({
    ban_reason: '',
});

const messageForm = useForm({
    message: '',
});

// Abrir Modal de Sanción
const openBanModal = (user) => {
    selectedUser.value = user;
    banForm.ban_reason = user.ban_reason || '';
    showBanModal.value = true;
};

// Confirmar Sanción/Reactivación
const confirmStatusToggle = () => {
    banForm.put(route('admin.users.status', selectedUser.value.id), {
        onSuccess: () => {
            showBanModal.value = false;
            banForm.reset();
            selectedUser.value = null;
        },
    });
};

// Abrir Modal de Mensaje
const openMessageModal = (user) => {
    selectedUser.value = user;
    messageForm.reset();
    showMessageModal.value = true;
};

// Enviar Mensaje
const sendMessage = () => {
    messageForm.post(route('admin.users.message', selectedUser.value.id), {
        onSuccess: () => {
            showMessageModal.value = false;
            messageForm.reset();
            selectedUser.value = null;
        },
    });
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Gestión de Usuarios 👥
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Pestañas (Tabs) -->
                <div class="flex space-x-4 mb-6 border-b border-gray-200">
                    <button 
                        @click="setRole('passenger')"
                        class="pb-2 px-4 font-semibold transition-colors duration-200"
                        :class="currentRole === 'passenger' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Pasajeros
                    </button>
                    <button 
                        @click="setRole('driver')"
                        class="pb-2 px-4 font-semibold transition-colors duration-200"
                        :class="currentRole === 'driver' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                    >
                        Conductores
                    </button>
                </div>

                <!-- Barra de Búsqueda -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
                    <TextInput
                        v-model="search"
                        placeholder="Buscar por nombre, correo o teléfono..."
                        class="w-full sm:w-1/2"
                    />
                </div>

                <!-- Tabla de Usuarios -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contacto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in users.data" :key="user.id" :class="{'bg-red-50': !user.is_active}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full object-cover" 
                                                     :src="user.profile_photo_path ? '/storage/' + user.profile_photo_path : `https://ui-avatars.com/api/?name=${user.name}&background=random`" 
                                                     alt="">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                <div class="text-xs text-gray-500">ID: {{ user.id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ user.email }}</div>
                                        <div class="text-sm text-gray-500">{{ user.phone_number || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                              :class="user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ user.is_active ? 'Activo' : 'Sancionado' }}
                                        </span>
                                        <div v-if="!user.is_active" class="text-xs text-red-600 mt-1 max-w-xs truncate">
                                            {{ user.ban_reason }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <!-- Botón Mensaje -->
                                        <button @click="openMessageModal(user)" class="text-blue-600 hover:text-blue-900 border border-blue-200 rounded px-3 py-1 bg-blue-50 hover:bg-blue-100 transition">
                                            ✉️ Mensaje
                                        </button>
                                        
                                        <!-- Botón Sancionar/Reactivar -->
                                        <button @click="openBanModal(user)" 
                                                class="border rounded px-3 py-1 transition"
                                                :class="user.is_active ? 'text-red-600 border-red-200 bg-red-50 hover:bg-red-100' : 'text-green-600 border-green-200 bg-green-50 hover:bg-green-100'">
                                            {{ user.is_active ? '🚫 Sancionar' : '✅ Reactivar' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No se encontraron usuarios.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación Simple -->
                    <div class="px-6 py-4 border-t border-gray-200" v-if="users.links.length > 3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">
                                Mostrando {{ users.from }} a {{ users.to }} de {{ users.total }} resultados
                            </span>
                             <div class="flex gap-1">
                                <Link v-for="(link, key) in users.links" :key="key" 
                                      :href="link.url || '#'" 
                                      v-html="link.label"
                                      class="px-3 py-1 text-sm border rounded"
                                      :class="{'bg-blue-500 text-white': link.active, 'text-gray-500 bg-white hover:bg-gray-50': !link.active, 'opacity-50 cursor-not-allowed': !link.url}" />
                             </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Modal de Sanción/Reactivación -->
        <Modal :show="showBanModal" @close="showBanModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ selectedUser?.is_active ? 'Sancionar Usuario' : 'Reactivar Usuario' }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ selectedUser?.is_active 
                        ? 'Estás a punto de bloquear el acceso a este usuario. Por favor indica la razón.' 
                        : '¿Deseas reactivar el acceso a este usuario? Se eliminará la razón de la sanción.' }}
                </p>

                <div class="mt-6">
                    <TextInput
                        v-if="selectedUser?.is_active"
                        v-model="banForm.ban_reason"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ej: Comportamiento inadecuado, impagos reiterados..."
                        ref="banInput"
                        @keyup.enter="confirmStatusToggle"
                    />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showBanModal = false"> Cancelar </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{'bg-green-600 hover:bg-green-700 focus:ring-green-500': !selectedUser?.is_active}"
                        :disabled="banForm.processing"
                        @click="confirmStatusToggle"
                    >
                        {{ selectedUser?.is_active ? 'Confirmar Sanción' : 'Confirmar Reactivación' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Modal de Mensaje -->
        <Modal :show="showMessageModal" @close="showMessageModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Enviar Mensaje a {{ selectedUser?.name }}
                </h2>
                
                <p class="mt-1 text-sm text-gray-600">
                    Este mensaje aparecerá en el buzón interno de la aplicación del usuario.
                </p>

                <div class="mt-4">
                    <textarea
                        v-model="messageForm.message"
                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        rows="4"
                        placeholder="Escribe tu mensaje aquí..."
                    ></textarea>
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="showMessageModal = false"> Cancelar </SecondaryButton>

                    <PrimaryButton
                        class="ms-3"
                        :disabled="messageForm.processing"
                        @click="sendMessage"
                    >
                        Enviar Mensaje 🚀
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
