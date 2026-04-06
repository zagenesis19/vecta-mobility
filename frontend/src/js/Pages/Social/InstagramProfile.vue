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
                        <div class="w-full h-full rounded-full flex items-center justify-center overflow-hidden bg-black">
                            <img src="/images/vecta-logo.png" alt="Vecta Mobility" class="w-full h-full object-contain p-2">
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
                        <span><strong>156</strong> posts</span>
                        <span><strong>12.5k</strong> followers</span>
                        <span><strong>89</strong> following</span>
                    </div>

                    <div class="text-sm">
                        <h2 class="font-bold">Vecta Mobility 🇻🇪</h2>
                        <p class="text-gray-300">Tu app de transporte en los Valles del Tuy 📍</p>
                        <p class="text-gray-300">🚀 Viajes seguros, rápidos y confiables.</p>
                        <p class="text-gray-300">👇 ¡Descarga la app aquí!</p>
                        <a href="https://vectamobility.com/descarga" class="text-[#e0f1ff] font-medium hover:underline">linktr.ee/vectamobility</a>
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

            <!-- Grid (Hybrid: Photo + Graphic Overlay) -->
            <div class="grid grid-cols-3 gap-1 sm:gap-4">
                
                <!-- Post 1: Promo "40% Menos" (Happy Woman in Car + Text) -->
                <div class="aspect-square relative group overflow-hidden cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#001F5B] via-[#001F5B]/70 to-transparent flex flex-col items-center justify-end p-2 sm:p-4 text-center">
                        <h3 class="text-white font-black text-lg sm:text-2xl leading-none mb-1">
                            <span class="text-[#80C5DE]">40% OFF</span><br>EN TU<br>VIAJE
                        </h3>
                        <div class="bg-white text-[#001F5B] text-[10px] font-bold px-2 py-0.5 rounded-full mt-1">NUEVOS USUARIOS</div>
                    </div>
                </div>

                <!-- Post 2: "Tu primer viaje GRATIS" (Phone App UI Context) -->
                <div class="aspect-square relative group overflow-hidden cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1512428559087-560fa0db99b2?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover grayscale brightness-50 contrast-125">
                    <div class="absolute inset-0 flex flex-col items-center justify-center p-2 text-center">
                        <div class="bg-[#001F5B] text-white p-3 rounded-xl shadow-lg border border-[#80C5DE] transform -rotate-3">
                            <h3 class="font-extrabold text-sm sm:text-lg leading-tight">TU PRIMER<br>VIAJE ES<br><span class="text-[#80C5DE]">GRATIS</span></h3>
                        </div>
                    </div>
                </div>

                <!-- Post 3: Moto Service (Motorcycle Helmet/Rider) -->
                <div class="aspect-square relative group overflow-hidden cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1558981403-c5f9899a28bc?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                        <div class="text-center">
                            <span class="text-4xl sm:text-5xl drop-shadow-md">🛵</span>
                            <div class="bg-[#FF5722] text-white text-xs font-bold px-2 py-1 mt-2 rounded shadow-sm transform -rotate-2">LLEGA RÁPIDO</div>
                        </div>
                    </div>
                </div>

                <!-- Post 4: Safety (Woman Looking at Phone Map) -->
                <div class="aspect-square relative group overflow-hidden cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1516738901171-8eb4fc13bd20?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover">
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black via-black/70 to-transparent p-3">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🛡️</span>
                            <div class="text-white text-left leading-none">
                                <p class="text-[10px] font-light opacity-80">MONITOREO</p>
                                <p class="text-sm font-bold">24/7 ACTIVO</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Post 5: Driver Recruitment (Happy Latino Driver) -->
                <div class="aspect-square relative group overflow-hidden cursor-pointer bg-[#001F5B]">
                     <img src="https://images.unsplash.com/photo-1507537362848-9c7e70b7b5c1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover opacity-60 mix-blend-overlay">
                     <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-2">
                         <h3 class="text-white font-bold text-lg sm:text-xl leading-tight mb-2">GENERA<br>INGRESOS<br><span class="text-[#80C5DE]">EXTRA</span></h3>
                         <button class="bg-[#80C5DE] text-[#001F5B] text-[10px] font-bold px-3 py-1.5 rounded-full shadow-md hover:scale-105 transition">REGÍSTRATE</button>
                     </div>
                </div>

                 <!-- Post 6: Car Fleet (Modern White Car) -->
                <div class="aspect-square relative group overflow-hidden cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover">
                    <div class="absolute top-2 right-2 bg-white text-black text-[10px] font-bold px-2 py-0.5 rounded shadow">COMFORT</div>
                </div>

                <!-- Post 7: Payment (Hand Holding Credit Card / Phone) -->
                <div class="aspect-square relative group overflow-hidden cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1556742049-0cfed4f7a07d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-[#001F5B]/80 flex flex-col items-center justify-center p-2 text-center opacity-0 hover:opacity-100 transition duration-300">
                        <div class="text-3xl mb-1">💳</div>
                        <p class="text-white font-bold text-sm">PAGO MÓVIL</p>
                        <p class="text-white text-xs opacity-75">ACEPTAMOS TODO</p>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-100 hover:opacity-0 transition duration-300">
                         <h3 class="text-white font-black text-xl drop-shadow-md">PAGOS<br>FÁCILES</h3>
                    </div>
                </div>

                 <!-- Post 8: Review / Stars (Happy Passengers) -->
                <div class="aspect-square relative group overflow-hidden cursor-pointer">
                    <img src="https://images.unsplash.com/photo-1532153955177-f59af40d6472?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="w-full h-full object-cover">
                    <div class="absolute bottom-2 left-2 bg-white/90 px-2 py-1 rounded shadow flex items-center gap-1">
                        <span class="text-yellow-500 text-xs">★★★★★</span>
                        <span class="text-gray-800 text-[10px] font-bold">4.9/5</span>
                    </div>
                </div>

                 <!-- Post 9: Brand Logo (Clean Vector Overlay) -->
                <div class="aspect-square bg-gray-100 relative group overflow-hidden cursor-pointer flex items-center justify-center p-8">
                     <img src="/images/vecta-logo.png" class="w-full h-auto object-contain drop-shadow-xl z-10 filter invert contrast-100 brightness-0"> <!-- Black Logo -->
                     <p class="absolute bottom-4 text-xs font-bold tracking-widest text-gray-400">VECTA</p>
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
