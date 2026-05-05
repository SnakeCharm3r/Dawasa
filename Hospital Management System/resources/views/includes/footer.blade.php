@auth
<!-- FOOTER -->
<footer class="footer mt-auto py-3 {{ auth()->check() ? 'content-wrapper' : '' }}">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 text-center">
                <p class="mb-0 text-muted small">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Hospital Management System') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>
@endauth
