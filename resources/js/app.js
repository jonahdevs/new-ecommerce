/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
import Swiper from 'swiper';
import { Navigation, Pagination, Scrollbar, Grid, Thumbs, FreeMode, Autoplay } from 'swiper/modules';
import 'swiper/css/bundle';

window.Swiper = Swiper
// Register modules globally so they're available in Alpine components
Swiper.use([Navigation, Pagination, Scrollbar, Grid, Thumbs, FreeMode, Autoplay]);