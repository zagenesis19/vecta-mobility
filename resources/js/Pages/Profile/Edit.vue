<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateIdentityForm from './Partials/UpdateIdentityForm.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    // 🔥 Recibimos las reseñas desde el controlador
    reviews: { 
        type: Array, 
        default: () => [] 
    } 
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profile</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg border-l-4 border-yellow-400">
                    <section>
                        <header class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">⭐ Mi Reputación</h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Así te ven otros usuarios en la plataforma.
                                </p>
                            </div>
                            <div class="text-right bg-yellow-50 px-4 py-2 rounded-xl border border-yellow-100">
                                <div class="text-4xl font-black text-yellow-500">{{ user.average_rating }}</div>
                                <div class="text-xs text-gray-400 uppercase tracking-wide font-bold">Promedio</div>
                            </div>
                        </header>

                        <div v-if="reviews.length > 0" class="space-y-4 max-h-96 overflow-y-auto pr-2">
                            <div v-for="review in reviews" :key="review.id" class="border-b pb-4 last:border-0 hover:bg-gray-50 p-2 rounded transition">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-bold text-gray-800 text-sm">{{ review.reviewer?.name || 'Usuario' }}</p>
                                        <div class="flex text-yellow-400 text-sm my-1">
                                            <span v-for="n in 5" :key="n">{{ n <= review.rating ? '★' : '☆' }}</span>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">{{ new Date(review.created_at).toLocaleDateString() }}</span>
                                </div>
                                <p v-if="review.comment" class="text-gray-600 text-sm mt-1 italic">"{{ review.comment }}"</p>
                                <p v-else class="text-gray-300 text-xs mt-1 italic">(Sin comentario)</p>
                            </div>
                        </div>

                        <div v-else class="text-center py-8 text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            <div class="text-4xl mb-2">😶</div>
                            <p class="font-bold">Aún no tienes calificaciones.</p>
                            <p class="text-xs">Completa viajes para empezar a ganar estrellas.</p>
                        </div>
                    </section>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdateIdentityForm class="max-w-xl" />
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>