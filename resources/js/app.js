/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
import richEditor from './rich-editor';

document.addEventListener('alpine:init', () => {
    Alpine.data('richEditor', richEditor);

    /**
     * countUp — animates a number from 0 to `to` using ease-out-quad.
     *
     * Options:
     *   to       — target number (raw, unformatted)
     *   decimals — decimal places to display (0 for integers, 2 for currency)
     *   prefix   — string prepended to the number, e.g. 'KES '
     *   suffix   — string appended to the number, e.g. '%'
     *   duration — animation duration in ms (default 900)
     *
     * Usage:
     *   x-data="countUp({ to: 12450.50, decimals: 2, prefix: 'KES ' })"
     *   x-text="display"
     */
    Alpine.data('countUp', ({ to = 0, decimals = 0, prefix = '', suffix = '', duration = 900 } = {}) => ({
        display: prefix + '0' + (decimals > 0 ? '.' + '0'.repeat(decimals) : '') + suffix,

        init() {
            // If value is 0 or unavailable, just show the final value immediately
            if (!to) {
                this.display = this.format(0);
                return;
            }
            this.animate(to);
        },

        format(value) {
            const fixed = Math.abs(value).toFixed(decimals);
            const parts = fixed.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return prefix + parts.join('.') + suffix;
        },

        animate(target) {
            const startTime = performance.now();

            const step = (now) => {
                const elapsed = now - startTime;
                const t = Math.min(elapsed / duration, 1);
                // Ease-out-quad: starts fast, decelerates at the end
                const eased = 1 - (1 - t) * (1 - t);

                this.display = this.format(eased * target);

                if (t < 1) {
                    requestAnimationFrame(step);
                } else {
                    this.display = this.format(target);
                }
            };

            requestAnimationFrame(step);
        },
    }));
});


