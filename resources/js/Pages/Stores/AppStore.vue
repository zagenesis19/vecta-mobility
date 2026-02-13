<template>
    <div class="bg-white min-h-screen font-sans text-gray-900 pb-20">
        <Head title="Vecta Mobility en App Store" />

        <!-- App Store Header -->
        <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100">
            <div class="max-w-5xl mx-auto px-4 py-3 flex justify-between items-center">
                <div class="text-blue-600 font-semibold flex items-center gap-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Atrás
                </div>
                <div class="text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                </div>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 py-6">
            <!-- App Header Info -->
            <div class="flex gap-6 mb-8">
                <img src="/images/vecta-logo.png" alt="Vecta Mobility" class="w-28 h-28 rounded-[2rem] shadow-sm border border-transparent object-contain bg-[#001F5B] p-2">
                <div class="flex-1 flex flex-col justify-between py-1">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight mb-1">Vecta Mobility</h1>
                        <p class="text-gray-500 text-sm mb-1">Tu viaje seguro en los Valles del Tuy</p>
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-4 h-4 rounded-full bg-blue-500 flex items-center justify-center text-[0.6rem] text-white font-bold">✓</span>
                            <span class="text-gray-400 text-xs">Vecta Inc.</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-end">
                        <button class="bg-blue-600 text-white px-6 py-1.5 rounded-full font-bold text-sm hover:bg-blue-700 transition">
                            OBTENER
                        </button>
                        <div class="text-gray-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="flex justify-between py-4 border-t border-b border-gray-100 mb-8 overflow-x-auto">
                <div class="text-center px-4 border-r border-gray-100 last:border-0 min-w-[80px]">
                    <div class="text-gray-400 text-xs font-semibold mb-1">CALIFICACIONES</div>
                    <div class="font-bold text-xl text-gray-900">4.9</div>
                    <div class="text-gray-400 text-xs">★★★★★</div>
                </div>
                <div class="text-center px-4 border-r border-gray-100 last:border-0 min-w-[80px]">
                    <div class="text-gray-400 text-xs font-semibold mb-1">EDAD</div>
                    <div class="font-bold text-xl text-gray-900">4+</div>
                    <div class="text-gray-400 text-xs">Años</div>
                </div>
                <div class="text-center px-4 border-r border-gray-100 last:border-0 min-w-[80px]">
                    <div class="text-gray-400 text-xs font-semibold mb-1">RANKING</div>
                    <div class="font-bold text-xl text-gray-900">#1</div>
                    <div class="text-gray-400 text-xs">Viajes</div>
                </div>
                <div class="text-center px-4 min-w-[80px]">
                    <div class="text-gray-400 text-xs font-semibold mb-1">IDIOMA</div>
                    <div class="font-bold text-xl text-gray-900">ES</div>
                    <div class="text-gray-400 text-xs">Español</div>
                </div>
            </div>

            <!-- Screenshots -->
            <div class="mb-8">
                <h2 class="text-xl font-bold mb-4">Novedades</h2>
                <div class="text-sm text-gray-500 mb-6">
                    <p>Versión 2.0.4</p>
                    <p>• Mejoras en la detección de ubicación.</p>
                    <p>• Nuevas rutas en Santa Teresa.</p>
                </div>

                <h2 class="text-xl font-bold mb-4">Vista Previa</h2>
                <!-- Fake carousel -->
                <div class="flex gap-4 overflow-x-auto pb-4 snap-x">
                    <!-- Screen 1: HTML Splash with Real Logo -->
                    <div class="w-64 aspect-[1/2] bg-[#001F5B] rounded-3xl flex-shrink-0 snap-center border-4 border-gray-200 shadow-sm flex flex-col items-center justify-center p-6 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_50%_120%,rgba(128,197,222,0.1),transparent)]"></div>
                        <img src="/images/vecta-logo.png" alt="Vecta Logo" class="w-32 h-32 object-contain mb-6 z-10">
                        <div class="text-center z-10">
                            <div class="text-white font-bold text-2xl leading-tight">Tu viaje</div>
                            <div class="text-[#80C5DE] font-black text-3xl tracking-wide">SEGURO</div>
                        </div>
                         <div class="absolute bottom-12 bg-[#80C5DE] text-[#001F5B] px-6 py-2 rounded-full font-bold text-sm shadow-lg">
                            Descargar Gratis
                        </div>
                    </div>
                    <img src="/images/screenshots/screen2.svg" class="w-64 h-auto rounded-3xl flex-shrink-0 snap-center border-4 border-gray-200 shadow-sm object-cover" alt="Vecta Ride Options">
                    <img src="/images/screenshots/screen3.svg" class="w-64 h-auto rounded-3xl flex-shrink-0 snap-center border-4 border-gray-200 shadow-sm object-cover" alt="Vecta Driver Profile">
                </div>
            </div>

             <!-- Description -->
             <div class="mb-8">
                 <p class="text-gray-800 leading-relaxed text-sm">
                     Vecta Mobility es la aplicación líder de transporte en los Valles del Tuy. Conecta con conductores confiables en minutos y muévete con total libertad y seguridad por Cúa, Charallave, Ocumare y más.
                     <br><br>
                     Características principales:
                     <br>• Viajes seguros y monitoreados.
                     <br>• Tarifas justas sin sorpresas.
                     <br>• Soporte 24/7.
                 </p>
             </div>
             
             <!-- Reviews -->
             <div>
                 <h2 class="text-xl font-bold mb-4">Reseñas y Calificaciones</h2>
                  <div class="bg-gray-50 p-4 rounded-xl mb-4">
                      <div class="flex justify-between items-center mb-2">
                          <span class="font-bold text-sm">Excelente servicio</span>
                          <span class="text-gray-400 text-xs">Hace 2d</span>
                      </div>
                      <div class="text-yellow-500 text-xs mb-2">★★★★★</div>
                      <p class="text-gray-600 text-sm">Me encanta usar Vecta para ir al trabajo. Los conductores son muy amables.</p>
                      <div class="text-gray-400 text-xs mt-2">Usuario123</div>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-xl">
                      <div class="flex justify-between items-center mb-2">
                          <span class="font-bold text-sm">Muy rápida</span>
                          <span class="text-gray-400 text-xs">Hace 5d</span>
                      </div>
                      <div class="text-yellow-500 text-xs mb-2">★★★★★</div>
                      <p class="text-gray-600 text-sm">La mejor opción en los Valles del Tuy. Recomendada 100%.</p>
                      <div class="text-gray-400 text-xs mt-2">MariaPerez</div>
                  </div>
             </div>

        </main>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
</script>
