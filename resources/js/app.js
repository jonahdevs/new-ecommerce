/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
import './livewire-errors';
import L from 'leaflet';
window.L = L;

import Swiper from 'swiper/bundle';
window.Swiper = Swiper;

document.dispatchEvent(new CustomEvent('app:ready'));
