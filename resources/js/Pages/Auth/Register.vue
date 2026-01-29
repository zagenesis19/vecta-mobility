<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

// Inicializamos el formulario con TODOS los campos necesarios
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'passenger', // Por defecto es pasajero
    
    // --- DATOS DEL VEHÍCULO ---
    vehicle_type: 'car', // <--- ¡NUEVO! Por defecto Carro
    vehicle_model: '',
    vehicle_plate: '',
    vehicle_year: '',
    vehicle_color: '',
    
    license_file: null // Campo para el archivo de la licencia
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registro" />

        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Únete a Vecta</h2>
            <p class="text-gray-500">Elige cómo quieres moverte hoy</p>
        </div>

        <form @submit.prevent="submit">
            
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

            <div>
                <InputLabel for="name" value="Nombre Completo" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Correo Electrónico" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="form.role === 'driver'" class="mt-6 bg-blue-50 p-4 rounded-lg border border-blue-100 space-y-4 animate-fade-in-down">
                <h3 class="font-bold text-blue-800 text-sm mb-2">Datos del Vehículo</h3>
                
                <div>
                    <InputLabel for="vehicle_type" value="¿Qué conduces?" />
                    <select 
                        id="vehicle_type" 
                        v-model="form.vehicle_type"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full mt-1"
                    >
                        <option value="car">🚗 Carro / Automóvil</option>
                        <option value="motorcycle">🏍️ Moto / Mototaxi</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="vehicle_model" value="Modelo (Ej: Bera SBR)" />
                        <TextInput
                            id="vehicle_model"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.vehicle_model"
                            required
                        />
                         <InputError class="mt-2" :message="form.errors.vehicle_model" />
                    </div>
                    <div>
                        <InputLabel for="vehicle_year" value="Año" />
                        <TextInput
                            id="vehicle_year"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.vehicle_year"
                            required
                        />
                         <InputError class="mt-2" :message="form.errors.vehicle_year" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="vehicle_plate" value="Placa" />
                        <TextInput
                            id="vehicle_plate"
                            type="text"
                            class="mt-1 block w-full uppercase"
                            v-model="form.vehicle_plate"
                            required
                        />
                         <InputError class="mt-2" :message="form.errors.vehicle_plate" />
                    </div>
                    <div>
                        <InputLabel for="vehicle_color" value="Color" />
                        <TextInput
                            id="vehicle_color"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.vehicle_color"
                            required
                        />
                         <InputError class="mt-2" :message="form.errors.vehicle_color" />
                    </div>
                </div>

                <div>
                    <InputLabel for="license_file" value="Licencia de Conducir (Imagen)" />
                    <input 
                        type="file" 
                        @change="form.license_file = $event.target.files[0]"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200"
                        accept="image/*"
                    />
                    <InputError class="mt-2" :message="form.errors.license_file" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Contraseña" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirmar Contraseña" />
                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    :href="route('login')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    ¿Ya tienes cuenta?
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Registrarme
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>

<style scoped>
.animate-fade-in-down {
    animation: fadeInDown 0.3s ease-out;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>