<x-layouts::guest title="Privacy Policy">
    {{-- Breadcrumb --}}
    <div class="bg-white border-b border-zinc-200 py-3">
        <flux:breadcrumbs class="container mx-auto px-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>
                Home
            </flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Privacy Policy</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <section class="container mx-auto px-4 py-8 min-h-[80svh]">
        <div class="">
            <flux:heading level="1"
                class="text-2xl! sm:text-3xl! lg:text-4xl! font-bold! text-zinc-900 dark:text-white mb-6">
                Privacy Policy
            </flux:heading>

            <div class="prose prose-zinc dark:prose-invert max-w-none">
                <p class="text-xs! sm:text-sm! text-zinc-500 dark:text-zinc-400 mb-6">
                    Last updated: {{ now()->format('F d, Y') }}
                </p>

                <h2>1. Information We Collect</h2>
                <p>
                    We collect information that you provide directly to us, including:
                </p>
                <ul>
                    <li>Name and contact information (email address, phone number, shipping address)</li>
                    <li>Account credentials (username and password)</li>
                    <li>Payment information (processed securely through our payment providers)</li>
                    <li>Order history and preferences</li>
                    <li>Communications with us</li>
                </ul>

                <h2>2. How We Use Your Information</h2>
                <p>
                    We use the information we collect to:
                </p>
                <ul>
                    <li>Process and fulfill your orders</li>
                    <li>Communicate with you about your orders and account</li>
                    <li>Provide customer support</li>
                    <li>Send you marketing communications (with your consent)</li>
                    <li>Improve our website and services</li>
                    <li>Prevent fraud and enhance security</li>
                </ul>

                <h2>3. Information Sharing</h2>
                <p>
                    We do not sell your personal information. We may share your information with:
                </p>
                <ul>
                    <li>Service providers who assist in our operations (payment processors, shipping companies)</li>
                    <li>Law enforcement when required by law</li>
                    <li>Business partners with your consent</li>
                </ul>

                <h2>4. Cookies and Tracking</h2>
                <p>
                    We use cookies and similar tracking technologies to enhance your experience, analyze site usage, and
                    assist in our marketing efforts. You can control cookies through your browser settings.
                </p>

                <h2>5. Data Security</h2>
                <p>
                    We implement appropriate technical and organizational measures to protect your personal information.
                    However, no method of transmission over the Internet is 100% secure.
                </p>

                <h2>6. Your Rights</h2>
                <p>
                    You have the right to:
                </p>
                <ul>
                    <li>Access your personal information</li>
                    <li>Correct inaccurate information</li>
                    <li>Request deletion of your information</li>
                    <li>Opt-out of marketing communications</li>
                    <li>Export your data</li>
                </ul>

                <h2>7. Data Retention</h2>
                <p>
                    We retain your personal information for as long as necessary to fulfill the purposes outlined in
                    this policy, unless a longer retention period is required by law.
                </p>

                <h2>8. Children's Privacy</h2>
                <p>
                    Our services are not directed to children under 13. We do not knowingly collect personal information
                    from children under 13.
                </p>

                <h2>9. International Data Transfers</h2>
                <p>
                    Your information may be transferred to and processed in countries other than your country of
                    residence. We ensure appropriate safeguards are in place.
                </p>

                <h2>10. Changes to This Policy</h2>
                <p>
                    We may update this Privacy Policy from time to time. We will notify you of any changes by posting
                    the new policy on this page.
                </p>

                <h2>11. Contact Us</h2>
                <p>
                    If you have any questions about this Privacy Policy, please contact us at:
                    <br>
                    Email: {{ config('mail.from.address') }}
                </p>
            </div>
        </div>
    </section>
</x-layouts::guest>
