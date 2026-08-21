<div class="stack">
    <div>
        <h1>Ruoli e utenti</h1>
        <p class="muted">Ruoli supportati: SUPER_ADMIN, ADMIN, EDITOR, ANALYTICS.</p>
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
