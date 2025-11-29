<?php

class SecureSessionHandler extends SessionHandler {

    protected $key, $name, $cookie;
    private $cipher = 'aes-256-cbc';

    public function __construct($key, $name = 'FVAULT', $cookie = [])
    {
        $this->key = $key;
        $this->name = $name;
        $this->cookie = $cookie;

        $this->cookie += [
            'lifetime' => 30,
            'path'     => ini_get('session.cookie_path'),
            'domain'   => ini_get('session.cookie_domain'),
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true
        ];

        $this->setup();
    }



    private function setup()
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);

        session_name($this->name);

        session_set_cookie_params(
            $this->cookie['lifetime'],
            $this->cookie['path'],
            $this->cookie['domain'],
            $this->cookie['secure'],
            $this->cookie['httponly']
        );
    }

    public function start()
    {
        if (session_id() === '') {
            if (session_start()) {
                return mt_rand(0, 4) === 0 ? $this->refresh() : true; // 1/5
            }
        }

        return false;
    }

    public function setToken($value = '')
    {
    	if (session_id() === '') {
    		// Session not started, do nothing
    		return false;
    	} else {
    		// Fixed: $value was undefined, now passed as parameter with default
    		setcookie("TestCookie", $value, [
    		    'httponly' => true,
    		    'secure' => isset($_SERVER['HTTPS']),
    		    'samesite' => 'Strict'
    		]);
    		return true;
    	}
    }

    public function forget()
    {
        if (session_id() === '') {
            return false;
        }

        $_SESSION = [];

        setcookie(
            $this->name,
            '',
            time() - 42000,
            $this->cookie['path'],
            $this->cookie['domain'],
            $this->cookie['secure'],
            $this->cookie['httponly']
        );

        return session_destroy();
    }

    public function refresh()
    {
        return session_regenerate_id(true);
    }

    /**
     * Decrypt session data using OpenSSL AES-256-CBC
     * Replaces deprecated mcrypt_decrypt with MCRYPT_3DES and MCRYPT_MODE_ECB
     */
    public function read($id)
    {
        $data = parent::read($id);
        if (empty($data)) {
            return '';
        }
        return $this->decrypt($data);
    }

    /**
     * Encrypt session data using OpenSSL AES-256-CBC
     * Replaces deprecated mcrypt_encrypt with MCRYPT_3DES and MCRYPT_MODE_ECB
     */
    public function write($id, $data)
    {
        $encrypted = $this->encrypt($data);
        return parent::write($id, $encrypted);
    }

    /**
     * Encrypt data using AES-256-CBC with proper IV
     * @param string $data Data to encrypt
     * @return string Base64 encoded encrypted data with IV prepended
     */
    private function encrypt($data) {
        // Derive a proper 256-bit key from the provided key
        $key = hash('sha256', $this->key, true);

        // Generate a cryptographically secure random IV
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);

        // Encrypt the data
        $encrypted = openssl_encrypt($data, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            return '';
        }

        // Prepend IV to encrypted data and base64 encode
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data using AES-256-CBC
     * @param string $data Base64 encoded encrypted data with IV prepended
     * @return string Decrypted data
     */
    private function decrypt($data) {
        // Derive the same 256-bit key
        $key = hash('sha256', $this->key, true);

        // Decode from base64
        $data = base64_decode($data);
        if ($data === false) {
            return '';
        }

        // Extract IV from the beginning of data
        $ivLength = openssl_cipher_iv_length($this->cipher);
        if (strlen($data) < $ivLength) {
            return '';
        }

        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        // Decrypt the data
        $decrypted = openssl_decrypt($encrypted, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            return '';
        }

        return $decrypted;
    }

    public function isExpired($ttl = 30)
    {
        $last = isset($_SESSION['_last_activity'])
            ? $_SESSION['_last_activity']
            : false;

        if ($last !== false && time() - $last > $ttl * 60) {
            return true;
        }

        $_SESSION['_last_activity'] = time();

        return false;
    }

    public function isFingerprint()
    {
        // Use SHA-256 instead of MD5 for fingerprinting
        $hash = hash('sha256',
            $_SERVER['HTTP_USER_AGENT'] .
            (ip2long($_SERVER['REMOTE_ADDR']) & ip2long('255.255.0.0'))
        );

        if (isset($_SESSION['_fingerprint'])) {
            // Use strict comparison
            return $_SESSION['_fingerprint'] === $hash;
        }

        $_SESSION['_fingerprint'] = $hash;

        return true;
    }

    public function isValid()
    {
        return ! $this->isExpired() && $this->isFingerprint();
    }

    public function get($name)
    {
        $parsed = explode('.', $name);

        $result = $_SESSION;

        while ($parsed) {
            $next = array_shift($parsed);

            if (isset($result[$next])) {
                $result = $result[$next];
            } else {
                return null;
            }
        }

        return $result;
    }

    public function put($name, $value)
    {
        $parsed = explode('.', $name);

        $session =& $_SESSION;

        while (count($parsed) > 1) {
            $next = array_shift($parsed);

            if ( ! isset($session[$next]) || ! is_array($session[$next])) {
                $session[$next] = [];
            }

            $session =& $session[$next];
        }

        $session[array_shift($parsed)] = $value;
    }

}
