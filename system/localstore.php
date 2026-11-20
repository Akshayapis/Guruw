<?php
/**
 * Cashpe — development / fallback JSON store.
 *
 * Used automatically when Firebase credentials are not configured, so the
 * Docker image boots and is fully usable out of the box. Set the Firebase
 * environment variables on Render to run the identical code on Firestore.
 */
class CashpeLocalStore
{
    private static $data = null;

    public static function path()
    {
        return dirname(__DIR__) . '/storage/db.json';
    }

    private static function load()
    {
        if (self::$data !== null) {
            return self::$data;
        }
        $path = self::path();
        if (!is_file($path)) {
            @copy(dirname(__DIR__) . '/storage/db.default.json', $path);
        }
        self::$data = is_file($path) ? (json_decode(file_get_contents($path), true) ?: array()) : array();
        return self::$data;
    }

    public static function rows($collection)
    {
        $data = self::load();
        return isset($data[$collection]) ? $data[$collection] : array();
    }

    public static function write($collection, $rows)
    {
        $data = self::load();
        $data[$collection] = $rows;
        self::$data = $data;
        $path = self::path();
        $fp = fopen($path, 'c+');
        if ($fp) {
            flock($fp, LOCK_EX);
            ftruncate($fp, 0);
            fwrite($fp, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        return true;
    }
}
