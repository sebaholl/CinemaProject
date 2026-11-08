<?php
declare(strict_types=1);

final class Auth
{
    private const IDLE_LIMIT = 1800; // 30 minutes

    public function __construct(private PDO $db) {}

    public function login(string $identifier, string $password): bool
    {
        $identifier = trim($identifier);

        if ($identifier === '' || strlen($identifier) > 254 ||
            $password === '') {
            return false;
        }

        // Customers sign in with their email.
        $stmt = $this->db->prepare(
            'SELECT customer_id, first_name, last_name, password_hash
             FROM Customer
             WHERE email = :identifier
             LIMIT 1'
        );
        $stmt->execute([':identifier' => $identifier]);
        $customer = $stmt->fetch();

        if ($customer && password_verify(
            $password, $customer['password_hash']
        )) {
            $this->startAuthenticatedSession(
                'customer',
                (int) $customer['customer_id'],
                $customer['first_name'] . ' ' . $customer['last_name']
            );
            return true;
        }

        // Admins sign in with AdminUser.login.
        $stmt = $this->db->prepare(
            'SELECT admin_id, name, password_hash
             FROM AdminUser
             WHERE login = :identifier
             LIMIT 1'
        );
        $stmt->execute([':identifier' => $identifier]);
        $admin = $stmt->fetch();

        if ($admin && password_verify(
            $password, $admin['password_hash']
        )) {
            $this->startAuthenticatedSession(
                'admin',
                (int) $admin['admin_id'],
                $admin['name']
            );
            return true;
        }

        return false;
    }

    public function check(): bool
    {
        return $this->currentUser() !== null;
    }

    public function currentUser(): ?array
    {
        $session = $_SESSION['auth'] ?? null;

        if (!is_array($session) ||
            !in_array($session['role'] ?? null, ['customer', 'admin'], true) ||
            !is_int($session['id'] ?? null) ||
            $session['id'] < 1 ||
            !is_string($session['name'] ?? null) ||
            !is_int($session['last_activity'] ?? null)) {
            return null;
        }

        if (time() - $session['last_activity'] > self::IDLE_LIMIT) {
            $this->logout();
            return null;
        }

        $_SESSION['auth']['last_activity'] = time();

        return [
            'role' => $session['role'],
            'id' => $session['id'],
            'name' => $session['name'],
        ];
    }

    public function isAdmin(): bool
{
    $user = $this->currentUser();
    return $user !== null && $user['role'] === 'admin';
}

    public function requireLogin(): void
    {
        if ($this->check()) {
            return;
        }

        $returnTo = $_SERVER['REQUEST_URI'] ?? url('index.php');
        header(
            'Location: ' . url(
                'login.php?return=' . rawurlencode($returnTo)
            ),
            true,
            302
        );
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];

        $cookie = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);

        session_destroy();
    }

    private function startAuthenticatedSession(
        string $role,
        int $id,
        string $name
    ): void {
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Could not start login session.');
        }

        // Clear anonymous checkout data and rotate the CSRF token.
        $_SESSION = [
            'auth' => [
                'role' => $role,
                'id' => $id,
                'name' => $name,
                'last_activity' => time(),
            ],
            'csrf_token' => bin2hex(random_bytes(32)),
        ];
    }
}