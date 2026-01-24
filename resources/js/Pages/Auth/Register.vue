<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'passenger', // Por defecto pasajero
    // Nuevos campos para el conductor
    vehicle_model: '',
    vehicle_plate: '',
    vehicle_year: '',
    license_file: null,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

// Función para manejar la subida del archivo
const handleFileUpload = (event) => {
    form.license_file = event.target.files[0];
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
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

            <div class="mt-6">
                <InputLabel value="¿Cómo quieres usar Vecta?" class="text-lg font-semibold text-gray-700 mb-2" />
                
                <div class="flex gap-4">
                    <label class="cursor-pointer border-2 p-4 rounded-xl flex-1 text-center transition-all duration-200"
                        :class="form.role === 'passenger' ? 'border-black bg-gray-50 ring-1 ring-black shadow-md' : 'border-gray-200 hover:border-gray-400'">
                        <input type="radio" v-model="form.role" value="passenger" class="hidden">
                        <span class="text-3xl block mb-1">🙋‍♂️</span>
                        <div class="text-sm font-bold text-gray-800">Viajar</div>
                    </label>

                    <label class="cursor-pointer border-2 p-4 rounded-xl flex-1 text-center transition-all duration-200"
                        :class="form.role === 'driver' ? 'border-black bg-gray-50 ring-1 ring-black shadow-md' : 'border-gray-200 hover:border-gray-400'">
                        <input type="radio" v-model="form.role" value="driver" class="hidden">
                        <span class="text-3xl block mb-1">🚗</span>
                        <div class="text-sm font-bold text-gray-800">Conducir</div>
                    </label>
                </div>
                <InputError class="mt-2" :message="form.errors.role" />
            </div>

            <div v-if="form.role === 'driver'" class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200 animate-fade-in-down">
                <h3 class="text-md font-bold text-gray-800 mb-4 border-b pb-2">Datos del Vehículo</h3>

                <div class="mt-2">
                    <InputLabel for="vehicle_model" value="Modelo del Vehículo (Ej. Toyota Corolla)" />
                    <TextInput
                        id="vehicle_model"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.vehicle_model"
                        :required="form.role === 'driver'"
                    />
                    <InputError class="mt-2" :message="form.errors.vehicle_model" />
                </div>

                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <InputLabel for="vehicle_plate" value="Placa" />
                        <TextInput
                            id="vehicle_plate"
                            type="text"
                            class="mt-1 block w-full uppercase"
                            v-model="form.vehicle_plate"
                            placeholder="ABC-123"
                            :required="form.role === 'driver'"
                        />
                        <InputError class="mt-2" :message="form.errors.vehicle_plate" />
                    </div>
                    <div>
                        <InputLabel for="vehicle_year" value="Año" />
                        <TextInput
                            id="vehicle_year"
                            type="number"
                            class="mt-1 block w-full"
                            v-model="form.vehicle_year"
                            placeholder="2020"
                            :required="form.role === 'driver'"
                        />
                        <InputError class="mt-2" :message="form.errors.vehicle_year" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel for="license_file" value="Licencia de Conducir (Foto/PDF)" />
                    <input
                        id="license_file"
                        type="file"
                        @change="handleFileUpload"
                        class="mt-1 block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-black file:text-white
                        hover:file:bg-gray-800 cursor-pointer"
                        accept=".jpg,.jpeg,.png,.pdf"
                        :required="form.role === 'driver'"
                    />
                    <InputError class="mt-2" :message="form.errors.license_file" />
                </div>
            </div>

            <div class="flex items-center justify-end mt-6">
                <Link
                    :href="route('login')"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    ¿Ya tienes cuenta?
                </Link>

                <PrimaryButton class="ms-4 bg-black hover:bg-gray-800" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Registrarse
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>