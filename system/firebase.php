<?php
/**
 * Cashpe — Firebase / Google Cloud Firestore REST client.
 *
 * No external SDK or Composer is required. The client authenticates with a
 * Firebase service account (RS256 JWT -> Google OAuth2 access token) and talks
 * directly to the Firestore REST API. It is deliberately dependency free so
 * the very same code runs on Render, on a local Docker container and on a
 * plain PHP 8 host.
 *
 * Configuration (environment variables on Render):
 *   FIREBASE_PROJECT_ID          required
 *   FIREBASE_CLIENT_EMAIL        required
 *   FIREBASE_PRIVATE_KEY         required (the -----BEGIN PRIVATE KEY----- block; \n is allowed)
 *   FIREBASE_SERVICE_ACCOUNT     alternative: complete service-account JSON (or base64 of it)
 *   FIREBASE_SERVICE_ACCOUNT_PATH alternative: path to service-account.json (never commit it)
 *   FIRESTORE_EMULATOR_HOST      optional: host:port of a local Firestore emulator
 */

class CashpeFirebase
{
    private static $config = null;
    private static $configured = false;
    private static $accessToken = null;
    private static $tokenExpiresAt = 0;
    private static $baseUrl = null;

    /** Read and normalise credentials from the environment. */
    public static function config()
    {
        if (self::$configured) {
            return self::$config;
        }
        self::$configured = true;

        $json = null;
        $raw = getenv('FIREBASE_SERVICE_ACCOUNT');
        if ($raw) {
            $decoded = base64_decode($raw, true);
            $json = json_decode($decoded !== false && $decoded[0] === '{' ? $decoded : $raw, true);
        }
        if (!$json && getenv('FIREBASE_SERVICE_ACCOUNT_PATH')) {
            $file = getenv('FIREBASE_SERVICE_ACCOUNT_PATH');
            if (is_file($file)) {
                $json = json_decode(file_get_contents($file), true);
            }
        }

        $projectId = getenv('FIREBASE_PROJECT_ID');
        $clientEmail = getenv('FIREBASE_CLIENT_EMAIL');
        $privateKey = getenv('FIREBASE_PRIVATE_KEY');

        if (is_array($json)) {
            $projectId = $projectId ?: (isset($json['project_id']) ? $json['project_id'] : null);
            $clientEmail = $clientEmail ?: (isset($json['client_email']) ? $json['client_email'] : null);
            $privateKey = $privateKey ?: (isset($json['private_key']) ? $json['private_key'] : null);
        }
        if ($privateKey) {
            // Environment variables frequently store the PEM with literal \n.
            $privateKey = str_replace('\\n', "\n", $privateKey);
        }

        $emulator = getenv('FIRESTORE_EMULATOR_HOST');
        if ($emulator) {
            self::$baseUrl = 'http://' . $emulator . '/v1/projects/' . ($projectId ?: 'cashpe-local') . '/databases/(default)/documents';
        } elseif ($projectId) {
            self::$baseUrl = 'https://firestore.googleapis.com/v1/projects/' . $projectId . '/databases/(default)/documents';
        }

        if ($projectId && ($clientEmail && $privateKey || $emulator)) {
            self::$config = array(
                'project_id' => $projectId,
                'client_email' => $clientEmail,
                'private_key' => $privateKey,
                'emulator' => (bool) $emulator,
            );
        } else {
            self::$config = null;
        }
        return self::$config;
    }

    /** True when Firebase credentials are present. */
    public static function enabled()
    {
        return self::config() !== null;
    }

    public static function baseUrl()
    {
        self::config();
        return self::$baseUrl;
    }

    /** Build (and cache for one hour) the Google OAuth2 access token. */
    public static function accessToken()
    {
        $cfg = self::config();
        if (!$cfg || $cfg['emulator']) {
            return null;
        }
        if (self::$accessToken && time() < self::$tokenExpiresAt - 60) {
            return self::$accessToken;
        }

        $now = time();
        $header = self::base64Url(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
        $payload = self::base64Url(json_encode(array(
            'iss' => $cfg['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        )));
        $unsigned = $header . '.' . $payload;
        $key = openssl_pkey_get_private($cfg['private_key']);
        if ($key === false) {
            throw new RuntimeException('Cashpe: invalid FIREBASE_PRIVATE_KEY');
        }
        $signature = '';
        if (!openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Cashpe: could not sign Firebase JWT');
        }
        $jwt = $unsigned . '.' . self::base64Url($signature);

        $response = self::http('https://oauth2.googleapis.com/token', 'POST',
            http_build_query(array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            )),
            array('Content-Type: application/x-www-form-urlencoded'), false);
        if (empty($response['access_token'])) {
            throw new RuntimeException('Cashpe: Firebase authentication failed');
        }
        self::$accessToken = $response['access_token'];
        self::$tokenExpiresAt = $now + (int) (isset($response['expires_in']) ? $response['expires_in'] : 3600);
        return self::$accessToken;
    }

    private static function base64Url($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /** Raw HTTP helper shared by the token and document calls. */
    private static function http($url, $method, $body = null, $headers = array(), $auth = true)
    {
        $ch = curl_init();
        $defaultHeaders = array('Accept: application/json');
        if ($auth && ($token = self::accessToken())) {
            $defaultHeaders[] = 'Authorization: Bearer ' . $token;
        }
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => array_merge($defaultHeaders, $headers),
        );
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('Cashpe Firestore request failed: ' . $error);
        }
        $data = json_decode($raw, true);
        if ($status >= 400) {
            $message = is_array($data) && isset($data['error']['message'])
                ? $data['error']['message'] : $raw;
            throw new RuntimeException('Cashpe Firestore HTTP ' . $status . ': ' . $message);
        }
        return $data;
    }

    /** Read every document of a collection (with pagination). */
    public static function listDocuments($collection)
    {
        $url = self::baseUrl() . '/' . rawurlencode($collection);
        $docs = array();
        $pageToken = null;
        do {
            $query = array('pageSize' => 1000);
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }
            $separator = (strpos($url, '?') === false) ? '?' : '&';
            $data = self::http($url . $separator . http_build_query($query), 'GET');
            foreach ((isset($data['documents']) ? $data['documents'] : array()) as $doc) {
                $name = $doc['name'];
                $docs[substr($name, strrpos($name, '/') + 1)] = self::decodeFields((isset($doc['fields']) ? $doc['fields'] : array()));
            }
            $pageToken = (isset($data['nextPageToken']) ? $data['nextPageToken'] : null);
        } while ($pageToken);
        return $docs;
    }

    /** Fetch a single document by id. */
    public static function getDocument($collection, $id)
    {
        $data = self::http(self::baseUrl() . '/' . rawurlencode($collection) . '/' . rawurlencode($id), 'GET');
        return self::decodeFields((isset($data['fields']) ? $data['fields'] : array()));
    }

    /**
     * Commit a batch of writes.
     * Each write: array('op' => 'set'|'delete', 'collection' => ..., 'id' => ..., 'fields' => ...)
     */
    public static function commit($writes)
    {
        if (!$writes) {
            return true;
        }
        $payload = array('writes' => array());
        foreach ($writes as $write) {
            $name = 'projects/' . self::config()['project_id'] . '/databases/(default)/documents/' . $write['collection'] . '/' . $write['id'];
            if ($write['op'] === 'delete') {
                $payload['writes'][] = array('delete' => $name);
            } else {
                $payload['writes'][] = array('update' => array(
                    'name' => $name,
                    'fields' => self::encodeFields($write['fields']),
                ));
            }
        }
        self::http(self::baseUrl() . ':commit', 'POST', json_encode($payload),
            array('Content-Type: application/json'));
        return true;
    }

    /** Convert PHP values into Firestore typed field values. Strings are intentional. */
    public static function encodeFields($fields)
    {
        $out = array();
        foreach ($fields as $key => $value) {
            if ($value === null) {
                $out[$key] = array('nullValue' => null);
            } elseif (is_bool($value)) {
                $out[$key] = array('booleanValue' => $value);
            } elseif (is_int($value) || is_float($value)) {
                $out[$key] = array('stringValue' => (string) $value);
            } else {
                $out[$key] = array('stringValue' => (string) $value);
            }
        }
        return $out;
    }

    /** Convert Firestore typed field values back to plain strings. */
    public static function decodeFields($fields)
    {
        $out = array();
        foreach ($fields as $key => $value) {
            if (isset($value['stringValue'])) {
                $out[$key] = $value['stringValue'];
            } elseif (isset($value['integerValue'])) {
                $out[$key] = (string) $value['integerValue'];
            } elseif (isset($value['doubleValue'])) {
                $out[$key] = (string) $value['doubleValue'];
            } elseif (isset($value['booleanValue'])) {
                $out[$key] = $value['booleanValue'] ? '1' : '';
            } elseif (array_key_exists('nullValue', $value)) {
                $out[$key] = '';
            } elseif (isset($value['timestampValue'])) {
                $out[$key] = $value['timestampValue'];
            } else {
                $out[$key] = json_encode($value);
            }
        }
        return $out;
    }
}
