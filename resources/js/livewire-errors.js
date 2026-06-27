/**
 * Replace Livewire's default "dump the server error into a modal/iframe"
 * behavior with a full-page render of the error response.
 *
 * When a 500/503 is thrown inside a Livewire action (e.g. a wire:click),
 * Livewire intercepts the HTML error response and shows it in an overlay
 * modal. Instead we take over the document so the user sees the proper
 * error page — with the same storefront/admin chrome they were already in,
 * which the server-rendered error view resolves from the request referer.
 *
 * Validation (422) and "page expired" (419) keep Livewire's default handling.
 */
document.addEventListener('livewire:init', () => {
    Livewire.interceptRequest(({ onError }) => {
        onError(({ response, responseBody, preventDefault }) => {
            if (![500, 503].includes(response.status)) {
                return;
            }

            // Suppress Livewire's built-in error modal...
            preventDefault();

            // ...and render the server's error page full-screen.
            document.open();
            document.write(responseBody);
            document.close();
        });
    });
});
