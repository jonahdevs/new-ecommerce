/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
import richEditor from './rich-editor';

/**
 * Mary UI Choices components use Alpine x-anchor (Floating UI autoUpdate) to position
 * their dropdown panels. autoUpdate runs continuously via DOM mutation observers —
 * even when the dropdown is visually closed. On every Livewire DOM morph (save, update,
 * wire:navigate, etc.) the mutation observer fires, autoUpdate tries to reposition the
 * panel using $refs.container, but that element has been replaced by the morph and is
 * now detached. This throws "Alpine: no element provided to x-anchor", which corrupts
 * Alpine's execution queue and makes subsequent wire:navigate calls fail.
 *
 * Fix 1 (primary) — Livewire.interceptRequest onSend fires before every HTTP request,
 *   before any DOM morph. We dispatch Escape + body click here to trigger Alpine's
 *   @keydown.escape.window and @click.outside handlers on all open Choices dropdowns,
 *   which sets focused=false and stops autoUpdate before the morph happens.
 *
 * Fix 2 — livewire:navigating fires just before Livewire swaps in new page HTML.
 *   A second pass for the navigation case specifically.
 *
 * Fix 3 (safety net) — suppress any residual x-anchor rejections that slip through.
 */
document.addEventListener('livewire:init', () => {
    Livewire.interceptRequest(({ onSend }) => {
        onSend(() => {
            window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
            document.body.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        });
    });
});

document.addEventListener('livewire:navigating', () => {
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    document.body.dispatchEvent(new MouseEvent('click', { bubbles: true }));
});

window.addEventListener('unhandledrejection', (event) => {
    const msg = event.reason?.message ?? String(event.reason ?? '');
    if (msg.includes('x-anchor')) {
        event.preventDefault();
    }
});

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


