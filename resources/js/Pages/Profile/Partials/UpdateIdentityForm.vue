<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import IdentityVerificationModal from '@/Components/IdentityVerification/IdentityVerificationModal.vue';

const user = usePage().props.auth.user;

// ESTADOS
const isPending = computed(() => user.identity_status === 'pending');
const isApproved = computed(() => user.identity_status === 'approved');
const isRejected = computed(() => user.identity_status === 'rejected');

// BLOQUEOS LÓGICOS
const isIdentityLocked = computed(() => isApproved.value); // Solo bloquear si YA ESTÁ APROBADO
const isPhoneLocked = computed(() => form.processing);

// MODOS DE EDICIÓN
const editingProfilePhoto = ref(false);
const editingIdCard = ref(false);
const showVerificationModal = ref(false);

// VARIABLES
const expiryMonth = ref('');
const expiryYear = ref('');
const phoneCode = ref('+58'); 
const phoneNumber = ref('');
const photoPreview = ref(null);

// PAÍSES
const countryCodes = [
    { code: '+58', flag: '🇻🇪', name: 'Venezuela' },
    { code: '+57', flag: '🇨🇴', name: 'Colombia' },
    { code: '+1',  flag: '🇺🇸', name: 'EE.UU' },
    { code: '+34', flag: '🇪🇸', name: 'España' },
];

const form = useForm({
    phone_number: user.phone_number || '',
    id_card_number: user.id_card_number || '',
    birth_date: user.birth_date ? user.birth_date.substring(0, 10) : '',
    id_card_expires_at: user.id_card_expires_at || '',
    id_card_photo: null,
    profile_photo: null,
    biometric_photo: null,
});

onMounted(() => {
    // 1. Separar Fecha
    if (user.id_card_expires_at) {
        const parts = user.id_card_expires_at.split('-');
        if (parts.length >= 2) {
            expiryYear.value = parts[0];
            expiryMonth.value = parts[1];
        }
    }
    // 2. Separar Teléfono
    if (user.phone_number) {
        let phone = user.phone_number.trim();
        const possibleCodes = ['+58', '58', '+1', '1', '+57', '57', '+34', '34'];
        let matched = false;

        for (const c of countryCodes) {
            if (phone.startsWith(c.code)) {
                phoneCode.value = c.code;
                phoneNumber.value = phone.replace(c.code, '').trim();
                matched = true;
                break;
            }
            const bare = c.code.replace('+', '');
            if (phone.startsWith(bare) && phone.length > 10) {
                 phoneCode.value = c.code;
                 phoneNumber.value = phone.substring(bare.length).trim();
                 matched = true;
                 break;
            }
        }

        if (!matched) {
            phoneNumber.value = phone;
        }
    }
});

// WATCHERS
watch([phoneCode, phoneNumber], () => {
    let raw = phoneNumber.value.replace(/\D/g, ''); 
    if (phoneCode.value === '+58' && raw.startsWith('0')) {
        raw = raw.substring(1);
    }
    phoneNumber.value = raw; 
    form.phone_number = raw ? `${phoneCode.value} ${raw}` : '';
});

watch(() => form.id_card_number, (val) => {
    if (val) form.id_card_number = val.replace(/[^0-9]/g, '');
});

watch(expiryYear, (val) => {
    if (val) {
        let clean = val.toString().replace(/\D/g, '');
        if (clean.length > 4) clean = clean.slice(0, 4);
        if (clean !== val) expiryYear.value = clean;
    }
});

watch([expiryMonth, expiryYear], () => {
    if (expiryMonth.value && expiryYear.value && expiryYear.value.length === 4) {
        form.id_card_expires_at = `${expiryYear.value}-${expiryMonth.value}-01`;
    } else {
        form.id_card_expires_at = '';
    }
});

const handleProfilePhotoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.profile_photo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            photoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const handleIdCardPhotoChange = (e) => form.id_card_photo = e.target.files[0];

// Helper para convertir Base64 a File
const dataURLtoFile = (dataurl, filename) => {
    let arr = dataurl.split(','),
        mime = arr[0].match(/:(.*?);/)[1],
        bstr = atob(arr[1]), 
        n = bstr.length, 
        u8arr = new Uint8Array(n);
        
    while(n--){
        u8arr[n] = bstr.charCodeAt(n);
    }
    
    return new File([u8arr], filename, {type:mime});
}

const handleVerificationComplete = ({ idCard, biometric }) => {
    // Intentamos usar la lógica más robusta: convertir a archivo si es posible
    if (idCard) {
        // Si el backend espera un archivo real (recomendado), usamos esto
        form.id_card_photo = dataURLtoFile(idCard, 'capture_cedula.png');
    }
    // Si la biometría es simplemente un string base64 para análisis facial
    if (biometric) {
        form.biometric_photo = biometric;
    }
};

const isIdCardExpired = computed(() => {
    if (!expiryMonth.value || !expiryYear.value) return true;
    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = today.getMonth() + 1;
    const expY = parseInt(expiryYear.value);
    const expM = parseInt(expiryMonth.value);
    if (currentYear > expY) return true;
    if (currentYear === expY && currentMonth > expM) return true;
    return false;
});

const submit = () => {
    if (phoneNumber.value.length < 7) {
        alert('El número de teléfono parece incompleto.');
        return;
    }
    // Solo validamos Cédula si NO está bloqueada (si está bloqueada, ya es correcta)
    if (!isIdentityLocked.value && form.id_card_number.length < 7) { 
        alert('El número de cédula debe tener al menos 7 dígitos.');
        return;
    }

    form.post(route('profile.identity.update'), {
        preserveScroll: true,
        forceFormData: true, 
        onSuccess: () => {
            form.reset('profile_photo', 'id_card_photo', 'biometric_photo');
            editingProfilePhoto.value = false;
            editingIdCard.value = false;
            photoPreview.value = null;
        },
        onError: (errors) => { 
            console.error('Errores de validación:', errors);
            if (Object.keys(errors).length > 0) {
                alert('Hay errores en el formulario: ' + Object.values(errors)[0]);
            }
        }
    });
};
</script>

<template>
    <section class="space-y-6 w-full">
        <!-- HEADER -->
        <header class="border-b pb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Identidad y Seguridad 🛡️</h2>
                <p class="mt-1 text-sm text-gray-600">Mantén tus datos actualizados para garantizar la seguridad de tu cuenta.</p>
            </div>
            
            <div class="shrink-0">
                <div v-if="isApproved" class="px-4 py-2 bg-green-100 text-green-800 rounded-full font-bold border border-green-200 flex items-center shadow-sm">
                    ✅ Identidad Verificada
                </div>
                <div v-else-if="isPending" class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full font-bold border border-yellow-200 flex items-center shadow-sm">
                    ⏳ Verificación en Proceso
                </div>
                <div v-else-if="isRejected" class="px-4 py-2 bg-red-100 text-red-800 rounded-full font-bold border border-red-200 flex items-center shadow-sm">
                    ❌ Rechazado: {{ user.identity_feedback }}
                </div>
            </div>
        </header>

        <form @submit.prevent="submit" class="space-y-8">
            
            <div class="bg-white p-6 rounded-xl border shadow-sm">
                <h3 class="font-bold text-lg text-indigo-900 mb-6 flex items-center gap-2">
                    🪪 Datos Personales
                    <span v-if="isIdentityLocked" class="text-xs font-normal text-gray-400 bg-gray-100 px-2 py-1 rounded">
                        Algunos campos están protegidos
                    </span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                    
                    <div class="md:col-span-12 lg:col-span-7 space-y-6">
                        
                        <div>
                            <InputLabel value="Número de Teléfono (WhatsApp)" class="mb-1" />
                            <div class="flex gap-3">
                                <select v-model="phoneCode" :disabled="isPhoneLocked" class="w-1/3 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                    <option v-for="country in countryCodes" :key="country.code" :value="country.code">
                                        {{ country.flag }} {{ country.code }}
                                    </option>
                                </select>
                                <TextInput 
                                    v-model="phoneNumber" 
                                    type="tel" 
                                    class="w-2/3" 
                                    :disabled="isPhoneLocked" 
                                    placeholder="4121234567"
                                    maxlength="11" 
                                />
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Este número se usará para contactarte.</p>
                            <InputError :message="form.errors.phone_number" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel value="Número de Cédula" class="mb-1" />
                            <TextInput 
                                v-model="form.id_card_number" 
                                type="text"
                                inputmode="numeric" 
                                class="w-full bg-gray-50" 
                                :class="{'opacity-75 cursor-not-allowed': isIdentityLocked}"
                                :disabled="isIdentityLocked" 
                                placeholder="Ej: 12345678"
                                maxlength="8"
                                minlength="7"
                            />
                            <InputError :message="form.errors.id_card_number" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel value="Fecha de Nacimiento" class="mb-1" />
                                <TextInput 
                                    v-model="form.birth_date" 
                                    type="date" 
                                    class="w-full" 
                                    :class="{'bg-gray-100 text-gray-500 cursor-not-allowed': isIdentityLocked}"
                                    :disabled="isIdentityLocked" 
                                />
                            </div>
                            
                            <div>
                                <InputLabel value="Vencimiento Cédula" class="mb-1" />
                                <div class="flex gap-2">
                                    <select 
                                        v-model="expiryMonth" 
                                        :disabled="isIdentityLocked" 
                                        class="w-1/2 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        :class="{'bg-gray-100 text-gray-500 cursor-not-allowed': isIdentityLocked}"
                                    >
                                        <option value="" disabled>Mes</option>
                                        <option value="01">Ene (01)</option><option value="02">Feb (02)</option><option value="03">Mar (03)</option>
                                        <option value="04">Abr (04)</option><option value="05">May (05)</option><option value="06">Jun (06)</option>
                                        <option value="07">Jul (07)</option><option value="08">Ago (08)</option><option value="09">Sep (09)</option>
                                        <option value="10">Oct (10)</option><option value="11">Nov (11)</option><option value="12">Dic (12)</option>
                                    </select>
                                    <TextInput 
                                        v-model="expiryYear" 
                                        type="text" 
                                        inputmode="numeric"
                                        maxlength="4"
                                        placeholder="Año (Ej: 2028)" 
                                        class="w-1/2" 
                                        :disabled="isIdentityLocked" 
                                        :class="{'bg-gray-100 text-gray-500 cursor-not-allowed': isIdentityLocked}"
                                    />
                                </div>
                                <InputError :message="form.errors.id_card_expires_at" class="mt-1" />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- SECCIÓN VERIFICACIÓN AVANZADA -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                    </div>
                    
                    <h3 class="font-bold text-indigo-900 mb-4 flex items-center relative z-10">
                        📸 Verificación de Identidad
                        <span v-if="isIdentityLocked" class="ml-2 text-xs text-gray-400">🔒 Verificado/En Revisión</span>
                    </h3>
                    
                    <div class="text-center h-full flex flex-col justify-center relative z-10">
                        <!-- Caso 1: Ya existe foto en DB -->
                        <div v-if="user.biometric_photo_path && !form.biometric_photo">
                            <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full mb-4 border border-green-200 text-sm font-bold shadow-sm">
                                ✅ Biometría Completada
                            </div>
                            <p class="text-xs text-green-700 mb-2">Tu identidad está protegida.</p>
                            <div v-if="!isIdentityLocked">
                                <SecondaryButton @click="showVerificationModal = true" size="sm">Actualizar Verificación</SecondaryButton>
                            </div>
                        </div>

                        <!-- Caso 2: Acaba de completar el proceso (form tiene data) -->
                        <div v-else-if="form.biometric_photo">
                            <div class="bg-white p-2 rounded-lg shadow mb-3 inline-block">
                                <img :src="form.biometric_photo" class="h-24 w-24 object-cover rounded-lg mx-auto">
                            </div>
                            <p class="text-sm font-bold text-indigo-700 mb-2">¡Captura Lista para Enviar!</p>
                            <SecondaryButton @click="showVerificationModal = true" size="sm">Repetir Proceso</SecondaryButton>
                        </div>

                        <!-- Caso 3: Pendiente o No Iniciado -->
                        <div v-else>
                            <p class="text-sm text-gray-600 mb-6">Realiza una breve verificación facial para activar tu cuenta.</p>
                            
                            <!-- SOLO Bloquear si está APROBADO o si está PENDIENTE Y YA TIENE FOTO (esperando revisión) -->
                            <div v-if="isApproved || (isPending && user.biometric_photo_path)" class="text-gray-400 italic text-sm border-2 border-dashed p-4 rounded-lg">
                                <span v-if="isApproved">✅ Identidad Verificada.</span>
                                <span v-else>⏳ Verificación enviada. Esperando revisión.</span>
                            </div>
                            
                            <!-- Habilitar si NO está aprobado Y (No es pendiente O le falta la foto) -->
                            <button v-else @click.prevent="showVerificationModal = true" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2 group">
                                <span class="text-xl group-hover:scale-110 transition-transform">📷</span>
                                Iniciar Verificación
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl border shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2">👤 Foto de Perfil</h3>
                        <p class="text-xs text-gray-500 mb-4">Esta es la imagen pública que verán los conductores.</p>
                    </div>
                    
                    <div class="flex items-center gap-6">
                        <div class="shrink-0">
                            <!-- PREVIEW LOGIC -->
                            <img v-if="photoPreview" :src="photoPreview" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md">
                            <img v-else-if="user.profile_photo_path && !editingProfilePhoto" :src="'/storage/' + user.profile_photo_path" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md">
                            <div v-else class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center text-3xl shadow-inner text-gray-400">👤</div>
                        </div>
                        <div class="w-full">
                            <div v-if="editingProfilePhoto || !user.profile_photo_path">
                                <input type="file" @change="handleProfilePhotoChange" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*" />
                                <SecondaryButton v-if="editingProfilePhoto" @click="editingProfilePhoto = false; photoPreview = null;" size="sm" class="mt-2">Cancelar</SecondaryButton>
                            </div>
                            <div v-else>
                                <div class="relative group cursor-pointer" @click="editingProfilePhoto = true">
                                    <div class="flex items-center gap-4 p-2 rounded-lg group-hover:bg-gray-50 transition">
                                        <div class="flex-1">
                                            <p class="text-sm font-bold text-gray-700 group-hover:text-indigo-600 transition">Tu foto actual</p>
                                            <p class="text-xs text-indigo-600 opacity-0 group-hover:opacity-100 transition">Clic para cambiar</p>
                                        </div>
                                        <SecondaryButton size="sm">Cambiar Foto</SecondaryButton>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-4 pt-4 border-t">
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-bold">✅ Información guardada correctamente.</p>
                </Transition>
                
                <PrimaryButton :disabled="form.processing" class="h-12 px-8 text-lg w-full sm:w-auto justify-center shadow-lg hover:shadow-xl transition-all bg-indigo-600 hover:bg-indigo-700">
                    Guardar Cambios
                </PrimaryButton>
            </div>
        </form>

        <!-- MODAL DE VERIFICACIÓN -->
        <IdentityVerificationModal 
            :show="showVerificationModal" 
            @close="showVerificationModal = false" 
            @completed="handleVerificationComplete"
        />

    </section>
</template>