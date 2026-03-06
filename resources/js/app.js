import './echo';
import Swiper from 'swiper';
import { Navigation, Pagination, Scrollbar, Grid, Thumbs, FreeMode, Autoplay } from 'swiper/modules';
import 'swiper/css/bundle';

Swiper.use([Navigation, Pagination, Scrollbar, Grid, Thumbs, FreeMode, Autoplay]);
window.Swiper = Swiper;

// Re-expose Swiper after every Livewire DOM morph
document.addEventListener('livewire:morph', () => {
    window.Swiper = Swiper;
});

document.addEventListener('livewire:navigated', () => {
    window.Swiper = Swiper;
});