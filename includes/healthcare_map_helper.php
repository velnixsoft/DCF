<?php
/**
 * Healthcare Partner Map & Geolocation Helper
 * Handles smart-parsing of Google Map URLs, Coordinates (Lat/Long), Embed Iframes, and Map Pin links.
 * 
 * Author: VELNIX SOFT / Antigravity AI
 * Date: 2026-09-12
 */

if (!function_exists('parseGoogleMapLocationInput')) {
    /**
     * Smart parses raw user input for Google Maps into standardized columns:
     * map_location, latitude, longitude, and map_embed_url.
     *
     * @param string $rawInput Raw input (link, coords, or iframe embed code)
     * @param float|string|null $explicitLat Explicitly supplied latitude
     * @param float|string|null $explicitLng Explicitly supplied longitude
     * @param string|null $explicitEmbed Explicitly supplied embed URL
     * @param string $address Fallback address
     * @param string $city Fallback district/city
     * @param string $state Fallback state
     * @param string $name Facility / Provider name
     * @return array [map_location, latitude, longitude, map_embed_url, has_coords]
     */
    function parseGoogleMapLocationInput(
        $rawInput = '',
        $explicitLat = null,
        $explicitLng = null,
        $explicitEmbed = null,
        $address = '',
        $city = '',
        $state = '',
        $name = ''
    ) {
        $rawInput = trim((string)$rawInput);
        $explicitEmbed = trim((string)$explicitEmbed);
        $lat = ($explicitLat !== null && $explicitLat !== '') ? (float)$explicitLat : null;
        $lng = ($explicitLng !== null && $explicitLng !== '') ? (float)$explicitLng : null;
        $mapLocation = $rawInput;
        $embedUrl = $explicitEmbed;

        // 1. Check if rawInput is an HTML iframe tag
        if (preg_match('/<iframe.*?src=["\']([^"\']+)["\']/i', $rawInput, $iframeMatches)) {
            $embedUrl = $iframeMatches[1];
            $mapLocation = $iframeMatches[1];
        }

        // 2. Check if rawInput is a comma-separated latitude and longitude (e.g. "28.628929, 77.221532")
        if (preg_match('/^\s*([+-]?\d{1,3}(?:\.\d+)?)\s*,\s*([+-]?\d{1,3}(?:\.\d+)?)\s*$/', $rawInput, $coordMatches)) {
            $lat = (float)$coordMatches[1];
            $lng = (float)$coordMatches[2];
            $mapLocation = "https://maps.google.com/?q={$lat},{$lng}";
            $embedUrl = "https://maps.google.com/maps?q={$lat},{$lng}&hl=en&z=15&output=embed";
        }

        // 3. Check if rawInput contains @lat,lng (standard Google Maps URL pattern)
        if ($lat === null || $lng === null) {
            if (preg_match('/@([+-]?\d{1,3}(?:\.\d+)?),([+-]?\d{1,3}(?:\.\d+)?)/', $rawInput, $atMatches)) {
                $lat = (float)$atMatches[1];
                $lng = (float)$atMatches[2];
            }
        }

        // 4. Check if rawInput contains pb=!1m...!3d(lat)!4d(lng) (Google Embed URL pattern)
        if ($lat === null || $lng === null) {
            if (preg_match('/!3d([+-]?\d{1,3}(?:\.\d+)?).*?!4d([+-]?\d{1,3}(?:\.\d+)?)/', $rawInput, $pbMatches)) {
                $lat = (float)$pbMatches[1];
                $lng = (float)$pbMatches[2];
            }
        }

        // 5. Check if rawInput contains query coordinates (e.g. q=28.6289,77.2215)
        if ($lat === null || $lng === null) {
            if (preg_match('/[?&]q=([+-]?\d{1,3}(?:\.\d+)?),([+-]?\d{1,3}(?:\.\d+)?)/', $rawInput, $qMatches)) {
                $lat = (float)$qMatches[1];
                $lng = (float)$qMatches[2];
            }
        }

        // 6. Build default map navigation location if not provided
        $fallbackQuery = trim(($name ? $name . ', ' : '') . ($address ? $address . ', ' : '') . ($city ? $city . ', ' : '') . $state);
        if (empty($mapLocation)) {
            if ($lat !== null && $lng !== null) {
                $mapLocation = "https://maps.google.com/?q={$lat},{$lng}";
            } elseif (!empty($fallbackQuery)) {
                $mapLocation = "https://www.google.com/maps/search/?api=1&query=" . urlencode($fallbackQuery);
            }
        }

        // 7. Build default embed URL if empty
        if (empty($embedUrl)) {
            if ($lat !== null && $lng !== null) {
                $embedUrl = "https://maps.google.com/maps?q={$lat},{$lng}&hl=en&z=15&output=embed";
            } elseif (!empty($fallbackQuery)) {
                $embedUrl = "https://maps.google.com/maps?q=" . urlencode($fallbackQuery) . "&hl=en&z=15&output=embed";
            }
        }

        return [
            'map_location' => $mapLocation ?: null,
            'latitude' => $lat,
            'longitude' => $lng,
            'map_embed_url' => $embedUrl ?: null,
            'has_coords' => ($lat !== null && $lng !== null)
        ];
    }
}

if (!function_exists('getProviderMapDetails')) {
    /**
     * Resolves complete map links and embed sources for a healthcare provider record.
     *
     * @param array $provider
     * @return array
     */
    function getProviderMapDetails($provider) {
        $lat = !empty($provider['latitude']) ? (float)$provider['latitude'] : null;
        $lng = !empty($provider['longitude']) ? (float)$provider['longitude'] : null;
        $mapLocation = !empty($provider['map_location']) ? trim($provider['map_location']) : '';
        $embedUrl = !empty($provider['map_embed_url']) ? trim($provider['map_embed_url']) : '';

        $name = $provider['name'] ?? '';
        $address = $provider['address'] ?? '';
        $district = $provider['district'] ?? '';
        $state = $provider['state'] ?? '';

        $hasExactCoords = ($lat !== null && $lng !== null && abs($lat) > 0 && abs($lng) > 0);

        // Standard navigation link
        if (!empty($mapLocation) && (strpos($mapLocation, 'http://') === 0 || strpos($mapLocation, 'https://') === 0)) {
            $viewUrl = $mapLocation;
        } elseif ($hasExactCoords) {
            $viewUrl = "https://maps.google.com/?q={$lat},{$lng}";
        } else {
            $query = trim(($name ? $name . ', ' : '') . ($address ? $address . ', ' : '') . ($district ? $district . ', ' : '') . $state);
            $viewUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($query);
        }

        // Standard Embed iframe URL
        if (!empty($embedUrl) && (strpos($embedUrl, 'http://') === 0 || strpos($embedUrl, 'https://') === 0)) {
            $embedSrc = $embedUrl;
        } elseif ($hasExactCoords) {
            $embedSrc = "https://maps.google.com/maps?q={$lat},{$lng}&hl=en&z=15&output=embed";
        } else {
            $query = trim(($name ? $name . ', ' : '') . ($address ? $address . ', ' : '') . ($district ? $district . ', ' : '') . $state);
            $embedSrc = "https://maps.google.com/maps?q=" . urlencode($query) . "&hl=en&z=15&output=embed";
        }

        return [
            'view_url' => $viewUrl,
            'embed_url' => $embedSrc,
            'latitude' => $lat,
            'longitude' => $lng,
            'has_exact_coords' => $hasExactCoords,
            'coords_display' => $hasExactCoords ? (number_format($lat, 5) . ', ' . number_format($lng, 5)) : null,
            'pin_badge_label' => $hasExactCoords ? 'Verified GPS Pin' : 'Map Location'
        ];
    }
}
