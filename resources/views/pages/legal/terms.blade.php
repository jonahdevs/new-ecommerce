<x-layouts::guest title="Terms of Service">
    {{-- Breadcrumb --}}
    <div class="bg-white border-b border-zinc-200 py-3">
        <flux:breadcrumbs class="container mx-auto px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Terms of Service</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <section class="container mx-auto px-4 py-8 min-h-[80svh]">
        <div class="">
            <flux:heading level="1"
                class="text-2xl! sm:text-3xl! lg:text-4xl! font-bold! text-zinc-900 dark:text-white mb-6">
                Terms of Service
            </flux:heading>

            <div class="prose prose-zinc dark:prose-invert max-w-none">
                <p class="text-xs! sm:text-sm! text-zinc-500 dark:text-zinc-400 mb-6">
                    Last updated: {{ now()->format('F d, Y') }}
                </p>

                <h2>1. Acceptance of Terms</h2>
                <p>
                    By accessing and using this website, you accept and agree to be bound by the terms and provision of
                    this agreement.
                </p>

                <h2>2. Use License</h2>
                <p>
                    Permission is granted to temporarily download one copy of the materials (information or software) on
                    our website for personal, non-commercial transitory viewing only.
                </p>

                <h2>3. Account Registration</h2>
                <p>
                    When you create an account with us, you must provide accurate, complete, and current information.
                    Failure to do so constitutes a breach of the Terms, which may result in immediate termination of
                    your account.
                </p>

                <h2>4. Orders and Payments</h2>
                <p>
                    All orders are subject to acceptance and availability. We reserve the right to refuse any order.
                    Prices are subject to change without notice.
                </p>

                <h2>5. Product Information</h2>
                <p>
                    We strive to provide accurate product descriptions and pricing. However, we do not warrant that
                    product descriptions or other content is accurate, complete, reliable, current, or error-free.
                </p>

                <h2>6. Shipping and Delivery</h2>
                <p>
                    Delivery times are estimates and not guaranteed. We are not responsible for delays caused by
                    shipping carriers or circumstances beyond our control.
                </p>

                <h2>7. Returns and Refunds</h2>
                <p>
                    Please review our Return Policy for detailed information about returns and refunds.
                </p>

                <h2>8. Limitation of Liability</h2>
                <p>
                    In no event shall we be liable for any damages (including, without limitation, damages for loss of
                    data or profit, or due to business interruption) arising out of the use or inability to use our
                    website.
                </p>

                <h2>9. Privacy</h2>
                <p>
                    Your use of our website is also governed by our Privacy Policy. Please review our
                    <a href="{{ route('privacy') }}"
                        class="text-blue-600 hover:text-blue-700 dark:text-blue-400">Privacy Policy</a>.
                </p>

                <h2>10. Changes to Terms</h2>
                <p>
                    We reserve the right to modify these terms at any time. Changes will be effective immediately upon
                    posting to the website.
                </p>

                <h2>11. Contact Information</h2>
                <p>
                    If you have any questions about these Terms, please contact us at:
                    <br>
                    Email: {{ config('mail.from.address') }}
                </p>
            </div>
        </div>
    </section>
</x-layouts::guest>
