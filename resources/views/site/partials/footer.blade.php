<footer class="site-footer">
    <div class="site-container">
        <div class="site-footer-grid">
            <div>
                <div class="site-footer-brand">GreenPOS</div>
                <p>La plateforme SaaS pour piloter votre commerce, votre stock et votre croissance.</p>
                <div class="site-footer-social" aria-label="Réseaux sociaux">
                    <a href="https://facebook.com" target="_blank" rel="noopener">Facebook</a>
                    <a href="https://linkedin.com" target="_blank" rel="noopener">LinkedIn</a>
                    <a href="https://instagram.com" target="_blank" rel="noopener">Instagram</a>
                </div>
            </div>
            <div>
                <strong>Produits</strong>
                <a href="{{ route('site.features') }}">Fonctionnalités</a>
                <a href="{{ route('site.pricing') }}">Tarifs</a>
                <a href="{{ route('site.sectors') }}">Secteurs</a>
                <a href="{{ route('register-company') }}">Créer mon entreprise</a>
            </div>
            <div>
                <strong>Documentation</strong>
                <a href="{{ route('site.about') }}">À propos</a>
                <a href="{{ route('site.contact') }}">Support</a>
                <a href="{{ route('login') }}">Connexion</a>
                <a href="{{ route('site.contact', ['demo' => 1]) }}">Demander une démo</a>
            </div>
            <div>
                <strong>Support</strong>
                <a href="{{ route('site.contact') }}">Contact</a>
                <a href="{{ route('register-company.track') }}">Suivre ma demande</a>
                <a href="{{ route('site.contact') }}">Confidentialité</a>
                <a href="{{ route('site.contact') }}">Conditions générales</a>
            </div>
        </div>
        <div class="site-footer-copy">
            © {{ date('Y') }} GreenPOS. Tous droits réservés.
        </div>
    </div>
</footer>
