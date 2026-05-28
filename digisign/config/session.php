<?php
ini_set('session.gc_maxlifetime', 900);
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
session_cache_limiter('nocache');

function checkSessionTimeout(): void
{
    $timeout = 900;

    if (isset($_SESSION['last_activity'])) {
        $idle = time() - (int)$_SESSION['last_activity'];
        if ($idle > $timeout) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']);
            }
            session_destroy();
            header('Location: index.php?page=login&timeout=1');
            exit;
        }
    }

    $_SESSION['last_activity'] = time();
}
