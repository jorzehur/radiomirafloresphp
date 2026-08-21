<?php
/**
 * Sistema de caché - APCu con fallback a archivo
 */

require_once __DIR__ . '/../config.php';

class Cache {
    private static $enabled = null;
    private static $fileCacheDir = null;
    
    public static function isEnabled() {
        if (self::$enabled === null) {
            self::$enabled = CACHE_ENABLED && function_exists('apcu_store');
        }
        return self::$enabled;
    }
    
    private static function getFileCacheDir() {
        if (self::$fileCacheDir === null) {
            self::$fileCacheDir = __DIR__ . '/../.cache/';
            if (!is_dir(self::$fileCacheDir)) {
                @mkdir(self::$fileCacheDir, 0755, true);
            }
        }
        return self::$fileCacheDir;
    }
    
    public static function get($key) {
        if (self::isEnabled()) {
            $value = apcu_fetch($key, $success);
            return $success ? $value : false;
        }
        
        $file = self::getFileCacheDir() . md5($key) . '.cache';
        if (!file_exists($file)) return false;
        
        $data = @unserialize(file_get_contents($file));
        if (!$data) return false;
        
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            @unlink($file);
            return false;
        }
        
        return $data['value'];
    }
    
    public static function set($key, $value, $ttl = null) {
        if ($ttl === null) {
            $ttl = CACHE_TTL;
        }
        
        if (self::isEnabled()) {
            return apcu_store($key, $value, $ttl);
        }
        
        $file = self::getFileCacheDir() . md5($key) . '.cache';
        $data = [
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'value' => $value
        ];
        
        return @file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }
    
    public static function delete($key) {
        if (self::isEnabled()) {
            return apcu_delete($key);
        }
        
        $file = self::getFileCacheDir() . md5($key) . '.cache';
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }
    
    public static function clear() {
        if (self::isEnabled()) {
            return apcu_clear_cache();
        }
        
        $dir = self::getFileCacheDir();
        $files = glob($dir . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        return true;
    }
    
    public static function remember($key, $callback, $ttl = null) {
        $cached = self::get($key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $value = $callback();
        self::set($key, $value, $ttl);
        
        return $value;
    }
}

function cache_get($key) {
    return Cache::get($key);
}

function cache_set($key, $value, $ttl = null) {
    return Cache::set($key, $value, $ttl);
}

function cache_remember($key, $callback, $ttl = null) {
    return Cache::remember($key, $callback, $ttl);
}

function cache_clear() {
    return Cache::clear();
}

function cache_delete($key) {
    return Cache::delete($key);
}
