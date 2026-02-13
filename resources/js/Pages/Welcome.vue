<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    driverStats: Object,
});

const origin = ref('');
const destination = ref('');
const originLat = ref(null);
const originLng = ref(null);
const destinationLat = ref(null);
const destinationLng = ref(null);

const originSearchResults = ref([]);
const searchResults = ref([]); // For destination
let originDebounce = null;
let destDebounce = null;

// --- Geolocation & Search Logic ---

const refreshCurrentLocation = () => {
    origin.value = '📍 Localizando...';
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                originLat.value = lat;
                originLng.value = lng;
                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                    const data = await res.json();
                    if (data?.display_name) origin.value = "📍 " + data.display_name.split(',')[0];
                } catch (e) { origin.value = "📍 Ubicación detectada"; }
            },
            () => { origin.value = '❌ Error de ubicación'; }
        );
    } else {
        origin.value = '❌ GPS no disponible';
    }
};

watch(origin, (newVal) => {
    clearTimeout(originDebounce);
    originDebounce = setTimeout(async () => {
        if (!newVal || newVal.length < 3) { originSearchResults.value = []; return; }
        // Don't search if it looks like a selected address or locating msg
        if (newVal.startsWith('📍')) return; 
        
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(newVal)}&countrycodes=ve&limit=5`);
            originSearchResults.value = await res.json();
        } catch (e) {}
    }, 300);
});

const selectOrigin = (place) => {
    origin.value = "📍 " + place.display_name.split(',')[0]; 
    originLat.value = parseFloat(place.lat);
    originLng.value = parseFloat(place.lon);
    originSearchResults.value = []; 
};

watch(destination, (newVal) => {
    clearTimeout(destDebounce);
    destDebounce = setTimeout(async () => {
        if (!newVal || newVal.length < 3) { searchResults.value = []; return; }
        if (newVal.startsWith('🏁')) return; // Avoid search loop on selection
        
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(newVal)}&countrycodes=ve&limit=5`);
            searchResults.value = await res.json();
        } catch (e) {}
    }, 300);
});

const selectDestination = (place) => {
    destination.value = place.display_name.split(',')[0]; 
    destinationLat.value = parseFloat(place.lat);
    destinationLng.value = parseFloat(place.lon);
    searchResults.value = []; 
};

const handleRequestRide = () => {
    const params = {
        origin_address: origin.value?.replace('📍 ', '') || '',
        origin_lat: originLat.value || '',
        origin_lng: originLng.value || '',
        destination_address: destination.value?.replace('🏁 ', '') || '', 
        destination_lat: destinationLat.value || '',
        destination_lng: destinationLng.value || '',
    };
    
    // Filter out empty params
    const query = Object.fromEntries(Object.entries(params).filter(([_, v]) => v != null && v !== ''));
    
    router.get(route('dashboard'), query);
};

// Spotlight effect
const heroSection = ref(null);
const spotlightX = ref(50);
const spotlightY = ref(50);

// Magic Line Navigation
const navLinks = ref(null);
const magicLine = ref(null);
const activeSection = ref('inicio');

const handleMouseMove = (e) => {
    if (!heroSection.value) return;
    const rect = heroSection.value.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    spotlightX.value = x;
    spotlightY.value = y;
};

const updateMagicLine = (target) => {
    if (!magicLine.value || !target) return;
    const rect = target.getBoundingClientRect();
    const navRect = navLinks.value.getBoundingClientRect();
    magicLine.value.style.width = `${rect.width}px`;
    magicLine.value.style.left = `${rect.left - navRect.left}px`;
};

const handleNavHover = (e) => {
    if (e.target.tagName === 'A') {
        updateMagicLine(e.target);
    }
};

const handleNavLeave = () => {
    const activeLink = document.querySelector(`a[href="#${activeSection.value}"]`);
    if (activeLink) {
        updateMagicLine(activeLink);
    }
};

const handleScroll = () => {
    const sections = ['inicio', 'nosotros', 'conductores', 'ayuda'];
    const scrollPos = window.scrollY + 100;
    
    for (const section of sections) {
        const element = document.getElementById(section);
        if (element) {
            const offsetTop = element.offsetTop;
            const offsetBottom = offsetTop + element.offsetHeight;
            
            if (scrollPos >= offsetTop && scrollPos < offsetBottom) {
                activeSection.value = section;
                const activeLink = document.querySelector(`a[href="#${section}"]`);
                if (activeLink) {
                    updateMagicLine(activeLink);
                }
                break;
            }
        }
    }
};

// Interactive Map Logic
const hoveredMunicipality = ref(null);
const tooltipPosition = ref({ x: 0, y: 0 });

// Definición de municipios con paths SVG (trazados del mapa real)
const municipalities = ref([
    {
        id: 'cua',
        name: 'Cúa',
        // Municipio Urdaneta (izquierda, forma irregular)
        path: 'M 150 180 L 200 150 L 280 160 L 320 200 L 310 280 L 260 320 L 180 310 L 140 260 Z',
    },
    {
        id: 'charallave',
        name: 'Charallave',
        // Municipio Cristóbal Rojas (centro-norte, forma alargada)
        path: 'M 320 200 L 420 180 L 480 200 L 520 240 L 500 300 L 440 320 L 380 310 L 310 280 Z',
    },
    {
        id: 'yare',
        name: 'San Francisco de Yare',
        // Municipio Simón Bolívar (centro, forma compacta)
        path: 'M 380 310 L 440 320 L 480 360 L 460 420 L 400 440 L 340 420 L 320 370 Z',
    },
    {
        id: 'ocumare',
        name: 'Ocumare del Tuy',
        // Municipio Tomás Lander (derecha, más grande)
        path: 'M 520 240 L 620 220 L 680 260 L 700 320 L 680 400 L 620 440 L 560 450 L 500 420 L 480 360 L 500 300 Z',
    },
    {
        id: 'santa_teresa',
        name: 'Santa Teresa del Tuy',
        // Municipio Independencia (abajo-izquierda)
        path: 'M 180 310 L 260 320 L 310 280 L 320 370 L 300 430 L 240 460 L 170 450 L 140 400 L 150 340 Z',
    },
    {
        id: 'santa_lucia',
        name: 'Santa Lucía del Tuy',
        // Municipio Paz Castillo (abajo-centro)
        path: 'M 320 370 L 340 420 L 400 440 L 460 420 L 500 420 L 520 480 L 480 520 L 400 530 L 320 520 L 280 480 L 300 430 Z',
    },
]);

// Función para obtener el color según disponibilidad
const getMunicipalityColor = (municipalityName) => {
    const count = props.driverStats?.[municipalityName] || 0;
    return count > 0 ? '#80C5DE' : '#D1D5DB'; // Azul VECTA o gris
};

// Función para obtener el conteo de conductores
const getDriverCount = (municipalityName) => {
    return props.driverStats?.[municipalityName] || 0;
};

// Manejo de hover
const handleMunicipalityHover = (municipality, event) => {
    hoveredMunicipality.value = municipality;
    updateTooltipPosition(event);
};

const handleMunicipalityLeave = () => {
    hoveredMunicipality.value = null;
};

const updateTooltipPosition = (event) => {
    const svg = event.currentTarget.closest('svg');
    if (svg) {
        const rect = svg.getBoundingClientRect();
        tooltipPosition.value = {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top - 20,
        };
    }
};

onMounted(() => {
    if (heroSection.value) {
        heroSection.value.addEventListener('mousemove', handleMouseMove);
    }
    
    // Initialize magic line
    setTimeout(() => {
        const firstLink = document.querySelector('a[href="#inicio"]');
        if (firstLink) {
            updateMagicLine(firstLink);
        }
    }, 100);
    
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    if (heroSection.value) {
        heroSection.value.removeEventListener('mousemove', handleMouseMove);
    }
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <Head title="Vecta - Tu viaje seguro en los Valles del Tuy" />

    <div class="min-h-screen" style="background-color: #FFFFFF;">
        <!-- NAVBAR -->
        <nav class="shadow-sm sticky top-0 z-50" style="background-color: #001F5B;">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center gap-3">
                        <img src="/images/vecta-logo.png" alt="VECTA" class="h-20 w-auto">
                    </div>

                    <!-- Nav Links -->
                    <div class="hidden md:flex items-center gap-8 relative">
                        <div 
                            ref="navLinks" 
                            class="flex items-center gap-8 relative"
                            @mouseenter="handleNavHover"
                            @mousemove="handleNavHover"
                            @mouseleave="handleNavLeave"
                        >
                            <a 
                                href="#inicio" 
                                class="nav-link font-medium transition-colors py-2 relative"
                                :class="activeSection === 'inicio' ? 'vecta-link-active' : 'vecta-link'"
                            >
                                Inicio
                            </a>
                            <a 
                                href="#nosotros" 
                                class="nav-link font-medium transition-colors py-2 relative"
                                :class="activeSection === 'nosotros' ? 'vecta-link-active' : 'vecta-link'"
                            >
                                Nosotros
                            </a>
                            <a 
                                href="#conductores" 
                                class="nav-link font-medium transition-colors py-2 relative"
                                :class="activeSection === 'conductores' ? 'vecta-link-active' : 'vecta-link'"
                            >
                                Conductores
                            </a>
                            <a 
                                href="#ayuda" 
                                class="nav-link font-medium transition-colors py-2 relative"
                                :class="activeSection === 'ayuda' ? 'vecta-link-active' : 'vecta-link'"
                            >
                                Ayuda
                            </a>
                            
                            <!-- Magic Line -->
                            <div 
                                ref="magicLine"
                                class="absolute bottom-0 h-0.5 rounded-full transition-all duration-300 ease-out shadow-lg"
                                style="background: linear-gradient(to right, #80C5DE, #80C5DE); width: 0; left: 0;"
                            ></div>
                        </div>
                        
                        <template v-if="canLogin">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="px-6 py-2 rounded-lg font-bold transition shadow-md vecta-btn-primary">
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link :href="route('login')" class="font-medium transition vecta-link">Iniciar Sesión</Link>
                                <Link v-if="canRegister" :href="route('register')" class="px-6 py-2 rounded-lg font-bold transition shadow-md vecta-btn-primary">
                                    Descargar la App
                                </Link>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- HERO SECTION -->
        <section 
            ref="heroSection"
            id="inicio" 
            class="relative text-white overflow-hidden"
            style="background: linear-gradient(135deg, #001F5B 0%, #003580 100%);"
        >
            <!-- Spotlight Effect Overlay -->
            <div 
                class="absolute inset-0 pointer-events-none transition-opacity duration-300"
                :style="{
                    background: `radial-gradient(600px circle at ${spotlightX}% ${spotlightY}%, rgba(128, 197, 222, 0.15), transparent 40%)`
                }"
            ></div>

            <!-- Interactive Floating Elements -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <!-- Coche flotante 1 (se mueve con parallax) -->
                <div 
                    class="absolute transition-all duration-700 ease-out"
                    :style="{
                        top: '15%',
                        right: `${20 + (spotlightX - 50) * 0.05}%`,
                        transform: `translate(${(spotlightX - 50) * 0.3}px, ${(spotlightY - 50) * 0.2}px) rotate(-15deg)`
                    }"
                >
                    <svg class="w-24 h-24 opacity-20" style="color: #80C5DE;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                    </svg>
                </div>

                <!-- Pin de ubicación flotante -->
                <div 
                    class="absolute transition-all duration-500 ease-out"
                    :style="{
                        top: `${30 + (spotlightY - 50) * 0.1}%`,
                        left: `${15 + (spotlightX - 50) * 0.08}%`,
                        transform: `translate(${(spotlightX - 50) * -0.2}px, ${(spotlightY - 50) * 0.3}px) scale(${1 + (spotlightY - 50) * 0.002})`
                    }"
                >
                    <svg class="w-16 h-16 text-blue-300 opacity-25" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </div>

                <!-- Ruta animada (línea curva) -->
                <svg 
                    class="absolute w-full h-full opacity-10 transition-all duration-1000"
                    :style="{
                        transform: `translate(${(spotlightX - 50) * 0.1}px, ${(spotlightY - 50) * 0.1}px)`
                    }"
                    viewBox="0 0 800 600"
                >
                    <path 
                        d="M100,300 Q250,150 400,300 T700,300" 
                        stroke="white" 
                        stroke-width="3" 
                        fill="none"
                        stroke-dasharray="10,10"
                        class="animate-dash"
                    />
                    <path 
                        d="M150,400 Q300,250 450,400 T750,400" 
                        stroke="white" 
                        stroke-width="2" 
                        fill="none"
                        stroke-dasharray="8,8"
                        class="animate-dash-slow"
                    />
                </svg>

                <!-- Coche flotante 2 (más pequeño, abajo) -->
                <div 
                    class="absolute transition-all duration-600 ease-out"
                    :style="{
                        bottom: '20%',
                        left: `${25 + (spotlightX - 50) * 0.06}%`,
                        transform: `translate(${(spotlightX - 50) * -0.25}px, ${(spotlightY - 50) * -0.15}px) rotate(10deg)`
                    }"
                >
                    <svg class="w-20 h-20 text-blue-500 opacity-15" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                    </svg>
                </div>

                <!-- Círculos decorativos con efecto parallax -->
                <div 
                    class="absolute w-64 h-64 rounded-full bg-blue-400 opacity-5 blur-3xl transition-all duration-700"
                    :style="{
                        top: `${10 + (spotlightY - 50) * 0.15}%`,
                        right: `${5 + (spotlightX - 50) * 0.1}%`,
                        transform: `scale(${1 + (spotlightX - 50) * 0.003})`
                    }"
                ></div>

                <div 
                    class="absolute w-96 h-96 rounded-full bg-blue-300 opacity-5 blur-3xl transition-all duration-1000"
                    :style="{
                        bottom: `${5 + (spotlightY - 50) * -0.1}%`,
                        left: `${10 + (spotlightX - 50) * -0.08}%`,
                        transform: `scale(${1 + (spotlightY - 50) * 0.002})`
                    }"
                ></div>

                <!-- Pin de destino (esquina superior izquierda) -->
                <div 
                    class="absolute transition-all duration-500 ease-out"
                    :style="{
                        top: `${25 + (spotlightY - 50) * 0.12}%`,
                        right: `${30 + (spotlightX - 50) * 0.1}%`,
                        transform: `translate(${(spotlightX - 50) * 0.25}px, ${(spotlightY - 50) * -0.2}px)`
                    }"
                >
                    <svg class="w-12 h-12 text-green-300 opacity-20" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                    </svg>
                </div>

                <!-- Velocímetro decorativo -->
                <div 
                    class="absolute transition-all duration-800 ease-out"
                    :style="{
                        bottom: `${35 + (spotlightY - 50) * -0.08}%`,
                        right: `${15 + (spotlightX - 50) * 0.12}%`,
                        transform: `translate(${(spotlightX - 50) * 0.2}px, ${(spotlightY - 50) * 0.25}px) rotate(${(spotlightX - 50) * 0.1}deg)`
                    }"
                >
                    <svg class="w-16 h-16 text-yellow-300 opacity-15" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.31-8.86c-1.77-.45-2.34-.94-2.34-1.67 0-.84.79-1.43 2.1-1.43 1.38 0 1.9.66 1.94 1.64h1.71c-.05-1.34-.87-2.57-2.49-2.97V5H10.9v1.69c-1.51.32-2.72 1.3-2.72 2.81 0 1.79 1.49 2.69 3.66 3.21 1.95.46 2.34 1.15 2.34 1.87 0 .53-.39 1.39-2.1 1.39-1.6 0-2.23-.72-2.32-1.64H8.04c.1 1.7 1.36 2.66 2.86 2.97V19h2.34v-1.67c1.52-.29 2.72-1.16 2.73-2.77-.01-2.2-1.9-2.96-3.66-3.42z"/>
                    </svg>
                </div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <!-- Left Content -->
                    <div class="space-y-6">
                        <h1 class="text-4xl lg:text-6xl font-black leading-tight" style="color: #FFFFFF;">
                            Tu viaje seguro<br/>
                            <span style="color: #80C5DE;">en los Valles del Tuy.</span>
                        </h1>
                        <p class="text-lg max-w-lg" style="color: rgba(255, 255, 255, 0.9);">
                            Conecta con conductores confiables en minutos. ¡Muévete con libertad!
                        </p>
                        
                        <!-- Mobile App Badges -->
                        <div class="flex gap-4 pt-4">
                            <Link :href="route('app-store')" class="hover:scale-105 transition">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" class="h-12">
                            </Link>
                            <Link :href="route('google-play')" class="hover:scale-105 transition">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" class="h-12">
                            </Link>
                        </div>
                    </div>

                    <!-- Right - Quick Search Form -->
                    <div class="rounded-2xl shadow-2xl p-8" style="background-color: #FFFFFF;">
                        <h3 class="text-xl font-bold mb-6" style="color: #001F5B;">Solicita tu viaje ahora</h3>
                        <form @submit.prevent class="space-y-4">
                            <div class="relative">
                                <label class="block text-sm font-medium mb-2" style="color: #001F5B;">Origen</label>
                                <div class="flex gap-2">
                                    <input 
                                        v-model="origin"
                                        type="text" 
                                        placeholder="📍 ¿Dónde estás?" 
                                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent text-gray-900 placeholder-gray-500"
                                        style="--tw-ring-color: #80C5DE;"
                                    >
                                    <button 
                                        type="button"
                                        @click="refreshCurrentLocation" 
                                        class="bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 rounded-lg transition-colors" 
                                        title="Usar mi ubicación actual"
                                    >
                                        📍
                                    </button>
                                </div>
                                <!-- Dropdown Resultados Origen -->
                                <ul v-if="originSearchResults.length > 0" class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto">
                                    <li 
                                        v-for="place in originSearchResults" 
                                        :key="place.place_id" 
                                        @click="selectOrigin(place)" 
                                        class="px-4 py-3 hover:bg-blue-50 cursor-pointer text-sm border-b last:border-0 transition-colors text-gray-700"
                                    >
                                        📍 {{ place.display_name }}
                                    </li>
                                </ul>
                            </div>

                            <div class="relative">
                                <label class="block text-sm font-medium mb-2" style="color: #001F5B;">Destino</label>
                                <input 
                                    v-model="destination"
                                    type="text" 
                                    placeholder="🏁 ¿A dónde vas?" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent text-gray-900 placeholder-gray-500"
                                    style="--tw-ring-color: #80C5DE;"
                                >
                                <!-- Dropdown Resultados Destino -->
                                <ul v-if="searchResults.length > 0" class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-xl mt-1 max-h-60 overflow-y-auto">
                                    <li 
                                        v-for="place in searchResults" 
                                        :key="place.place_id" 
                                        @click="selectDestination(place)" 
                                        class="px-4 py-3 hover:bg-blue-50 cursor-pointer text-sm border-b last:border-0 transition-colors text-gray-700"
                                    >
                                        🏁 {{ place.display_name }}
                                    </li>
                                </ul>
                            </div>
                            <button 
                                type="button"
                                @click="handleRequestRide"
                                class="block w-full text-center py-3 rounded-lg font-bold transition shadow-lg vecta-btn-primary text-white hover:scale-[1.02]"
                            >
                                Solicitar VECTA ahora
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- BENEFITS SECTION -->
        <section id="nosotros" class="py-20" style="background-color: #F4F6F8;">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-black text-center mb-16" style="color: #001F5B;">Beneficios</h2>
                
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Seguridad -->
                    <div class="benefit-card group bg-white rounded-2xl p-8 text-center space-y-4 shadow-md hover:shadow-2xl transition-all duration-300 cursor-pointer transform hover:scale-105 hover:-translate-y-2">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full transition-all duration-300 group-hover:scale-110" style="background-color: rgba(128, 197, 222, 0.2);">
                            <svg class="w-10 h-10 transition-transform duration-300 group-hover:scale-110" style="color: #80C5DE;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold transition-colors duration-300" style="color: #001F5B;">Seguridad</h3>
                        <p class="text-gray-600 transition-colors duration-300 group-hover:text-gray-700">
                            Conecta con conductores verificados en minutos. Múltiples opciones de seguridad.
                        </p>
                    </div>

                    <!-- Rapidez -->
                    <div class="benefit-card group bg-white rounded-2xl p-8 text-center space-y-4 shadow-md hover:shadow-2xl transition-all duration-300 cursor-pointer transform hover:scale-105 hover:-translate-y-2">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full transition-all duration-300 group-hover:scale-110" style="background-color: rgba(128, 197, 222, 0.2);">
                            <svg class="w-10 h-10 transition-transform duration-300 group-hover:scale-110" style="color: #80C5DE;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold transition-colors duration-300" style="color: #001F5B;">Rapidez</h3>
                        <p class="text-gray-600 transition-colors duration-300 group-hover:text-gray-700">
                            Velocidad al camino. Ahorra tiempo, no importa que tan lejos te lleva la ruta conseguida.
                        </p>
                    </div>

                    <!-- Mejor Tarifa -->
                    <div class="benefit-card group bg-white rounded-2xl p-8 text-center space-y-4 shadow-md hover:shadow-2xl transition-all duration-300 cursor-pointer transform hover:scale-105 hover:-translate-y-2">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full transition-all duration-300 group-hover:scale-110" style="background-color: rgba(128, 197, 222, 0.2);">
                            <svg class="w-10 h-10 transition-transform duration-300 group-hover:scale-110" style="color: #80C5DE;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold transition-colors duration-300" style="color: #001F5B;">Mejor Tarifa</h3>
                        <p class="text-gray-600 transition-colors duration-300 group-hover:text-gray-700">
                            Mejor tarifa económica sin precio al proyecto, para no segurarte tu precio al precio.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- COVERAGE AREA SECTION -->
        <section id="conductores" class="py-20" style="background-color: #FFFFFF;">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <!-- Left - Coverage List -->
                    <div>
                        <h2 class="text-3xl font-black mb-4" style="color: #001F5B;">Áreas de Servicio</h2>
                        <h3 class="text-4xl font-black mb-8" style="color: #80C5DE;">Dónde te llevamos</h3>
                        
                        <div class="space-y-4 text-gray-700">
                            <div class="flex items-start gap-2">
                                <span class="font-bold" style="color: #80C5DE;">•</span>
                                <p><strong>Municipio Urdaneta:</strong> Cúa, Nueva Cúa.</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-blue-600 font-bold">•</span>
                                <p><strong>Municipio Cristóbal Rojas:</strong> Charallave, Las Brisas.</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-blue-600 font-bold">•</span>
                                <p><strong>Municipio Simón Bolívar:</strong> San Francisco de Yare, San Antonio de Yare.</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-blue-600 font-bold">•</span>
                                <p><strong>Municipio Tomás Lander:</strong> Ocumare del Tuy, La Democracia, Santa Bárbara, La Mata, La Cabrera.</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-blue-600 font-bold">•</span>
                                <p><strong>Municipio Independencia:</strong> Santa Teresa del Tuy, El Cartanal.</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-blue-600 font-bold">•</span>
                                <p><strong>Municipio Paz Castillo:</strong> Santa Lucía del Tuy, Santa Rita, Siquire, Soapire.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right - Interactive Map -->
                    <div class="relative">
                        <div class="rounded-2xl p-8 shadow-2xl" style="background-color: #001F5B;">
                            <h4 class="text-white text-center font-bold mb-4">Disponibilidad en Tiempo Real</h4>
                            
                            <!-- SVG Interactive Map -->
                            <svg viewBox="0 0 800 600" class="w-full h-auto relative">
                                <!-- Background -->
                                <rect x="0" y="0" width="800" height="600" fill="#001F5B" opacity="0.1"/>
                                
                                <!-- Municipios Interactivos -->
                                <g v-for="municipality in municipalities" :key="municipality.id">
                                    <path
                                        :d="municipality.path"
                                        :fill="getMunicipalityColor(municipality.name)"
                                        stroke="white"
                                        stroke-width="2"
                                        class="municipality-path transition-all duration-300 cursor-pointer"
                                        :class="{ 'hovered': hoveredMunicipality?.id === municipality.id }"
                                        @mouseenter="handleMunicipalityHover(municipality, $event)"
                                        @mousemove="updateTooltipPosition($event)"
                                        @mouseleave="handleMunicipalityLeave"
                                    />
                                </g>
                                
                                <!-- Tooltip -->
                                <g v-if="hoveredMunicipality" class="pointer-events-none">
                                    <rect
                                        :x="tooltipPosition.x - 80"
                                        :y="tooltipPosition.y - 50"
                                        width="160"
                                        height="50"
                                        rx="8"
                                        fill="white"
                                        stroke="#80C5DE"
                                        stroke-width="2"
                                        opacity="0.95"
                                    />
                                    <text
                                        :x="tooltipPosition.x"
                                        :y="tooltipPosition.y - 30"
                                        text-anchor="middle"
                                        fill="#001F5B"
                                        font-size="14"
                                        font-weight="bold"
                                    >
                                        {{ hoveredMunicipality.name }}
                                    </text>
                                    <text
                                        :x="tooltipPosition.x"
                                        :y="tooltipPosition.y - 12"
                                        text-anchor="middle"
                                        :fill="getDriverCount(hoveredMunicipality.name) > 0 ? '#80C5DE' : '#9CA3AF'"
                                        font-size="16"
                                        font-weight="bold"
                                    >
                                        {{ getDriverCount(hoveredMunicipality.name) }} conductor{{ getDriverCount(hoveredMunicipality.name) !== 1 ? 'es' : '' }}
                                    </text>
                                </g>
                                
                                <!-- Título -->
                                <text x="400" y="580" text-anchor="middle" fill="white" font-size="18" font-weight="bold">
                                    Valles del Tuy
                                </text>
                            </svg>
                            
                            <!-- Leyenda -->
                            <div class="flex justify-center gap-6 mt-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded" style="background-color: #80C5DE;"></div>
                                    <span class="text-white">Disponible</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 rounded bg-gray-400"></div>
                                    <span class="text-white">Sin conductores</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA SECTION -->
        <section id="ayuda" class="text-white py-16" style="background: linear-gradient(to right, #001F5B, #003580);">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-black mb-6">Empieza a viajar hoy.</h2>
                <div class="flex justify-center gap-4">
                    <Link :href="route('app-store')" class="hover:scale-105 transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" class="h-14">
                    </Link>
                    <Link :href="route('google-play')" class="hover:scale-105 transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" class="h-14">
                    </Link>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="border-t py-12" style="background-color: #001F5B; border-color: rgba(128, 197, 222, 0.2);">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Column 1 -->
                    <div>
                        <h4 class="font-bold mb-4" style="color: #80C5DE;">Inicio</h4>
                        <ul class="space-y-2" style="color: rgba(255, 255, 255, 0.8);">
                            <li><a href="#" class="hover-vecta-link transition">Nosotros</a></li>
                            <li><a href="#" class="hover-vecta-link transition">Conductores</a></li>
                            <li><a href="#" class="hover-vecta-link transition">Ayuda</a></li>
                        </ul>
                    </div>

                    <!-- Column 2 -->
                    <div>
                        <h4 class="font-bold mb-4" style="color: #80C5DE;">Legal</h4>
                        <ul class="space-y-2" style="color: rgba(255, 255, 255, 0.8);">
                            <li><a href="#" class="hover-vecta-link transition">Términos de servicio</a></li>
                            <li><a href="#" class="hover-vecta-link transition">Privacidad</a></li>
                            <li><a href="#" class="hover-vecta-link transition">Contacto</a></li>
                        </ul>
                    </div>

                    <!-- Column 3 -->
                    <div>
                        <h4 class="font-bold mb-4" style="color: #80C5DE;">Síguenos</h4>
                        <div class="flex gap-4">
                            <a href="#" class="w-10 h-10 rounded-full flex items-center justify-center transition" style="background-color: rgba(128, 197, 222, 0.2); color: #80C5DE;" onmouseover="this.style.backgroundColor='#80C5DE'; this.style.color='#FFFFFF';" onmouseout="this.style.backgroundColor='rgba(128, 197, 222, 0.2)'; this.style.color='#80C5DE';">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                            <a href="#" class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="border-t mt-8 pt-8 text-center text-sm" style="border-color: rgba(128, 197, 222, 0.2); color: rgba(255, 255, 255, 0.6);">
                    Copyright © 2026 VECTA.com
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Smooth scroll behavior */
html {
    scroll-behavior: smooth;
}

/* Animated dashed lines (rutas en movimiento) */
@keyframes dash {
    to {
        stroke-dashoffset: -100;
    }
}

@keyframes dash-slow {
    to {
        stroke-dashoffset: -80;
    }
}

.animate-dash {
    animation: dash 20s linear infinite;
}

.animate-dash-slow {
    animation: dash-slow 30s linear infinite;
}

/* VECTA Brand Colors */
.vecta-btn-primary {
    background-color: #80C5DE;
    color: #FFFFFF;
}

.vecta-btn-primary:hover {
    background-color: #6BB5CE;
    transform: translateY(-1px);
}

.vecta-link {
    color: rgba(255, 255, 255, 0.8);
}

.vecta-link:hover {
    color: #80C5DE;
}

.vecta-link-active {
    color: #80C5DE;
}

.hover-vecta-link:hover {
    color: #80C5DE;
}

/* Interactive Map Styles */
.municipality-path {
    filter: brightness(1);
    transform-origin: center;
}

.municipality-path:hover,
.municipality-path.hovered {
    filter: brightness(1.2);
    transform: scale(1.05);
    stroke-width: 3;
}
</style>