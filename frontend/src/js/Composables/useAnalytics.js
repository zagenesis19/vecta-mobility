import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

// Singleton state to persist across components
const eventQueue = ref([]);
const isSending = ref(false);
const BATCH_SIZE = 5;
const FLUSH_INTERVAL = 5000; // 5 seconds

let timer = null;

export function useAnalytics() {

    // Core function to track any event
    const trackEvent = (type, target = null, meta = {}) => {
        eventQueue.value.push({
            type,
            target,
            meta: {
                ...meta,
                timestamp: new Date().toISOString(),
                url: window.location.pathname
            }
        });

        if (eventQueue.value.length >= BATCH_SIZE) {
            flushEvents();
        }
    };

    // Send data to backend
    const flushEvents = async () => {
        if (eventQueue.value.length === 0 || isSending.value) return;

        isSending.value = true;
        const batch = [...eventQueue.value];
        eventQueue.value = []; // Clear queue immediately

        try {
            await axios.post(route('analytics.store'), { events: batch });
        } catch (error) {
            console.error("Analytics Error:", error);
            // Optional: Re-queue failed events if critical
        } finally {
            isSending.value = false;
        }
    };

    // Auto-setup: Start timer and hook into Router
    const initAutoTracking = () => {
        // Interval flush
        if (!timer) {
            timer = setInterval(flushEvents, FLUSH_INTERVAL);
        }

        // Page View Tracking
        router.on('navigate', (event) => {
            trackEvent('page_view', event.detail.page.url);
        });

        // Initial Page Load
        trackEvent('session_start', document.referrer || 'direct');
    };

    return {
        trackEvent,
        initAutoTracking
    };
}
