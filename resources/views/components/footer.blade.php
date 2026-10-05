<footer class="academy-footer">
    <div class="academy-footer-inner">
        <div class="academy-footer-grid">
            <div class="academy-footer-intro">
                <a class="academy-footer-brand" href="{{ route('landing') }}" aria-label="Orange Dance Academy - Beranda">
                    <img src="{{ asset('images/logoorange.png') }}" alt="" width="38" height="44">
                    <span>ORANGE DANCE ACADEMY</span>
                </a>
                <p>Elevating the art of motion through premium spaces and elite instruction. Experience dance like never before.</p>
                <div class="academy-footer-social" aria-label="Media sosial">
                    <button type="button" disabled aria-label="Facebook" title="Facebook"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 21v-8h3l.5-4H14V7c0-1 .3-2 2-2h2V1.5c-.5-.1-1.8-.2-3-.2-3 0-5 1.8-5 5V9H7v4h3v8z"/></svg></button>
                    <button type="button" disabled aria-label="Instagram" title="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none"/></svg></button>
                    <button type="button" data-share-academy aria-label="Bagikan website" title="Bagikan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4m-6.8 7 6.8 4"/></svg></button>
                </div>
                <span class="academy-footer-share-status" role="status"></span>
            </div>
            <nav class="academy-footer-column" aria-label="Quick Links">
                <h2>Quick Links</h2>
                <a href="{{ route('user.about') }}">About Us</a>
                <a href="{{ route('landing') }}#studio">Find a Studio</a>
                <a href="{{ route('landing') }}#class">Class Schedule</a>
                <a href="{{ route('landing') }}#about">Instructors</a>
                <a href="{{ auth()->check() && auth()->user()->can('access-admin') ? route('admin.memberships.index') : route('memberships.index') }}">Pricing Plans</a>
            </nav>
            <div class="academy-footer-column">
                <h2>Support</h2>
                <a href="tel:+6281234567891">Help Center</a>
                <span>Terms of Service</span>
                <span>Privacy Policy</span>
                <a href="tel:+6281234567891">Contact Us</a>
            </div>
            <div class="academy-footer-column">
                <h2>Our Location</h2>
                <address>
                    <span class="academy-footer-contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><span>Jl. Gurun Agung</span></span>
                    <a class="academy-footer-contact" href="tel:+6281234567891"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m7 3 3 5-3 3c2 3 3 4 6 6l3-3 5 3c0 3-2 5-5 4C9 19 5 15 3 8 2 5 4 3 7 3Z"/></svg><span>081234567891</span></a>
                </address>
            </div>
        </div>
    </div>
    <div class="academy-footer-bottom">&copy; {{ now()->year }} Orange Dance Academy. All rights reserved.</div>
</footer>
<script>
    document.querySelector('[data-share-academy]').addEventListener('click', async () => {
        const url = @json(route('landing'));
        const status = document.querySelector('.academy-footer-share-status');
        try {
            if (navigator.share) {
                await navigator.share({ title: 'Orange Dance Academy', url });
            } else if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
                status.textContent = 'Link berhasil disalin.';
            } else {
                window.prompt('Salin link website:', url);
            }
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = 'Link belum bisa dibagikan. Silakan coba lagi.';
        }
    });
</script>
