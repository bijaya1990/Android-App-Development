<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config.php';

Auth::requireRole(ROLE_SUPER_ADMIN);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $customUsername = trim((string) ($_POST['username'] ?? ''));

        if ($fullName === '' || mb_strlen($fullName) > 100) {
            $errors[] = 'Full name is required (max 100 characters).';
        } elseif ($customUsername !== '' && !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $customUsername)) {
            $errors[] = 'Custom username must be 3-50 characters: letters, numbers, underscore only.';
        } elseif ($customUsername !== '' && UserRepository::usernameExists($customUsername)) {
            $errors[] = 'That username is already taken.';
        } else {
            $username = $customUsername !== '' ? $customUsername : Credentials::generateUsername($fullName);
            $password = Credentials::generatePassword();
            $id = UserRepository::createWriter($username, $fullName, password_hash($password, PASSWORD_DEFAULT));

            flash_set('new_writer', ['id' => $id, 'username' => $username, 'password' => $password]);
            header('Location: /admin/writers.php');
            exit;
        }
    } elseif ($action === 'disable' || $action === 'enable') {
        UserRepository::setStatus((int) $_POST['user_id'], $action === 'disable' ? 'disabled' : 'active');
        header('Location: /admin/writers.php');
        exit;
    } elseif ($action === 'reset_password') {
        $userId = (int) $_POST['user_id'];
        $user = UserRepository::find($userId);
        if ($user && $user['role'] === ROLE_CONTENT_WRITER) {
            $password = Credentials::generatePassword();
            UserRepository::resetPassword($userId, password_hash($password, PASSWORD_DEFAULT));
            flash_set('reset_password', ['username' => $user['username'], 'password' => $password]);
        }
        header('Location: /admin/writers.php');
        exit;
    } elseif ($action === 'delete') {
        try {
            UserRepository::deleteWriter((int) $_POST['user_id']);
        } catch (PDOException) {
            flash_set('delete_error', 'Cannot delete a writer who already has articles. Disable the account instead.');
        }
        header('Location: /admin/writers.php');
        exit;
    }
}

$writers = UserRepository::listWriters();
$newWriter = flash_take('new_writer');
$resetPassword = flash_take('reset_password');
$deleteError = flash_take('delete_error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Content Writers - Talpadar TV News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php $active = 'writers'; require __DIR__ . '/_nav.php'; ?>
<main>
    <?php if ($newWriter): ?>
        <div class="card">
            <p><strong>Writer created.</strong> Share these credentials now — the password will not be shown again.</p>
            <p>Username: <strong><?= e($newWriter['username']) ?></strong><br>Password: <strong><?= e($newWriter['password']) ?></strong></p>
        </div>
    <?php endif; ?>

    <?php if ($resetPassword): ?>
        <div class="card">
            <p><strong>Password reset.</strong> Share this with <?= e($resetPassword['username']) ?> now — it will not be shown again.</p>
            <p>New password: <strong><?= e($resetPassword['password']) ?></strong></p>
        </div>
    <?php endif; ?>

    <?php if ($deleteError): ?>
        <div class="card"><p class="error"><?= e($deleteError) ?></p></div>
    <?php endif; ?>

    <div class="card">
        <h1>Create Writer</h1>
        <?php foreach ($errors as $error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endforeach; ?>
        <form method="post" action="/admin/writers.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" maxlength="100" required>
            <label for="username">Custom Username (optional — auto-generated if left blank)</label>
            <input type="text" id="username" name="username" maxlength="50">
            <div class="actions">
                <button type="submit">Create Writer (auto-generates password)</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h1>Content Writers</h1>
        <?php if (empty($writers)): ?>
            <p>No writers yet.</p>
        <?php else: ?>
            <table>
                <thead>
                <tr><th>Username</th><th>Full Name</th><th>Status</th><th>Created</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($writers as $writer): ?>
                    <tr>
                        <td><?= e($writer['username']) ?></td>
                        <td><?= e($writer['full_name']) ?></td>
                        <td><?= e($writer['status']) ?></td>
                        <td><?= e($writer['created_at']) ?></td>
                        <td>
                            <form method="post" action="/admin/writers.php" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $writer['id'] ?>">
                                <?php if ($writer['status'] === 'active'): ?>
                                    <button type="submit" name="action" value="disable">Disable</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="enable">Enable</button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="reset_password" onclick="return confirm('Reset this writer\'s password?');">Reset Password</button>
                                <button type="submit" name="action" value="delete" onclick="return confirm('Delete this writer permanently?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
