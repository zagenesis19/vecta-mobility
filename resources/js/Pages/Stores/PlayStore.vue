<template>
    <div class="bg-white min-h-screen font-sans text-gray-900 pb-20">
        <Head title="Vecta Mobility - Apps en Google Play" />

        <!-- Google Play Header -->
        <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm flex items-center px-4 h-16 gap-4">
             <div class="text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </div>
            <div class="flex items-center gap-2 flex-1">
                <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/Google_Play_Arrow_logo.svg" alt="Google Play" class="h-6">
                <span class="text-xl text-gray-500 font-medium">Google Play</span>
            </div>
            <div class="w-8 h-8 bg-purple-600 rounded-full text-white flex items-center justify-center font-bold">A</div>
        </header>

        <main class="max-w-4xl mx-auto md:px-4 py-6">
            <!-- App Header Info -->
            <div class="flex gap-6 px-4 md:px-0 mb-6">
                 <img src="/images/vecta-logo.png" alt="Vecta Mobility" class="w-20 h-20 rounded-2xl shadow-sm border border-transparent object-contain bg-[#001F5B] p-2">
                 <div class="flex-1">
                     <h1 class="text-2xl font-medium text-gray-900 mb-1">Vecta Mobility</h1>
                     <div class="text-green-700 font-medium text-sm mb-1">Vecta Inc.</div>
                     <div class="text-gray-500 text-xs mb-3">Contiene anuncios • Compras en la aplicación</div>
                 </div>
            </div>

            <!-- Ratings & Stats -->
             <div class="flex px-4 md:px-0 mb-6 overflow-x-auto gap-6">
                 <div class="flex flex-col items-center min-w-[60px]">
                     <div class="flex items-center gap-1 font-bold text-gray-800">
                         4.8 <span class="text-xs">★</span>
                     </div>
                     <div class="text-[10px] text-gray-500">2K reseñas</div>
                 </div>
                 <div class="w-px bg-gray-300 h-8 self-center"></div>
                 <div class="flex flex-col items-center min-w-[60px]">
                     <div class="font-bold text-gray-800">50K+</div>
                     <div class="text-[10px] text-gray-500">Descargas</div>
                 </div>
                 <div class="w-px bg-gray-300 h-8 self-center"></div>
                  <div class="flex flex-col items-center min-w-[60px]">
                     <div class="w-6 h-6 bg-gray-200 rounded text-[10px] flex items-center justify-center font-bold text-gray-600">E</div>
                     <div class="text-[10px] text-gray-500">Para todos</div>
                 </div>
             </div>

             <!-- Install Button -->
             <div class="px-4 md:px-0 mb-8 w-full md:w-auto">
                 <button class="w-full md:w-auto md:px-12 bg-[#01875f] text-white py-2.5 rounded-lg font-medium text-sm hover:bg-[#017a56] transition">
                     Instalar
                 </button>
                 <div class="text-center md:text-left mt-3 text-green-700 text-sm flex items-center justify-center md:justify-start gap-1">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                     <span>Verificado por Play Protect</span>
                 </div>
             </div>

             <!-- Screenshots -->
             <div class="mb-6 overflow-x-auto flex gap-3 px-4 md:px-0 pb-4">
                 <!-- Screen 1: HTML Splash -->
                 <div class="w-32 aspect-[1/2] bg-[#001F5B] rounded-lg flex-shrink-0 border border-gray-200 shadow-sm flex flex-col items-center justify-center p-2 relative overflow-hidden">
                     <img src="/images/vecta-logo.png" alt="Vecta Logo" class="w-16 h-16 object-contain mb-2 z-10">
                     <div class="text-center z-10">
                         <div class="text-white font-bold text-sm leading-tight">Tu viaje</div>
                         <div class="text-[#80C5DE] font-black text-base tracking-wide">SEGURO</div>
                     </div>
                 </div>
                 <img src="/images/screenshots/screen2.svg" class="w-32 h-auto rounded-lg flex-shrink-0 border border-gray-200 shadow-sm" alt="Vecta Ride Options">
                 <img src="/images/screenshots/screen3.svg" class="w-32 h-auto rounded-lg flex-shrink-0 border border-gray-200 shadow-sm" alt="Vecta Driver Profile">
             </div>

             <!-- Description -->
             <div class="px-4 md:px-0 mb-8">
                 <div class="flex justify-between items-center mb-4">
                     <h2 class="text-lg font-medium text-gray-900">Sobre esta app</h2>
                     <button class="text-[#01875f] text-2xl">→</button>
                 </div>
                 <p class="text-gray-600 text-sm leading-relaxed">
                     Vecta Mobility es tu compañero de viaje en los Valles del Tuy.
                     <br><br>
                     Viaja seguro, rápido y al mejor precio. Nuestra comunidad de conductores verificados te llevará a donde necesites ir.
                     <br><br>
                     Novedades:<br>
                     - Mejoras de rendimiento<br>
                     - Corrección de errores
                 </p>
             </div>
             
             <!-- Ratings Section -->
             <div class="px-4 md:px-0">
                 <h2 class="text-lg font-medium text-gray-900 mb-4">Calificaciones y opiniones</h2>
                  <div class="space-y-6">
                      <!-- Review 1 -->
                      <div class="flex gap-4">
                          <div class="w-8 h-8 rounded-full bg-orange-400 text-white flex items-center justify-center text-xs font-bold">J</div>
                          <div>
                              <div class="text-sm font-medium">Juan Perez</div>
                              <div class="flex items-center gap-2 mb-1">
                                  <div class="text-yellow-500 text-xs">★★★★★</div>
                                  <div class="text-gray-400 text-xs">12/02/26</div>
                              </div>
                              <p class="text-gray-600 text-sm">Excelente aplicación, muy útil para moverse por Charallave.</p>
                          </div>
                      </div>
                      
                       <!-- Review 2 -->
                      <div class="flex gap-4">
                          <div class="w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-xs font-bold">L</div>
                          <div>
                              <div class="text-sm font-medium">Laura Diaz</div>
                              <div class="flex items-center gap-2 mb-1">
                                  <div class="text-yellow-500 text-xs">★★★★☆</div>
                                  <div class="text-gray-400 text-xs">10/02/26</div>
                              </div>
                              <p class="text-gray-600 text-sm">Funciona bien, aunque a veces tarda en cargar el mapa.</p>
                          </div>
                      </div>
                  </div>
             </div>

        </main>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
</script>
