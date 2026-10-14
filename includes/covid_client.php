<?php
declare(strict_types=1);

/**
 * covid_client.php — Real COVID-19 data client for disease.sh v3 API.
 * Handles server-side fetching, MySQL caching, graceful degradation, and offline fallback.
 */

class CovidClient
{
    private const BASE_URL = 'https://disease.sh/v3/covid-19/';
    private const CONNECT_TIMEOUT = 5;
    private const TOTAL_TIMEOUT = 10;
    private const MAX_RESPONSE_SIZE = 10485760; // 10MB guard

    /**
     * Fetch global summary statistics.
     */
    public static function getGlobal(): array
    {
        $cacheKey = 'covid_global';
        $ttl = (int)(APP_CFG['cache_ttl_global'] ?? 3600);

        return self::fetchWithCache($cacheKey, 'all', $ttl, function (array $raw) {
            return [
                'cases'         => (int)($raw['cases'] ?? 0),
                'deaths'        => (int)($raw['deaths'] ?? 0),
                'recovered'     => (int)($raw['recovered'] ?? 0),
                'active'        => (int)($raw['active'] ?? 0),
                'todayCases'    => (int)($raw['todayCases'] ?? 0),
                'todayDeaths'   => (int)($raw['todayDeaths'] ?? 0),
                'todayRecovered'=> (int)($raw['todayRecovered'] ?? 0),
                'critical'      => (int)($raw['critical'] ?? 0),
                'tests'         => (int)($raw['tests'] ?? 0),
                'population'    => (int)($raw['population'] ?? 0),
                'updated'       => (int)($raw['updated'] ?? 0),
            ];
        });
    }

    /**
     * Fetch country breakdown.
     */
    public static function getCountries(): array
    {
        $cacheKey = 'covid_countries';
        $ttl = (int)(APP_CFG['cache_ttl_global'] ?? 3600);

        return self::fetchWithCache($cacheKey, 'countries', $ttl, function ($raw) {
            if (!is_array($raw)) return [];
            $list = [];
            foreach ($raw as $c) {
                if (!isset($c['country'])) continue;
                $list[] = [
                    'country'         => (string)$c['country'],
                    'iso2'            => (string)($c['countryInfo']['iso2'] ?? ''),
                    'flag'            => (string)($c['countryInfo']['flag'] ?? ''),
                    'continent'       => (string)($c['continent'] ?? 'Unknown'),
                    'cases'           => (int)($c['cases'] ?? 0),
                    'deaths'          => (int)($c['deaths'] ?? 0),
                    'recovered'       => (int)($raw['recovered'] ?? ($c['recovered'] ?? 0)),
                    'active'          => (int)($c['active'] ?? 0),
                    'todayCases'      => (int)($c['todayCases'] ?? 0),
                    'todayDeaths'     => (int)($c['todayDeaths'] ?? 0),
                    'tests'           => (int)($c['tests'] ?? 0),
                    'population'      => (int)($c['population'] ?? 0),
                    'casesPerMillion' => (int)($c['casesPerOneMillion'] ?? 0),
                    'deathsPerMillion'=> (int)($c['deathsPerOneMillion'] ?? 0),
                ];
            }
            return $list;
        });
    }

    /**
     * Fetch historical trend data.
     * @param string $days '30', '90', '180', '365', or 'all'
     */
    public static function getHistorical(string $days = '30'): array
    {
        $allowed = ['30', '90', '180', '365', 'all'];
        if (!in_array($days, $allowed, true)) {
            $days = '30';
        }

        $cacheKey = 'covid_historical_' . $days;
        $endpoint = 'historical/all?lastdays=' . $days;
        $ttl = (int)(APP_CFG['cache_ttl_history'] ?? 21600);

        return self::fetchWithCache($cacheKey, $endpoint, $ttl, function (array $raw) {
            // Normalise date keys from "M/D/YY" to ISO "YYYY-MM-DD"
            $casesNorm = self::normaliseDateKeys($raw['cases'] ?? []);
            $deathsNorm = self::normaliseDateKeys($raw['deaths'] ?? []);
            $recoveredNorm = self::normaliseDateKeys($raw['recovered'] ?? []);

            return [
                'cases'     => $casesNorm,
                'deaths'    => $deathsNorm,
                'recovered' => $recoveredNorm,
            ];
        });
    }

    /**
     * Core caching + HTTP fetching mechanism.
     */
    private static function fetchWithCache(string $cacheKey, string $endpoint, int $ttl, callable $transformer): array
    {
        $pdo = db_try();
        $cachedRow = null;

        if ($pdo) {
            try {
                $stmt = $pdo->prepare('SELECT payload, fetched_at FROM covid_cache WHERE cache_key = ? LIMIT 1');
                $stmt->execute([$cacheKey]);
                $cachedRow = $stmt->fetch();
            } catch (Throwable $e) {
                error_log('covid_cache query failed: ' . $e->getMessage());
            }
        }

        $now = time();
        $isFresh = false;
        if ($cachedRow) {
            $age = $now - strtotime($cachedRow['fetched_at']);
            if ($age < $ttl) {
                $isFresh = true;
                $decoded = json_decode($cachedRow['payload'], true);
                if ($decoded !== null) {
                    return [
                        'ok'        => true,
                        'source'    => 'disease.sh',
                        'updatedAt' => date('c', strtotime($cachedRow['fetched_at'])),
                        'stale'     => false,
                        'data'      => $decoded,
                    ];
                }
            }
        }

        // Fresh cache not available — perform upstream live request
        $liveResult = self::requestUpstream($endpoint);

        if ($liveResult['ok']) {
            $transformed = $transformer($liveResult['data']);
            $jsonPayload = json_encode($transformed);

            if ($pdo) {
                try {
                    $upsert = $pdo->prepare(
                        'INSERT INTO covid_cache (cache_key, payload, fetched_at)
                         VALUES (?, ?, NOW())
                         ON DUPLICATE KEY UPDATE payload = VALUES(payload), fetched_at = NOW()'
                    );
                    $upsert->execute([$cacheKey, $jsonPayload]);
                } catch (Throwable $e) {
                    error_log('covid_cache store failed: ' . $e->getMessage());
                }
            }

            return [
                'ok'        => true,
                'source'    => 'disease.sh',
                'updatedAt' => date('c'),
                'stale'     => false,
                'data'      => $transformed,
            ];
        }

        // Live request failed — fallback strategy
        if ($cachedRow) {
            $staleData = json_decode($cachedRow['payload'], true);
            if ($staleData !== null) {
                return [
                    'ok'           => true,
                    'source'       => 'disease.sh (cached)',
                    'updatedAt'    => date('c', strtotime($cachedRow['fetched_at'])),
                    'stale'        => true,
                    'staleMessage' => 'Live upstream temporarily unavailable. Displaying data last cached on ' . date('M j, Y H:i:s T', strtotime($cachedRow['fetched_at'])),
                    'data'         => $staleData,
                ];
            }
        }

        // Try offline snapshot fallback file
        $fallbackFile = APP_ROOT . '/data/covid-fallback.json';
        if (file_exists($fallbackFile)) {
            $fbContent = file_get_contents($fallbackFile);
            $fbData = json_decode($fbContent, true);
            if ($fbData !== null) {
                if (str_starts_with($cacheKey, 'covid_historical')) {
                    return [
                        'ok'           => true,
                        'source'       => 'disease.sh (offline snapshot)',
                        'updatedAt'    => date('c', filemtime($fallbackFile)),
                        'stale'        => true,
                        'staleMessage' => 'Displaying verified historical snapshot from data/covid-fallback.json',
                        'data'         => [
                            'cases'     => self::normaliseDateKeys($fbData['cases'] ?? []),
                            'deaths'    => self::normaliseDateKeys($fbData['deaths'] ?? []),
                            'recovered' => self::normaliseDateKeys($fbData['recovered'] ?? []),
                        ]
                    ];
                }
            }
        }

        // Clear empty state — never invent data
        return [
            'ok'    => false,
            'error' => [
                'code'    => 'UPSTREAM_UNAVAILABLE',
                'message' => 'COVID-19 data source is currently unreachable and no cache is available.'
            ]
        ];
    }

    /**
     * Perform HTTP GET request via cURL with strict timeouts and SSL checks.
     */
    private static function requestUpstream(string $endpoint): array
    {
        $base = rtrim(APP_CFG['covid_api_base'] ?? self::BASE_URL, '/') . '/';
        $url = $base . ltrim($endpoint, '/');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT        => self::TOTAL_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT      => 'COVID19-SQA-Portal/1.0',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err !== '' || $httpCode !== 200 || !is_string($body)) {
            error_log("Upstream request to {$url} failed: code={$httpCode}, err={$err}");
            return ['ok' => false];
        }

        $decoded = json_decode($body, true);
        if ($decoded === null) {
            error_log("Upstream JSON decode error on {$url}");
            return ['ok' => false];
        }

        return ['ok' => true, 'data' => $decoded];
    }

    /**
     * Convert "M/D/YY" or "MM/DD/YY" date strings to "YYYY-MM-DD".
     */
    private static function normaliseDateKeys(array $series): array
    {
        $out = [];
        foreach ($series as $dateStr => $val) {
            $parts = explode('/', (string)$dateStr);
            if (count($parts) === 3) {
                $m = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
                $d = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
                $y = (int)$parts[2];
                $year = ($y < 50) ? 2000 + $y : 1900 + $y;
                $isoDate = sprintf('%04d-%s-%s', $year, $m, $d);
                $out[$isoDate] = (int)$val;
            } else {
                $out[$dateStr] = (int)$val;
            }
        }
        return $out;
    }
}
