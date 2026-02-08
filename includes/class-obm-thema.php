<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class OBM_Thema
{
    private static array $map = [];
    private static bool $loaded = false;

    /**
     * Load and cache the Thema vocabulary.
     */
    private static function load(): void
    {
        if (self::$loaded) return;

        $file = OBM_PATH . 'assets/thema_da.json';
        if (!file_exists($file)) {
            self::$loaded = true;
            return;
        }

        $cache_key = 'obm_thema_map';
        $mtime = filemtime($file);
        $cached = get_transient($cache_key);

        // Return cached data if file hasn't changed
        if (is_array($cached) && isset($cached['mtime']) && $cached['mtime'] === $mtime) {
            self::$map = $cached['data'];
            self::$loaded = true;
            return;
        }

        $json_content = file_get_contents($file);
        $data = json_decode($json_content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $codes = $data['CodeList']['ThemaCodes']['Code'] ?? null;

            if ($codes) {
                // Handle the "Single Entry" quirk (Object vs Array)
                if (isset($codes['CodeValue'])) {
                    $codes = [$codes];
                }

                if (is_array($codes)) {
                    foreach ($codes as $entry) {
                        if (isset($entry['CodeValue'], $entry['CodeDescription'])) {
                            // Normalize keys to uppercase and trimmed strings
                            $key = strtoupper(trim((string)$entry['CodeValue']));
                            self::$map[$key] = (string)$entry['CodeDescription'];
                        }
                    }
                }
            }
        }

        // Cache the processed map
        set_transient($cache_key, [
            'mtime' => $mtime,
            'data'  => self::$map
        ], YEAR_IN_SECONDS);

        self::$loaded = true;
    }

    /**
     * Get the Danish description for a Thema code.
     */
    public static function subject(?string $code): ?string
    {
        if (empty($code)) return null;
        
        self::load();
        
        $search_code = strtoupper(trim($code));

        // Return the label if found, otherwise fall back to the raw code
        return self::$map[$search_code] ?? $code;
    }
}