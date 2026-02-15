<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import LegalContent from '@/Components/LegalContent.vue';

// --- ESTADOS DE MODALES Y PASOS ---
const showTermsModal = ref(false);      
const showVehicleModal = ref(false);    // Paso 2 Choferes
const showDocumentsModal = ref(false);  // Paso 3 Choferes (NUEVO)
const showOTPModal = ref(false);        // Paso 4 Verificación
const showLocationModal = ref(false);   // Paso 5 Final (Editado: Valles del Tuy)

const otpCodeInput = ref('');
const isVerifyingOTP = ref(false);
const otpError = ref('');

// Listas de Ubicación
const statesList = ['Miranda'];
const municipalitiesList = ref([]); // Reemplazado por lista dinámica
const fetchMunicipalities = async () => {
    try {
        const response = await axios.get('/api/municipalities');
        // Concatenar nombre y capital si deseas mantener el formato anterior (e.g. "Nombre (Capital)")
        municipalitiesList.value = response.data.map(m => {
           return m.capital ? `${m.name} (${m.capital})` : m.name;
        });
    } catch (e) {
        console.error("Error fetching municipalities:", e);
    }
};

onMounted(() => {
    fetchMunicipalities();
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'passenger',
    
    // Identidad
    id_card_number: '',
    phone_number: '',
    country_code: '+58', // Default
    gender: '',
    terms_accepted: false,
    
    // Vehículo
    vehicle_type: 'car',
    vehicle_model: '',
    vehicle_plate: '',
    vehicle_year: '',
    vehicle_color: '',
    
    // Documentos (5 Archivos)
    profile_photo: null,        
    license_file: null,         
    id_card_photo: null,        
    medical_certificate: null,  
    rif_file: null,             

    // Ubicación
    country: 'Venezuela',
    state: 'Miranda', // Default fijo
    municipality: ''
});

// --- LÓGICA DE VALIDACIÓN (Watchers para evitar doble clic) ---
watch(() => form.id_card_number, (val) => {
    if (val) {
        // Eliminar automáticamente todo lo que NO sea número (puntos, guiones, letras)
        const clean = val.replace(/\D/g, ''); 
        
        // Solo actualizar si cambió para evitar conflictos
        if (clean !== val) {
            form.id_card_number = clean;
        }
    }
});

// ... (Resto del código)

watch(() => form.phone_number, (val) => {
    if (val) {
        let clean = val.replace(/\D/g, '');
        // Quitar 0 inicial
        if (clean.startsWith('0')) clean = clean.substring(1);
        // Max 10 chars
        if (clean.length > 10) clean = clean.substring(0, 10);
        
        // Solo actualizar si es diferente para evitar loops
        if (clean !== val) {
            form.phone_number = clean;
        }
    }
});

// Validación de correo electrónico
const emailError = ref('');
const isEmailValid = ref(false);

watch(() => form.email, (val) => {
    if (!val) {
        emailError.value = '';
        isEmailValid.value = false;
        return;
    }

    // Eliminar espacios
    const trimmed = val.trim();
    if (trimmed !== val) {
        form.email = trimmed;
        return;
    }

    // Validaciones progresivas
    if (!val.includes('@')) {
        emailError.value = 'El correo debe contener @';
        isEmailValid.value = false;
        return;
    }

    const [localPart, domain] = val.split('@');

    if (!localPart || localPart.length < 1) {
        emailError.value = 'Falta el nombre de usuario antes de @';
        isEmailValid.value = false;
        return;
    }

    if (!domain) {
        emailError.value = 'Falta el dominio después de @';
        isEmailValid.value = false;
        return;
    }

    if (!domain.includes('.')) {
        emailError.value = 'El dominio debe contener un punto (ej: gmail.com)';
        isEmailValid.value = false;
        return;
    }

    const domainParts = domain.split('.');
    const extension = domainParts[domainParts.length - 1];

    if (extension.length < 2) {
        emailError.value = 'La extensión del dominio es muy corta (ej: .com, .net)';
        isEmailValid.value = false;
        return;
    }

    // Regex final completa para formato estándar de email
    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(val)) {
        emailError.value = 'Formato de correo inválido';
        isEmailValid.value = false;
        return;
    }

    emailError.value = '';
    isEmailValid.value = true;
});


// --- LÓGICA DE PROGRESO DE DOCUMENTOS ---
const documentsUploadedCount = computed(() => {
    let count = 0;
    if (form.profile_photo) count++;
    if (form.license_file) count++;
    if (form.id_card_photo) count++;
    if (form.medical_certificate) count++;
    if (form.rif_file) count++;
    if (form.circulation_permit) count++; // 🔥 Nuevo (6)
    return count;
});

const uploadPercentage = computed(() => {
    return (documentsUploadedCount.value / 6) * 100;
});

// --- LÓGICA DE NAVEGACIÓN ---

const startRegistrationFlow = async () => {
    if (!form.name || !form.email || !form.id_card_number || !form.phone_number || !form.gender || !form.password) {
        alert('Por favor completa todos los campos obligatorios del formulario.');
        return;
    }

    // Validación de correo electrónico
    if (!isEmailValid.value) {
        alert(emailError.value || 'Por favor ingresa un correo electrónico válido.');
        return;
    }

    // Validación de contraseñas coincidentes
    if (form.password !== form.password_confirmation) {
        alert('Las contraseñas no coinciden.');
        return;
    }

    // Validación extra teléfono
    if (form.phone_number.length !== 10 || !form.phone_number.startsWith('4')) {
        alert('El número debe tener 10 dígitos y comenzar por 4 (ej: 412...). No incluyas el 0 inicial.');
        return;
    }

    if (!form.terms_accepted) {
        alert('Debes aceptar los Términos y Condiciones para continuar.');
        return;
    }

    // 🔥 VALIDACIÓN REMOTA (AJAX) - Paso 1
    // Verificamos unicidad antes de avanzar
    try {
        await axios.post('/api/validate-register-step', {
            email: form.email,
            id_card_number: form.id_card_number,
            phone_number: form.phone_number
        });
    } catch (error) {
        if (error.response && error.response.status === 422) {
            let msg = "Error de validación:\n";
            Object.values(error.response.data.errors).forEach(err => msg += "❌ " + err + "\n");
            alert(msg);
            return; // 🛑 DETIENE EL PROCESO
        }
        console.error(error);
        alert('Error de conexión validando datos. Intenta de nuevo.');
        return;
    }

    if (form.role === 'driver') {
        showVehicleModal.value = true;
    } else {
        showOTPModal.value = true;
    }
};

// Vehículo -> Documentos
const confirmVehicleData = () => {
    if (!form.vehicle_model || !form.vehicle_plate || !form.vehicle_year || !form.vehicle_color) {
        alert('Por favor completa los datos del vehículo.');
        return;
    }
    showVehicleModal.value = false;
    showDocumentsModal.value = true; // Siguiente paso
};

// Documentos -> OTP
const confirmDocuments = () => {
    // Si faltan documentos, el usuario puede continuar pero sabiendo que está incompleto
    showDocumentsModal.value = false;
    showOTPModal.value = true;
};

// OTP -> Ubicación
const verifyOTP = () => {
    if (otpCodeInput.value.length < 4) {
        otpError.value = 'El código debe tener 4 dígitos.';
        return;
    }
    isVerifyingOTP.value = true;
    otpError.value = '';

    setTimeout(() => {
        isVerifyingOTP.value = false;
        if (otpCodeInput.value === '1234' || true) { 
            showOTPModal.value = false;
            showLocationModal.value = true;
        } else {
            otpError.value = 'Código incorrecto.';
        }
    }, 1500);
};

// Retroceder desde OTP
const backFromOTP = () => {
    showOTPModal.value = false;
    if (form.role === 'driver') {
        showDocumentsModal.value = true;
    }
    // Si es pasajero, simplemente se cierra el modal OTP y vuelve al form base.
};


// Submit Final
const submitFinal = () => {
    if (!form.state || !form.municipality) {
        alert('Por favor selecciona tu ubicación.');
        return;
    }
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
        onError: (errors) => {
            console.error('Errores de registro:', errors);
            // Convertir objeto de errores a string legible
            let msg = "No se pudo completar el registro por los siguientes errores:\n\n";
            Object.values(errors).forEach(err => msg += "- " + err + "\n");
            
            alert(msg);
            
            // Opcional: Cerrar modales si el error es de campos básicos
            if (errors.name || errors.email || errors.password || errors.id_card_number || errors.phone_number) {
                 showLocationModal.value = false;
                 showOTPModal.value = false;
                 showDocumentsModal.value = false;
                 showVehicleModal.value = false;
                 // Ir al inicio para que corrija
            }
        }
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registro" />

        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Únete a Vecta</h2>
            <p class="text-gray-500">Crea tu cuenta en pocos pasos</p>
        </div>

        <form @submit.prevent="startRegistrationFlow">
            
            <!-- SELECCIÓN DE ROL -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div 
                    @click="form.role = 'passenger'"
                    class="cursor-pointer border-2 rounded-xl p-4 flex flex-col items-center transition-all"
                    :class="form.role === 'passenger' ? 'border-black bg-gray-50' : 'border-gray-200 hover:border-gray-300'"
                >
                    <span class="text-3xl mb-2">🙋‍♂️</span>
                    <span class="font-bold text-sm">Viajar</span>
                </div>

                <div 
                    @click="form.role = 'driver'"
                    class="cursor-pointer border-2 rounded-xl p-4 flex flex-col items-center transition-all"
                    :class="form.role === 'driver' ? 'border-black bg-gray-50' : 'border-gray-200 hover:border-gray-300'"
                >
                    <span class="text-3xl mb-2">🚖</span>
                    <span class="font-bold text-sm">Conducir</span>
                </div>
            </div>

            <!-- DATOS PERSONALES -->
            <div>
                <InputLabel for="name" value="Nombre Completo" />
                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Correo Electrónico" />
                <div class="relative">
                    <TextInput 
                        id="email" 
                        type="email" 
                        class="mt-1 block w-full" 
                        :class="{
                            'border-red-500 focus:border-red-500 focus:ring-red-500': emailError && form.email,
                            'border-green-500 focus:border-green-500 focus:ring-green-500': isEmailValid && form.email
                        }"
                        v-model="form.email" 
                        required 
                        placeholder="ejemplo@correo.com"
                    />
                    <span v-if="isEmailValid && form.email" class="absolute right-3 top-1/2 -translate-y-1/2 text-green-500 text-lg">✓</span>
                    <span v-else-if="emailError && form.email" class="absolute right-3 top-1/2 -translate-y-1/2 text-red-500 text-lg">✗</span>
                </div>
                <p v-if="emailError && form.email" class="mt-1 text-sm text-red-600">{{ emailError }}</p>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- CÉDULA Y TELÉFONO (Mejorados) -->
            <div class="grid grid-cols-2 gap-4 mt-4">
                <div>
                    <InputLabel for="id_card_number" value="Cédula" />
                    <TextInput 
                        id="id_card_number" 
                        type="tel" 
                        class="mt-1 block w-full" 
                        v-model="form.id_card_number" 
                        required 
                        placeholder="Solo números" 
                    />
                    <InputError class="mt-2" :message="form.errors.id_card_number" />
                </div>
                <div>
                   <InputLabel for="phone_number" value="Teléfono" />
                   <div class="flex mt-1">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-100 text-gray-500 text-sm">
                            🇻🇪 +58
                        </span>
                        <TextInput 
                            id="phone_number" 
                            type="tel" 
                            class="rounded-l-none block w-full" 
                            v-model="form.phone_number" 
                            required 
                            placeholder="412..." 
                        />
                   </div>
                   <InputError class="mt-2" :message="form.errors.phone_number" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="gender" value="Género" />
                <select 
                    id="gender" 
                    v-model="form.gender"
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full mt-1"
                    required
                >
                    <option value="" disabled>Selecciona una opción</option>
                    <option value="male">Masculino</option>
                    <option value="female">Femenino</option>
                    <option value="other">Otro</option>
                </select>
                <InputError class="mt-2" :message="form.errors.gender" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Contraseña" />
                <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirmar Contraseña" />
                <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" required />
            </div>

            <div class="mt-4 flex items-center">
                <input 
                    type="checkbox" 
                    id="terms" 
                    v-model="form.terms_accepted"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    required
                >
                <label for="terms" class="ml-2 text-sm text-gray-600">
                    Acepto los <a href="#" @click.prevent="showTermsModal = true" class="text-blue-600 hover:underline">Términos y Condiciones</a>
                </label>
            </div>
            <InputError class="mt-1" :message="form.errors.terms_accepted" />

            <div class="flex items-center justify-end mt-6">
                <Link :href="route('login')" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md">
                    ¿Ya tienes cuenta?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Continuar ➡
                </PrimaryButton>
            </div>
        </form>

        <!-- ================= MODALES ================= -->

        <!-- 1. TÉRMINOS -->
        <Teleport to="body">
            <div v-if="showTermsModal" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
                <div class="fixed inset-0 bg-gray-500 opacity-75" @click="showTermsModal = false"></div>
                <div class="bg-white rounded-lg shadow-xl relative z-20 sm:w-full sm:max-w-2xl max-h-[80vh] flex flex-col">
                    <div class="p-6 overflow-y-auto flex-1">
                        <h3 class="text-xl font-bold mb-4">Términos y Condiciones</h3>
                        <LegalContent />
                    </div>
                    <div class="bg-gray-50 p-4 border-t flex justify-end">
                        <button @click="showTermsModal = false; form.terms_accepted = true" class="bg-blue-600 text-white px-4 py-2 rounded-md">
                            Acepto
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- 2. VEHÍCULO -->
        <Teleport to="body">
            <div v-if="showVehicleModal" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
                <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
                <div class="bg-white rounded-lg shadow-xl relative z-20 sm:w-full sm:max-w-lg">
                    <div class="bg-blue-600 px-4 py-3 text-white rounded-t-lg">
                        <h3 class="font-bold">🚙 Datos del Vehículo</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <InputLabel value="Tipo" />
                            <select v-model="form.vehicle_type" class="w-full border-gray-300 rounded-md mt-1">
                                <option value="car">🚗 Carro</option>
                                <option value="motorcycle">🏍️ Moto</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><InputLabel value="Modelo" /><TextInput v-model="form.vehicle_model" class="w-full" required /></div>
                            <div><InputLabel value="Año" /><TextInput v-model="form.vehicle_year" type="number" class="w-full" required /></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><InputLabel value="Placa" /><TextInput v-model="form.vehicle_plate" class="w-full uppercase" required /></div>
                            <div><InputLabel value="Color" /><TextInput v-model="form.vehicle_color" class="w-full" required /></div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 flex justify-end gap-3 rounded-b-lg">
                        <button @click="showVehicleModal = false" class="text-gray-600">Cancelar</button>
                        <PrimaryButton @click="confirmVehicleData">Siguiente ➡</PrimaryButton>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- 3. DOCS (Optimizado con "Llenar más tarde") -->
        <Teleport to="body">
            <div v-if="showDocumentsModal" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
                <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
                <div class="bg-white rounded-lg shadow-xl relative z-20 sm:w-full sm:max-w-xl max-h-[90vh] flex flex-col">
                    <div class="bg-indigo-600 px-4 py-3 text-white flex justify-between items-center rounded-t-lg">
                        <h3 class="font-bold">📄 Documentos Requeridos</h3>
                        <span class="bg-white/20 px-2 py-1 rounded text-xs">{{ documentsUploadedCount }}/6 Completados</span>
                    </div>
                    
                    <div class="w-full bg-gray-200 h-2">
                        <div class="bg-indigo-500 h-2 transition-all duration-300" :style="{ width: uploadPercentage + '%' }"></div>
                    </div>

                    <div class="p-6 space-y-4 overflow-y-auto">
                        <p v-if="uploadPercentage < 100" class="text-sm bg-yellow-50 text-yellow-700 p-2 rounded border border-yellow-200 mb-4">
                            📢 Puedes saltar este paso y completar tus documentos más tarde desde tu perfil.
                        </p>
                        <p class="text-sm text-gray-500 mb-4">Sube fotos claras de tus documentos. Formatos: JPG, PNG o PDF.</p>

                        <!-- Campos de Archivos -->
                        <div class="border-b pb-4">
                            <InputLabel value="1. Foto de Perfil" class="mb-1" />
                            <input type="file" @change="form.profile_photo = $event.target.files[0]" class="file-input" accept="image/*" />
                        </div>
                        <div class="border-b pb-4">
                            <InputLabel value="2. Licencia de Conducir" class="mb-1" />
                            <input type="file" @change="form.license_file = $event.target.files[0]" class="file-input" accept="image/*" />
                        </div>
                        <div class="border-b pb-4">
                            <InputLabel value="3. Foto de la Cédula" class="mb-1" />
                            <input type="file" @change="form.id_card_photo = $event.target.files[0]" class="file-input" accept="image/*" />
                        </div>
                        <div class="border-b pb-4">
                            <InputLabel value="4. Certificado Médico" class="mb-1" />
                            <input type="file" @change="form.medical_certificate = $event.target.files[0]" class="file-input" accept=".pdf,image/*" />
                        </div>
                        <div>
                            <InputLabel value="5. RIF Vigente" class="mb-1" />
                            <input type="file" @change="form.rif_file = $event.target.files[0]" class="file-input" accept=".pdf,image/*" />
                        </div>
                        <div class="mt-4 border-t pt-4">
                            <InputLabel value="6. Carnet de Circulación" class="mb-1" />
                            <input type="file" @change="form.circulation_permit = $event.target.files[0]" class="file-input" accept=".pdf,image/*" />
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 flex justify-between items-center rounded-b-lg">
                        <button @click="showDocumentsModal = false; showVehicleModal = true" class="text-gray-600 text-sm">⬅ Volver</button>
                        
                        <!-- BOTONES DE ACCIÓN -->
                        <div class="flex gap-2">
                            <button 
                                v-if="documentsUploadedCount < 6"
                                @click="confirmDocuments"
                                class="text-indigo-600 border border-indigo-600 px-4 py-2 rounded-md hover:bg-indigo-50 transition text-sm font-medium"
                            >
                                Llenar más tarde
                            </button>
                            
                            <PrimaryButton @click="confirmDocuments">
                                {{ documentsUploadedCount === 6 ? 'Siguiente ➡' : 'Continuar' }}
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- 4. OTP -->
        <Teleport to="body">
            <div v-if="showOTPModal" class="fixed inset-0 z-50 px-4 flex items-center justify-center">
                <div class="fixed inset-0 bg-gray-900 opacity-90"></div>
                <div class="bg-white rounded-2xl shadow-2xl relative z-20 w-full max-w-sm p-8 text-center">
                    <h3 class="text-xl font-bold mb-4">Verifica tu teléfono</h3>
                    <p class="text-gray-500 mb-4">Enviado a +58 {{ form.phone_number }}</p>
                    <input v-model="otpCodeInput" type="tel" class="text-center text-3xl font-bold w-full border-b-2 py-2 mb-4" placeholder="0000" maxlength="4" />
                    <button @click="verifyOTP" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl">
                        {{ isVerifyingOTP ? '...' : 'Verificar (1234)' }}
                    </button>
                    <button @click="backFromOTP" class="mt-4 text-sm text-gray-400 hover:text-gray-600 underline">
                        ⬅ Volver atrás
                    </button>
                </div>
            </div>
        </Teleport>

        <!-- 5. UBICACIÓN (Valles del Tuy) -->
        <Teleport to="body">
            <div v-if="showLocationModal" class="fixed inset-0 z-50 px-4 flex items-center justify-center">
                <div class="fixed inset-0 bg-gray-500 opacity-75"></div>
                <div class="bg-white rounded-lg shadow-xl relative z-20 w-full max-w-md">
                    <div class="bg-green-600 px-4 py-3 text-white rounded-t-lg"><h3 class="font-bold">📍 Ubicación</h3></div>
                    <div class="p-6 space-y-4">
                        <div>
                            <InputLabel value="País" />
                            <div class="flex"><span class="mr-2">🇻🇪</span><TextInput v-model="form.country" class="w-full bg-gray-100" readonly /></div>
                        </div>
                        <div>
                            <InputLabel value="Estado" />
                            <select v-model="form.state" class="w-full border-gray-300 rounded-md bg-gray-50">
                                <option v-for="st in statesList" :key="st" :value="st">{{ st }}</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Municipio" />
                            <select v-model="form.municipality" class="w-full border-gray-300 rounded-md">
                                <option value="" disabled>Selecciona tu municipio</option>
                                <option v-for="muni in municipalitiesList" :key="muni" :value="muni">{{ muni }}</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Servicio disponible en Valles del Tuy.</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 flex justify-between items-center rounded-b-lg">
                        <button @click="showLocationModal = false; showOTPModal = true" class="text-gray-600 text-sm">⬅ Volver</button>
                        <PrimaryButton @click="submitFinal" :disabled="form.processing">🚀 FINALIZAR REGISTRO</PrimaryButton>
                    </div>
                </div>
            </div>
        </Teleport>

    </GuestLayout>
</template>

<style scoped>
.file-input {
    @apply block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100;
}
</style>
```