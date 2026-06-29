<?php
/**
 * Helper Keamanan: CSRF Protection & Input Sanitization
 */

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = sanitize($value);
            }
        } else {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }
}

if (!function_exists('set_flash')) {
    function set_flash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type, // 'success', 'danger', 'warning', 'info'
            'message' => $message
        ];
    }
}

if (!function_exists('display_flash')) {
    function display_flash() {
        if (isset($_SESSION['flash'])) {
            $type = $_SESSION['flash']['type'];
            $msg = $_SESSION['flash']['message'];
            unset($_SESSION['flash']);
            
            // Map types to Bootstrap Icons
            $icon = 'bi-info-circle-fill';
            if ($type === 'success') {
                $icon = 'bi-check-circle-fill';
            } elseif ($type === 'danger') {
                $icon = 'bi-exclamation-triangle-fill';
            } elseif ($type === 'warning') {
                $icon = 'bi-exclamation-circle-fill';
            }
            
            echo "
            <div class='alert alert-{$type} d-flex align-items-center shadow border-0 py-3 px-3' role='alert' style='position: fixed; top: 24px; right: 24px; z-index: 1080; max-width: 380px; width: calc(100% - 48px); border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important; transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);' id='flash-alert'>
                <i class='bi {$icon} me-3' style='font-size: 1.25rem; flex-shrink: 0;'></i>
                <div style='font-size: 0.88rem; font-weight: 500; padding-right: 20px; line-height: 1.4; color: inherit; word-break: break-word;'>{$msg}</div>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close' style='position: absolute; top: 50%; transform: translateY(-50%); right: 12px; font-size: 0.75rem; padding: 0.75rem;'></button>
            </div>
            <script>
                setTimeout(function() {
                    var el = document.getElementById('flash-alert');
                    if (el) {
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-20px)';
                        setTimeout(function() {
                            el.remove();
                        }, 500);
                    }
                }, 4000);
            </script>
            ";
        }
    }
}
