<?php
namespace App\Helpers;

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function setFlash($type, $message)
    {
        self::start();
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
    
    public static function getFlash()
    {
        self::start();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
    
    public static function hasFlash()
    {
        self::start();
        return isset($_SESSION['flash']);
    }
    
    public static function remove($key)
    {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function destroy()
    {
        self::start();
        session_destroy();
        $_SESSION = [];
    }
}