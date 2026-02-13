<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Instagram - Vecta Mobility" />
    
    <div class="min-h-screen bg-black text-white flex flex-col font-sans">
        <!-- Header -->
        <header class="border-b border-gray-800 px-4 py-3 sticky top-0 bg-black z-50 flex justify-between items-center">
            <div class="font-bold text-xl tracking-tighter">Instagram</div>
            <div class="flex gap-4">
                <button class="bg-blue-500 px-4 py-1.5 rounded-lg text-sm font-semibold hover:bg-blue-600 transition">Log In</button>
                <button class="text-blue-500 font-semibold text-sm">Sign Up</button>
            </div>
        </header>

        <!-- Profile Section -->
        <main class="max-w-[935px] mx-auto w-full p-4 sm:p-8 flex-1">
            
            <!-- Profile Info -->
            <div class="flex flex-col sm:flex-row gap-6 sm:gap-16 items-center sm:items-start mb-12">
                <!-- Avatar -->
                <div class="w-24 h-24 sm:w-40 sm:h-40 rounded-full p-1 bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 flex-shrink-0">
                    <div class="w-full h-full rounded-full bg-black p-1">
                        <div class="w-full h-full rounded-full bg-gray-800 flex items-center justify-center overflow-hidden">
                            <span class="text-4xl">🚀</span>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="flex-1 text-center sm:text-left">
                    <div class="flex flex-col sm:flex-row items-center gap-4 mb-4">
                        <h1 class="text-xl text-gray-100">vectamobility</h1>
                        <div class="flex gap-2">
                            <button class="bg-[#0095f6] hover:bg-[#1877f2] px-6 py-1.5 rounded-lg font-semibold text-sm transition">Follow</button>
                            <button class="bg-gray-800 hover:bg-gray-700 px-4 py-1.5 rounded-lg font-semibold text-sm transition">Message</button>
                        </div>
                    </div>

                    <div class="flex justify-center sm:justify-start gap-8 mb-4 text-sm">
                        <span><strong>12</strong> posts</span>
                        <span><strong>1.5k</strong> followers</span>
                        <span><strong>45</strong> following</span>
                    </div>

                    <div class="text-sm">
                        <h2 class="font-bold">Vecta Mobility 🇻🇪</h2>
                        <p class="text-gray-300">Tu solución de transporte en los Valles del Tuy.</p>
                        <p class="text-gray-300">🚖 Viajes seguros y rápidos.</p>
                        <p class="text-gray-300">📍 Charallave - Cúa - Ocumare - Yare - Santa Teresa.</p>
                        <a href="#" class="text-[#e0f1ff] font-medium hover:underline">vecta.com</a>
                    </div>
                </div>
            </div>

            <!-- Stories Highlights -->
            <div class="flex gap-4 sm:gap-8 overflow-x-auto pb-4 mb-8 justify-center sm:justify-start">
                <div class="flex flex-col items-center gap-2 cursor-pointer" v-for="i in 5" :key="i">
                    <div class="w-16 h-16 rounded-full border border-gray-700 bg-gray-900 flex items-center justify-center">
                        <span class="text-xl">{{ ['🔥', '🚌', '🛣️', '⭐', '👥'][i-1] }}</span>
                    </div>
                    <span class="text-xs text-gray-400">Highlight</span>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-t border-gray-800 flex justify-center gap-12 text-xs font-semibold tracking-widest text-gray-400 mb-4">
                <div class="border-t-2 border-white text-white py-4 flex gap-2 items-center"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h18v18H3V3zm16 16V5H5v14h14z"/></svg> POSTS</div>
                <div class="py-4 flex gap-2 items-center"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M16 12l2.25 4.88-.04 2.12-2.12.04L11.2 14.12 6.32 19H5v-1.32l4.88-4.88-2.12-2.25H10L16 6v4l-4 2 4 2z"/></svg> REELS</div>
                <div class="py-4 flex gap-2 items-center"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg> TAGGED</div>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-3 gap-1 sm:gap-4">
                <div v-for="n in 9" :key="n" class="aspect-square bg-gray-900 hover:opacity-75 transition cursor-pointer relative group">
                    <img :src="`https://picsum.photos/400/400?random=${n}`" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center gap-4 transition font-bold">
                        <span class="flex items-center gap-1">❤️ {{ Math.floor(Math.random() * 500) }}</span>
                        <span class="flex items-center gap-1">💬 {{ Math.floor(Math.random() * 50) }}</span>
                    </div>
                </div>
            </div>

        </main>
        
        <footer class="p-8 text-center text-gray-500 text-xs mt-8 border-t border-gray-900">
            <div class="flex justify-center gap-4 mb-4 flex-wrap">
                <span>Meta</span> <span>About</span> <span>Blog</span> <span>Jobs</span> <span>Help</span> <span>API</span>
            </div>
            © 2026 Instagram from Meta (Simulation for Vecta)
        </footer>
    </div>
</template>
