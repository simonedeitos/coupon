<section class="page-intro">
    <div class="container" style="max-width:480px">
        <div class="panel">
            <div class="pill">Admin Login</div>
            <h1>Accedi al backend Couponami</h1>
            <p class="muted">Protezione CSRF, sessioni server-side e rate limiting a 5 tentativi.</p>
            <form class="stack" action="<?php echo e(url('/admin/login')); ?>" method="post">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input class="form-control" id="username" name="username" required value="<?php echo e((string) old('username')); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input class="form-control" id="password" name="password" type="password" required>
                        <button class="btn btn-secondary" id="toggle-password" type="button" aria-label="Mostra password" aria-pressed="false">👁️</button>
                    </div>
                </div>
                <button class="btn" type="submit">Accedi</button>
            </form>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var password = document.getElementById('password');
    var toggle = document.getElementById('toggle-password');
    if (!password || !toggle) {
        return;
    }
    toggle.addEventListener('click', function () {
        var isHidden = password.type === 'password';
        password.type = isHidden ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        toggle.setAttribute('aria-label', isHidden ? 'Nascondi password' : 'Mostra password');
    });
});
</script>
