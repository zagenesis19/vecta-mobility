<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';
import LegalContent from '@/Components/LegalContent.vue';
import BcvCalculator from '@/Components/BcvCalculator.vue';

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

const isMobileMenuOpen = ref(false); // 🔥 Mobile Menu State
const toggleMobileMenu = () => isMobileMenuOpen.value = !isMobileMenuOpen.value;

const showLegalModal = ref(false); // ⚖️ State for Legal Modal

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

// Definición de municipios con paths SVG (trazados reales del mapa)
const municipalities = ref([]);

const fetchMunicipalities = async () => {
    try {
        const response = await axios.get('/api/municipalities');
        municipalities.value = response.data.map(m => ({
            ...m,
            path: m.svg_path,
            id: m.calibration_data?.id || m.id // Ensure ID match for map keys/logic
        }));
    } catch (error) {
        console.error("Error loading map data:", error);
    }
};

// Función para obtener el color dinámico según disponibilidad
const getMunicipalityColor = (municipalityName) => {
    const count = props.driverStats?.[municipalityName] || 0;
    return count > 0 ? '#80C5DE' : '#E5E7EB'; // Azul VECTA si hay conductores, Gris muy claro si no
};

const getMunicipalityStroke = (municipalityName) => {
    const count = props.driverStats?.[municipalityName] || 0;
    return count > 0 ? '#5BA3C5' : '#D1D5DB'; // Borde sutilmente más oscuro que el relleno
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
    // Si el evento viene del mousemove global o del SVG
    const x = event.clientX;
    const y = event.clientY;
    
    // Ajustar posición para que no tape el cursor
    tooltipPosition.value = {
        x: x, 
        y: y - 20
    };
};

// Función para generar el string de transformación
const getMunicipalityTransform = (municipality) => {
    const c = municipality.calibration_data;
    if (!c) return '';
    return `translate(${c.x}, ${c.y}) rotate(${c.r}) scale(${c.s * c.sx}, ${c.s * c.sy})`;
};

onMounted(() => {
    fetchMunicipalities();

    // Listen for real-time driver updates
    if (window.Echo) {
        window.Echo.channel('drivers')
            .listen('DriverLocationUpdated', (e) => {
                // Here we would ideally update the specific driver position or increment the count for the municipality
                // Since we only have aggregate stats, we can re-fetch or increment locally if we have the municipality name/ID
                console.log('Driver moved:', e);
                // Simple re-fetch for now to ensure consistency
                // Optimization: Update local state if e.municipalityId is known
                fetchMunicipalities(); 
            });
    }

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

        <!-- 🏦 Widget flotante tasa BCV -->
        <BcvCalculator />
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
                                Servicio
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
                                <Link v-if="canRegister" :href="route('register')" class="font-medium transition vecta-link">Registrarse</Link>
                                <Link :href="route('login')" class="px-6 py-2 rounded-lg font-bold transition shadow-md vecta-btn-primary">
                                    Iniciar Sesión
                                </Link>
                            </template>
                        </template>
                    </div>

                    <!-- HAMBURGER BUTTON (Mobile Only) -->
                    <div class="md:hidden flex items-center">
                        <button @click="toggleMobileMenu" class="text-white hover:text-blue-200 focus:outline-none transition p-2">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- MOBILE MENU DROPDOWN -->
            <transition 
                enter-active-class="transition duration-200 ease-out" 
                enter-from-class="transform -translate-y-4 opacity-0" 
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in" 
                leave-from-class="transform translate-y-0 opacity-100" 
                leave-to-class="transform -translate-y-4 opacity-0"
            >
                <div v-if="isMobileMenuOpen" class="md:hidden absolute top-16 left-0 w-full bg-[#001F5B] shadow-xl border-t border-blue-900 py-4 px-4 flex flex-col space-y-4 z-40">
                    <a href="#inicio" @click="isMobileMenuOpen = false" class="text-white hover:text-[#80C5DE] font-medium py-2 border-b border-blue-900">Inicio</a>
                    <a href="#nosotros" @click="isMobileMenuOpen = false" class="text-white hover:text-[#80C5DE] font-medium py-2 border-b border-blue-900">Nosotros</a>
                    <a href="#conductores" @click="isMobileMenuOpen = false" class="text-white hover:text-[#80C5DE] font-medium py-2 border-b border-blue-900">Servicio</a>
                    <a href="#ayuda" @click="isMobileMenuOpen = false" class="text-white hover:text-[#80C5DE] font-medium py-2 border-b border-blue-900">Ayuda</a>
                    
                    <div class="pt-2 flex flex-col gap-3">
                        <template v-if="canLogin">
                            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-center w-full px-6 py-3 rounded-lg font-bold transition shadow-md vecta-btn-primary">
                                Dashboard
                            </Link>
                            <template v-else>
                                <Link :href="route('login')" class="text-center w-full px-6 py-3 rounded-lg font-bold transition shadow-md vecta-btn-primary">
                                    Iniciar Sesión
                                </Link>
                                <Link v-if="canRegister" :href="route('register')" class="text-center w-full text-white font-medium hover:text-[#80C5DE] py-2">
                                    Registrarse
                                </Link>
                            </template>
                        </template>
                    </div>
                </div>
            </transition>
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
                            <!-- Ajuste de viewBox para que el mapa quepa dentro del recuadro -->
                            <svg viewBox="-500 -6200 9000 7000" class="w-full h-auto relative" preserveAspectRatio="xMidYMid meet">
                                <!-- Background -->
                                <rect x="-1000" y="-6000" width="10000" height="10000" fill="#001F5B" opacity="0.05"/>
                                
                                <!-- Municipios Interactivos -->
                                <g v-for="municipality in municipalities" 
                                   :key="municipality.id"
                                   :transform="getMunicipalityTransform(municipality)"
                                   class="transition-transform duration-300">
                                    <path
                                        :d="municipality.path"
                                        :fill="getMunicipalityColor(municipality.name)"
                                        :stroke="getMunicipalityStroke(municipality.name)"
                                        stroke-width="20"
                                        stroke-linejoin="round"
                                        stroke-linecap="round"
                                        vector-effect="non-scaling-stroke"
                                        class="municipality-path transition-all duration-300 cursor-pointer"
                                        :class="{ 
                                            'hovered': hoveredMunicipality?.id === municipality.id 
                                        }"
                                        @mouseenter="handleMunicipalityHover(municipality, $event)"
                                        @mousemove="updateTooltipPosition($event)"
                                        @mouseleave="handleMunicipalityLeave"
                                    />
                                </g>
                                

                            </svg>
                            
                            <!-- Tooltip -->
                            <div v-if="hoveredMunicipality" 
                                 class="fixed bg-white/95 backdrop-blur-sm px-4 py-3 rounded-xl shadow-2xl z-50 pointer-events-none transform -translate-x-1/2 -translate-y-full border border-blue-100"
                                 :style="{ left: `${tooltipPosition.x}px`, top: `${tooltipPosition.y}px` }">
                                <div class="font-bold text-gray-800">{{ hoveredMunicipality.name }}</div>
                                <div class="text-sm text-gray-600">
                                    {{ getDriverCount(hoveredMunicipality.name) }} conductor{{ getDriverCount(hoveredMunicipality.name) !== 1 ? 'es' : '' }} disponible{{ getDriverCount(hoveredMunicipality.name) !== 1 ? 's' : '' }}
                                </div>
                            </div>
                            
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
                    <a :href="route('app-store')" class="hover:scale-105 transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" class="h-12">
                    </a>
                    <a :href="route('google-play')" class="hover:scale-105 transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" class="h-12">
                    </a>
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
                            <li><a href="#" class="hover-vecta-link transition">Servicio</a></li>
                            <li><a href="#" class="hover-vecta-link transition">Ayuda</a></li>
                        </ul>
                    </div>

                    <!-- Column 2 -->
                    <div>
                        <h4 class="font-bold mb-4" style="color: #80C5DE;">Legal</h4>
                        <ul class="space-y-2" style="color: rgba(255, 255, 255, 0.8);">
                            <li>
                                <a href="#" @click.prevent="showLegalModal = true" class="hover-vecta-link transition">
                                    Términos y Condiciones
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 3 -->
                    <div>
                        <h4 class="font-bold mb-4" style="color: #80C5DE;">Síguenos</h4>
                        <div class="flex gap-4">
                            <!-- YouTube -->
                            <a href="https://youtube.com/@vectamobilityvenezuela?si=6Qt5mOygDOHveFuE" target="_blank" class="w-10 h-10 rounded-full flex items-center justify-center transition bg-white/5 hover:bg-red-600 text-white" title="YouTube">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                            </a>
                            <!-- Instagram -->
                            <a :href="route('social.instagram')" target="_blank" class="w-10 h-10 rounded-full flex items-center justify-center transition bg-white/5 hover:bg-pink-600 text-white" title="Instagram">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm7.846-10.405a1.44 1.44 0 1 1 0-2.88 1.44 1.44 0 0 1 0 2.88z"/></svg>
                            </a>
                            <!-- WhatsApp -->
                            <a href="https://wa.me/584241928802" target="_blank" class="w-10 h-10 rounded-full flex items-center justify-center transition bg-white/5 hover:bg-green-600 text-white" title="WhatsApp">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            </a>
                            <!-- Gmail -->
                            <a href="mailto:vectamobility@gmail.com" class="w-10 h-10 rounded-full flex items-center justify-center transition bg-white/5 hover:bg-red-500 text-white" title="Gmail">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.91 1.528-1.145C21.69 2.28 24 3.434 24 5.457z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="border-t mt-8 pt-8 text-center text-sm" style="border-color: rgba(128, 197, 222, 0.2); color: rgba(255, 255, 255, 0.6);">
                    Copyright © 2026 VECTA.com
                </div>
            </div>
        </footer>

        <!-- LEGAL MODAL -->
        <Teleport to="body">
            <div v-if="showLegalModal" class="fixed inset-0 z-[60] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
                <div class="fixed inset-0 bg-gray-900 opacity-90 transition-opacity" @click="showLegalModal = false"></div>
                
                <div class="bg-white rounded-2xl shadow-2xl relative z-20 w-full max-w-3xl flex flex-col max-h-[90vh]">
                    <!-- Header -->
                    <div class="px-8 py-6 border-b flex justify-between items-center bg-gray-50 rounded-t-2xl">
                        <h3 class="text-2xl font-black text-[#001F5B]">Términos y Condiciones</h3>
                        <button @click="showLegalModal = false" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="p-8 overflow-y-auto">
                        <LegalContent />
                    </div>

                    <!-- Footer -->
                    <div class="px-8 py-5 bg-gray-50 border-t rounded-b-2xl flex justify-end">
                        <button 
                            @click="showLegalModal = false"
                            class="px-8 py-3 bg-[#001F5B] text-white font-bold rounded-xl hover:bg-blue-900 transition shadow-lg"
                        >
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
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
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    transition: all 0.3s ease;
}

.municipality-path:hover,
.municipality-path.hovered {
    filter: drop-shadow(0 8px 12px rgba(0, 0, 0, 0.3)) brightness(1.2);
    stroke: #80C5DE;
    stroke-width: 8;
    z-index: 10;
}
</style>