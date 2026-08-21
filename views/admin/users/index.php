<div class="stack">
    <div>
        <h1>Ruoli e utenti</h1>
        <p class="muted">Ruoli supportati: SUPER_ADMIN, ADMIN, EDITOR, ANALYTICS.</p>
    </div>

    <div class="panel">
        <h2 style="margin-top:0;">Aggiungi nuovo utente</h2>
        <form method="post" action="<?php echo e(url('/admin/users/create')); ?>" class="form-grid">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="display_name">Nome</label>
                <input class="form-control" id="display_name" name="display_name" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Ruolo</label>
                <select class="form-control" id="role" name="role">
                    <option value="SUPER_ADMIN">SUPER_ADMIN</option>
                    <option value="ADMIN">ADMIN</option>
                    <option value="EDITOR" selected>EDITOR</option>
                    <option value="ANALYTICS">ANALYTICS</option>
                </select>
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;">
                <label class="checkbox" for="is_active">
                    <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                    Attivo
                </label>
            </div>
            <div class="form-group form-group-full">
                <button class="btn" type="submit">Crea utente</button>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Ruolo</th>
                    <th>Attivo</th>
                    <th>Ultimo accesso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo e($user['display_name'] ?? '-'); ?></td>
                        <td><?php echo e($user['username'] ?? '-'); ?></td>
                        <td><?php echo e($user['email'] ?? '-'); ?></td>
                        <td><span class="pill"><?php echo e($user['role'] ?? '-'); ?></span></td>
                        <td><?php echo ! empty($user['is_active']) ? 'Sì' : 'No'; ?></td>
                        <td><?php echo e($user['last_login_at'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
