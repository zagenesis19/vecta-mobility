<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    driverStats: Object,
});

const origin = ref('');
const destination = ref('');

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
const municipalities = ref([
    {
        id: 'urdaneta',
        name: 'Cúa',
        // Municipio Urdaneta - Path real
        path: 'M1946 2906 c-21 -14 -42 -29 -45 -34 -3 -5 -17 -12 -31 -15 -14 -3 -43 -10 -65 -16 -22 -6 -59 -11 -82 -11 -23 0 -45 -4 -48 -10 -3 -5 -21 -10 -40 -10 -19 0 -37 -5 -40 -10 -4 -6 -10 -8 -15 -5 -8 5 -18 -45 -12 -63 2 -7 10 -12 17 -12 8 0 26 -11 41 -25 15 -14 33 -25 39 -25 7 0 12 -6 11 -12 0 -7 2 -14 7 -15 22 -7 64 -33 78 -49 26 -30 39 -92 26 -124 -12 -32 -19 -118 -17 -234 0 -54 -4 -84 -13 -95 -8 -9 -17 -24 -20 -34 -4 -9 -19 -22 -34 -28 -27 -10 -72 -55 -73 -72 0 -4 -18 -26 -40 -48 -49 -49 -55 -94 -15 -109 16 -6 25 -17 25 -31 0 -12 20 -42 45 -68 57 -59 100 -112 107 -132 8 -21 123 -99 146 -99 46 0 69 -47 35 -73 -30 -24 -29 -30 12 -70 19 -19 35 -40 35 -47 0 -7 19 -34 43 -61 47 -53 116 -142 133 -171 26 -45 14 -42 280 -64 47 -4 74 -2 78 5 5 7 57 11 145 11 121 0 140 2 154 18 10 10 17 21 17 25 0 3 19 27 43 52 23 26 42 57 42 69 0 12 6 21 15 21 8 0 24 8 35 17 11 9 30 25 42 35 12 10 24 32 27 48 6 26 3 32 -21 42 -16 7 -40 24 -55 38 -15 14 -30 25 -34 25 -4 0 -22 11 -40 25 -18 14 -37 25 -42 25 -5 0 -14 11 -20 25 -10 22 -8 28 25 60 33 32 35 37 24 59 -14 25 -7 101 10 112 15 9 10 157 -6 178 -22 29 -20 159 3 199 9 18 19 34 22 37 3 3 11 17 17 33 7 15 17 27 23 27 6 0 10 8 8 18 -1 9 6 29 15 43 14 21 16 39 12 90 -7 71 -10 79 -31 79 -8 0 -12 4 -9 10 3 5 -7 15 -22 21 -15 7 -42 18 -60 25 -17 8 -42 14 -56 14 -13 0 -30 6 -36 14 -6 8 -25 19 -41 26 -17 7 -30 19 -30 26 0 8 -6 14 -13 14 -7 0 -20 9 -30 20 -9 11 -23 20 -29 20 -7 0 -19 9 -25 20 -7 10 -28 22 -47 25 -19 4 -37 13 -40 21 -3 8 -15 14 -26 14 -12 0 -46 16 -76 36 -29 19 -58 33 -63 29 -6 -3 -24 1 -41 10 -17 9 -35 13 -40 10 -5 -3 -35 7 -67 22 -57 27 -63 29 -83 32 -5 0 -10 8 -10 16 0 8 -5 15 -12 15 -6 0 -19 7 -28 15 -30 27 -72 27 -114 1z',
    },
    {
        id: 'cristobal_rojas',
        name: 'Charallave',
        // Municipio Cristóbal Rojas - Path real
        path: 'M3103 3981 c-34 -6 -83 -61 -83 -92 0 -11 -13 -22 -33 -30 -24 -8 -33 -18 -33 -33 1 -12 -5 -21 -14 -21 -20 0 -40 -43 -40 -87 0 -21 -4 -38 -10 -38 -5 0 -10 -6 -10 -13 0 -16 -68 -93 -96 -110 -12 -7 -39 -22 -60 -34 -22 -12 -47 -31 -56 -42 -10 -12 -24 -21 -32 -21 -8 0 -16 -3 -18 -7 -4 -10 -91 -53 -107 -53 -6 0 -11 -4 -11 -10 0 -5 -12 -10 -26 -10 -17 0 -28 -6 -31 -20 -4 -14 -17 -21 -52 -26 -25 -3 -63 -18 -85 -32 -21 -15 -48 -31 -60 -37 -11 -6 -27 -23 -34 -38 -7 -15 -18 -27 -23 -27 -5 0 -9 -8 -7 -17 2 -11 -3 -18 -12 -18 -8 0 -23 -7 -33 -17 -11 -9 -43 -33 -72 -55 -64 -45 -69 -69 -22 -103 17 -13 42 -33 54 -44 12 -12 28 -21 35 -22 7 0 21 -2 30 -2 9 -1 18 -9 20 -18 3 -17 89 -54 126 -54 12 0 22 -5 22 -11 0 -8 4 -8 13 0 8 6 18 7 23 2 5 -5 43 -18 84 -31 41 -12 80 -23 87 -25 6 -2 13 -14 15 -25 2 -13 19 -32 39 -44 20 -11 57 -35 81 -53 25 -17 58 -35 74 -39 30 -7 77 -31 129 -65 17 -10 38 -22 48 -25 9 -4 17 -11 17 -16 0 -6 7 -5 15 2 13 11 18 10 29 -5 13 -18 14 -18 26 5 7 12 15 49 17 82 3 32 10 61 15 64 5 3 4 12 -3 20 -13 16 -4 104 12 104 5 0 9 7 9 15 0 8 3 15 8 15 4 0 8 10 8 23 4 48 34 230 39 236 4 3 81 7 172 9 134 3 168 0 182 -11 18 -17 35 -20 105 -18 38 1 47 4 42 16 -4 12 5 15 54 14 33 -1 65 3 72 9 7 5 27 13 45 16 17 4 34 14 37 22 3 8 19 14 41 14 60 0 124 20 130 41 4 11 16 19 29 19 36 0 76 23 76 44 0 11 3 16 7 13 3 -4 16 -1 27 6 12 8 36 17 54 20 19 4 47 16 62 27 l29 21 -1 110 c-1 60 -5 112 -10 115 -4 3 -8 13 -8 23 0 10 -19 34 -42 54 -24 19 -60 51 -80 71 -22 22 -38 31 -41 23 -3 -9 -8 -8 -22 3 -9 8 -32 13 -49 12 -21 -2 -27 0 -18 6 18 12 -32 49 -77 58 -17 4 -31 10 -31 15 0 5 -9 9 -19 9 -11 0 -23 5 -26 10 -4 6 -10 8 -15 5 -15 -9 -90 19 -95 36 -7 20 -54 69 -82 85 -13 8 -25 9 -37 2 -10 -6 -100 -12 -200 -14 -161 -3 -225 2 -276 18 -8 3 -29 3 -47 -1z',
    },
    {
        id: 'simon_bolivar',
        name: 'San Francisco de Yare',
        // Municipio Simón Bolívar - Path real
        path: 'M4290 3290 c0 -5 -6 -10 -14 -10 -8 0 -23 -13 -33 -30 -9 -16 -22 -31 -28 -31 -61 -7 -78 -11 -107 -29 -18 -11 -48 -27 -67 -36 -18 -8 -35 -23 -38 -34 -5 -19 -41 -30 -104 -30 -21 0 -41 -4 -44 -10 -3 -5 -17 -10 -30 -10 -13 0 -51 -14 -84 -32 -52 -28 -61 -36 -61 -60 0 -15 5 -28 10 -28 6 0 10 -5 10 -12 0 -18 61 -77 74 -72 7 3 21 -4 31 -16 10 -11 23 -20 29 -20 6 0 16 -10 21 -22 6 -14 24 -26 48 -31 20 -5 37 -13 37 -18 0 -4 16 -11 35 -15 19 -3 38 -10 41 -15 3 -5 18 -9 34 -9 22 0 28 -4 24 -15 -4 -8 -1 -15 5 -15 6 0 11 -6 11 -14 0 -8 16 -16 43 -20 47 -8 103 -24 131 -38 10 -5 34 -12 54 -14 20 -3 46 -16 62 -32 49 -47 64 -57 133 -92 37 -19 67 -38 67 -42 0 -4 11 -8 25 -8 13 0 28 -7 33 -16 9 -16 43 -38 75 -50 9 -3 17 -10 17 -15 0 -5 11 -9 25 -9 20 0 25 -6 31 -36 8 -45 -5 -235 -17 -250 -5 -7 -11 -34 -12 -60 -2 -39 3 -54 21 -72 22 -22 23 -22 347 -22 193 0 325 4 325 9 0 5 39 12 88 15 112 6 129 9 140 21 16 17 29 69 22 85 -4 8 -14 30 -23 48 -9 19 -17 51 -17 72 0 21 -4 41 -9 44 -5 3 -12 54 -16 113 -6 89 -11 110 -29 127 -11 12 -26 30 -32 41 -6 11 -21 25 -32 31 -17 8 -19 13 -9 23 9 10 8 15 -5 25 -10 7 -18 19 -18 27 -1 14 -72 64 -92 64 -15 0 -37 35 -31 51 3 8 -6 25 -21 39 -14 13 -26 29 -26 35 0 5 -9 10 -19 10 -11 0 -22 4 -26 9 -3 6 -27 13 -53 16 -68 10 -102 26 -102 50 0 12 5 26 12 33 9 9 -3 12 -54 12 -37 0 -70 5 -73 10 -4 6 -86 10 -208 11 -243 2 -257 4 -278 46 -21 42 -68 103 -79 103 -16 0 -80 73 -86 99 -4 14 -13 34 -21 43 -8 9 -12 21 -9 26 3 5 -1 15 -9 22 -8 7 -15 19 -15 26 0 8 -7 14 -15 14 -8 0 -15 -4 -15 -10z',
    },
    {
        id: 'tomas_lander',
        name: 'Ocumare del Tuy',
        // Municipio Tomás Lander - Path real
        path: 'M3166 3034 c-3 -9 -3 -20 0 -26 3 -5 -1 -17 -9 -26 -18 -21 -27 -78 -17 -113 5 -16 3 -37 -5 -55 -36 -79 -44 -103 -50 -151 -4 -28 -7 -77 -7 -110 0 -48 -5 -62 -25 -83 -20 -20 -23 -30 -18 -55 20 -83 2 -205 -29 -205 -7 0 -23 -11 -36 -25 -13 -14 -20 -25 -15 -25 5 0 4 -5 -3 -12 -7 -7 -12 -20 -12 -29 0 -9 -7 -22 -15 -29 -17 -14 -22 -136 -5 -142 11 -4 40 -111 40 -150 0 -16 -8 -33 -20 -41 -16 -11 -21 -29 -25 -97 -4 -62 -10 -86 -22 -96 -15 -10 -15 -14 -3 -29 8 -9 25 -21 38 -26 13 -5 20 -13 17 -19 -3 -5 -1 -10 4 -10 6 0 25 -12 42 -27 16 -16 42 -35 57 -43 44 -24 92 -72 92 -90 0 -23 -26 -44 -45 -36 -10 3 -24 -5 -38 -21 -12 -14 -34 -40 -50 -57 -32 -36 -30 -50 8 -54 15 -2 37 -12 48 -23 11 -10 29 -19 39 -19 10 0 18 -4 18 -9 0 -4 28 -12 63 -15 34 -4 72 -14 83 -21 14 -9 67 -17 140 -21 66 -4 125 -11 132 -17 8 -6 12 -6 12 2 0 11 11 7 60 -21 8 -4 25 -12 36 -17 12 -6 28 -18 35 -28 16 -20 98 -63 121 -63 9 0 21 -7 28 -15 7 -8 18 -15 25 -15 7 0 18 -7 25 -15 7 -8 19 -15 28 -15 23 0 82 -49 82 -67 0 -9 -6 -24 -14 -32 -22 -25 -46 -133 -34 -149 6 -7 13 -10 16 -7 3 3 10 1 16 -4 27 -22 89 -11 79 15 -3 8 4 18 18 24 28 11 62 32 69 41 3 4 19 9 35 13 44 9 97 44 89 57 -4 7 -1 10 7 7 8 -3 25 -1 38 4 17 6 27 5 32 -3 5 -9 11 -10 21 -2 7 6 32 14 55 18 23 3 47 13 53 20 9 11 105 13 521 11 280 -2 509 1 509 6 0 4 8 8 18 8 35 0 45 -17 48 -82 5 -112 -11 -103 186 -106 95 -1 220 -7 278 -12 139 -13 237 -13 242 1 2 7 61 12 162 14 94 2 163 8 169 14 6 6 17 11 24 11 7 0 13 4 13 8 0 15 97 91 121 95 67 11 102 33 125 80 16 30 30 45 53 51 17 5 31 15 31 22 0 26 24 65 37 60 7 -3 13 -2 13 3 0 5 16 16 35 25 19 9 38 23 42 30 4 8 34 19 65 26 32 6 61 17 65 24 8 13 73 34 133 43 19 3 43 12 52 20 9 9 30 16 45 18 15 1 35 5 43 8 8 3 34 8 58 10 52 6 56 9 47 37 -4 14 -11 18 -16 13 -6 -6 -33 12 -70 46 -56 51 -111 80 -151 81 -10 0 -35 9 -58 21 -22 11 -59 23 -82 25 -74 7 -94 14 -123 44 -16 17 -33 30 -38 30 -14 0 -81 61 -92 84 -6 12 -17 21 -25 21 -8 0 -20 9 -25 20 -6 11 -23 26 -38 33 -15 7 -27 18 -27 25 0 31 -119 99 -180 102 -19 1 -48 8 -65 14 -16 7 -59 16 -95 20 -66 9 -135 35 -126 49 2 4 -12 15 -32 24 -86 38 -111 48 -120 48 -5 0 -23 7 -38 15 -16 8 -36 15 -45 15 -9 0 -32 18 -52 40 -19 22 -40 40 -45 40 -5 0 -15 6 -21 13 -18 23 -76 19 -115 -8 -20 -14 -47 -27 -61 -31 -27 -7 -62 -41 -52 -51 7 -7 -38 -33 -58 -33 -7 0 -18 -6 -24 -13 -8 -11 -68 -15 -243 -21 -129 -3 -237 -11 -242 -16 -6 -6 -98 -10 -227 -10 -202 0 -220 2 -253 20 -19 12 -36 26 -39 33 -3 7 -5 65 -6 128 -2 94 1 118 14 129 25 21 17 160 -10 160 -7 0 -18 7 -25 15 -7 9 -21 13 -31 10 -12 -4 -19 -1 -19 7 0 13 -62 53 -84 53 -18 0 -56 29 -56 43 0 7 -6 12 -12 11 -31 -4 -48 2 -48 16 0 8 -6 15 -14 15 -7 0 -19 7 -26 15 -7 8 -21 15 -32 15 -10 0 -30 11 -43 24 -20 20 -85 44 -132 48 -7 1 -13 5 -13 10 0 4 -7 8 -15 8 -14 0 -32 8 -75 31 -8 5 -30 15 -47 23 -18 8 -52 28 -76 45 -24 16 -57 33 -73 37 -16 4 -34 11 -40 16 -7 5 -16 8 -20 7 -16 -2 -54 13 -54 22 0 5 -3 8 -7 7 -15 -3 -43 14 -43 26 0 22 -106 128 -126 126 -34 -4 -44 0 -44 15 0 10 -14 17 -41 21 -27 5 -38 11 -35 20 8 20 -20 26 -165 34 -75 4 -141 10 -147 14 -5 3 -13 -1 -16 -10z',
    },
    {
        id: 'independencia',
        name: 'Santa Teresa del Tuy',
        // Municipio Independencia - Path real
        path: 'M5673 3458 c-16 -3 -129 -12 -253 -19 -224 -13 -264 -14 -350 -11 -73 3 -280 -9 -280 -16 0 -4 -28 -7 -62 -6 -101 1 -301 -15 -311 -25 -5 -5 -15 -7 -22 -5 -15 6 -28 -23 -15 -31 6 -4 7 -17 4 -30 -3 -12 -1 -26 5 -29 6 -4 11 -14 11 -22 0 -20 42 -108 53 -113 17 -6 86 -72 85 -79 -6 -21 25 -52 52 -52 18 0 39 -11 59 -30 l30 -31 233 0 c228 -1 234 -1 273 -25 22 -14 54 -42 70 -64 19 -25 36 -37 46 -34 9 2 21 -2 28 -10 6 -7 31 -19 56 -26 59 -16 72 -32 56 -67 -12 -25 -10 -29 21 -55 18 -15 38 -28 43 -28 21 -1 100 -103 103 -133 2 -20 18 -44 49 -72 l45 -42 -7 -99 c-5 -86 -3 -106 14 -154 12 -30 21 -65 21 -77 0 -12 5 -25 10 -28 6 -3 10 -15 10 -25 0 -29 18 -34 48 -14 15 10 30 17 35 16 4 -1 7 3 7 8 0 6 12 15 26 20 14 6 23 14 19 19 -10 17 29 21 152 13 23 -1 31 -6 27 -16 -7 -19 63 -74 104 -82 18 -4 36 -13 39 -20 3 -8 11 -14 19 -14 8 0 22 -4 32 -9 87 -43 149 -71 159 -71 6 0 13 -6 15 -12 3 -8 19 -12 42 -10 26 3 44 -2 57 -14 11 -10 43 -21 72 -26 112 -16 162 -33 208 -70 25 -21 51 -38 56 -38 14 0 131 -120 146 -150 7 -14 21 -26 32 -29 11 -2 23 -9 28 -17 4 -7 14 -11 23 -7 9 3 21 -3 30 -16 8 -11 21 -21 29 -21 7 0 16 -6 18 -12 4 -10 6 -10 6 0 2 20 40 14 65 -12 13 -12 41 -26 62 -29 20 -4 40 -10 43 -14 8 -10 36 -14 90 -13 34 1 50 7 68 26 13 14 23 31 23 38 0 8 6 25 14 40 16 32 12 142 -7 159 -7 7 -30 21 -51 32 -21 11 -41 25 -43 32 -3 7 -17 13 -32 14 -49 2 37 17 108 19 95 3 93 3 245 -2 77 -3 148 -3 158 0 16 4 18 18 18 164 0 138 -3 164 -20 197 -18 35 -20 62 -21 278 -1 224 -2 239 -22 260 -16 19 -18 25 -7 38 8 10 9 19 2 28 -5 6 -13 35 -17 62 -3 28 -15 60 -25 73 -18 22 -25 23 -185 24 -154 1 -169 3 -186 21 -10 12 -19 24 -19 29 0 4 -20 8 -44 8 -65 0 -96 31 -96 97 0 28 -6 58 -15 69 -8 10 -15 40 -15 66 0 60 -20 68 -85 33 -49 -25 -107 -30 -522 -36 -18 0 -46 9 -62 20 -16 12 -32 21 -36 21 -20 0 -65 44 -65 64 0 25 -31 62 -63 77 -12 5 -29 13 -37 18 -8 6 -28 12 -43 16 -18 4 -36 18 -49 41 -12 21 -26 33 -34 30 -7 -3 -16 -1 -19 4 -4 6 -78 10 -174 10 -92 0 -171 3 -174 6 -9 9 -200 11 -234 2z',
    },
    {
        id: 'paz_castillo',
        name: 'Santa Lucía del Tuy',
        // Municipio Paz Castillo - Path real
        path: 'M5855 5117 c-19 -9 -36 -11 -47 -6 -10 6 -19 5 -23 -1 -3 -5 -44 -10 -89 -10 -60 0 -87 -4 -95 -14 -7 -8 -27 -18 -44 -22 -18 -3 -36 -9 -42 -13 -5 -4 -41 -8 -80 -10 -38 -2 -80 -7 -93 -12 -13 -5 -28 -6 -33 -3 -6 3 -18 -1 -28 -10 -11 -10 -43 -19 -82 -22 -35 -2 -94 -7 -131 -10 -40 -3 -72 -11 -79 -20 -6 -8 -17 -14 -24 -14 -20 0 -45 -35 -45 -63 0 -14 -4 -28 -9 -31 -4 -3 -11 -26 -14 -51 -2 -25 -7 -48 -9 -50 -2 -3 -24 -6 -49 -9 -35 -3 -47 -9 -59 -30 -9 -15 -24 -26 -36 -26 -12 0 -26 -11 -34 -24 -9 -17 -25 -27 -50 -31 -21 -4 -48 -17 -61 -31 -12 -13 -30 -24 -39 -24 -17 0 -61 -44 -58 -58 4 -20 -29 -61 -74 -92 -50 -34 -53 -35 -162 -37 -61 -1 -122 -2 -136 -2 -19 -1 -30 -11 -44 -41 -11 -22 -23 -40 -28 -40 -4 0 -8 -7 -8 -15 0 -13 -21 -15 -130 -15 -80 0 -130 -4 -130 -10 0 -6 -46 -10 -119 -10 l-119 0 -26 -34 c-14 -19 -26 -39 -26 -44 0 -11 -47 -22 -91 -22 -16 0 -37 -4 -47 -9 -9 -5 -25 -12 -36 -15 -11 -4 -16 -12 -12 -21 4 -12 -6 -15 -54 -15 -33 0 -60 -4 -60 -8 0 -4 -14 -13 -32 -20 -31 -11 -31 -12 -13 -32 15 -17 31 -20 96 -20 43 0 81 5 84 10 8 12 133 11 141 -1 7 -13 91 -11 99 1 5 7 11 8 19 1 6 -5 36 -11 66 -13 50 -2 59 -6 95 -45 23 -25 50 -43 62 -43 12 0 34 -10 50 -23 36 -30 156 -88 189 -92 14 -1 30 -7 37 -14 6 -6 17 -11 24 -11 7 0 58 -46 113 -102 l100 -102 0 -63 0 -63 133 6 c72 4 134 11 137 15 4 5 227 10 498 12 362 2 495 6 504 15 16 16 228 16 262 1 19 -9 36 -9 71 1 30 9 49 9 57 2 7 -5 84 -13 171 -17 86 -4 157 -11 157 -16 0 -5 6 -9 13 -9 7 0 19 -6 25 -12 7 -7 18 -13 25 -14 26 -2 57 -17 57 -26 0 -14 53 -68 67 -68 7 0 19 -15 28 -34 10 -21 28 -39 46 -45 16 -5 29 -16 29 -24 0 -39 25 -42 268 -38 235 3 261 7 288 41 9 10 32 15 79 15 64 0 67 -1 95 -35 27 -33 29 -40 27 -118 -2 -80 -1 -85 25 -107 17 -15 35 -21 48 -18 11 3 28 -1 38 -8 30 -24 83 -51 89 -45 4 3 12 1 20 -6 8 -6 60 -13 116 -16 56 -2 107 -8 112 -13 15 -11 85 14 102 37 7 10 11 26 7 36 -4 12 0 18 11 18 9 0 18 12 22 30 3 17 10 33 14 36 5 3 9 23 9 45 0 21 5 39 11 39 5 0 8 4 5 9 -3 5 6 30 19 57 14 27 25 63 25 79 0 21 13 45 45 80 25 28 43 55 41 60 -5 13 29 56 37 48 4 -3 11 7 18 23 6 16 15 37 20 46 5 10 9 32 9 50 0 57 10 78 36 78 13 0 24 5 24 10 0 6 6 10 14 10 7 0 26 16 40 35 15 19 22 35 17 35 -6 0 -11 5 -11 10 0 6 6 10 13 10 8 0 24 12 38 26 23 24 24 33 27 176 2 130 0 153 -14 169 -9 11 -23 19 -29 19 -26 0 -39 30 -28 61 6 16 8 31 3 34 -5 3 -11 23 -15 44 -4 23 -13 42 -23 45 -9 4 -30 14 -46 22 -15 9 -46 19 -68 21 -26 4 -37 10 -34 19 3 7 -9 19 -28 28 -47 22 -68 50 -70 94 -2 42 -34 92 -59 92 -8 0 -26 10 -40 22 -13 11 -37 22 -53 23 -16 1 -64 5 -107 10 -76 8 -78 8 -94 44 -9 20 -29 48 -45 63 -15 15 -28 31 -28 35 0 5 -15 23 -32 40 -33 31 -33 31 -147 32 -113 0 -114 0 -145 31 -18 16 -42 30 -54 30 -33 0 -92 27 -92 42 0 16 -57 68 -74 68 -7 0 -17 5 -22 10 -6 6 -44 14 -86 19 -41 5 -84 14 -96 20 -23 12 -129 4 -138 -10 -8 -14 -501 -11 -527 3 -34 18 -324 22 -362 5z',
    },
]);

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

// Configuración de calibración final (obtenida manualmente)
const municipalityCalibration = {
  "urdaneta": { x: -1370, y: -490, s: 1, r: 0, sx: 1, sy: -1 },
  "cristobal_rojas": { x: -1270, y: -490, s: 1, r: 0, sx: 1, sy: -1 },
  "simon_bolivar": { x: -1320, y: -380, s: 1, r: 0, sx: 1, sy: -1 },
  "tomas_lander": { x: -1270, y: -300, s: 1, r: 0, sx: 1, sy: -1 },
  "independencia": { x: -1220, y: -490, s: 1, r: 0, sx: 1, sy: -1 },
  "paz_castillo": { x: -1160, y: -650, s: 1, r: 0, sx: 1, sy: -1 }
};

// Función para generar el string de transformación
const getMunicipalityTransform = (id) => {
    const c = municipalityCalibration[id];
    if (!c) return '';
    return `translate(${c.x}, ${c.y}) rotate(${c.r}) scale(${c.s * c.sx}, ${c.s * c.sy})`;
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
                            <a href="#" class="hover:scale-105 transition">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" class="h-12">
                            </a>
                            <a href="#" class="hover:scale-105 transition">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" class="h-12">
                            </a>
                        </div>
                    </div>

                    <!-- Right - Quick Search Form -->
                    <div class="rounded-2xl shadow-2xl p-8" style="background-color: #FFFFFF;">
                        <h3 class="text-xl font-bold mb-6" style="color: #001F5B;">Solicita tu viaje ahora</h3>
                        <form @submit.prevent class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2" style="color: #001F5B;">Origen</label>
                                <input 
                                    v-model="origin"
                                    type="text" 
                                    placeholder="Entrar un origen" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #80C5DE;"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2" style="color: #001F5B;">Destino</label>
                                <input 
                                    v-model="destination"
                                    type="text" 
                                    placeholder="Entrar un destino" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:border-transparent"
                                    style="--tw-ring-color: #80C5DE;"
                                >
                            </div>
                            <Link 
                                :href="route('register')"
                                class="block w-full text-center py-3 rounded-lg font-bold transition shadow-lg vecta-btn-primary"
                            >
                                Solicitar VECTA ahora
                            </Link>
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
                                   :transform="getMunicipalityTransform(municipality.id)"
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
                    <a href="#" class="hover:scale-105 transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" class="h-14">
                    </a>
                    <a href="#" class="hover:scale-105 transition">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" class="h-14">
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