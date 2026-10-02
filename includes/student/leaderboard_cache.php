<?php

if (!class_exists('StudentLeaderboardCache')) {
    class StudentLeaderboardCache
    {
        private const DEFAULT_TTL = 300;

        public static function cacheDir(): string
        {
            $dir = dirname(__DIR__, 2) . '/storage/cache/leaderboards';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            return $dir;
        }

        public static function getNational(PDO $pdo, int $limit = 10, int $ttl = self::DEFAULT_TTL): array
        {
            $key = 'national_' . $limit;
            $cached = self::read($key, $ttl);
            if ($cached !== null) {
                return $cached;
            }

            $rows = $pdo->query("
                SELECT full_name, total_points, college_name, level_name
                FROM sa_students
                WHERE status = 'Active'
                ORDER BY total_points DESC
                LIMIT " . (int)$limit
            )->fetchAll(PDO::FETCH_ASSOC);

            self::write($key, $rows);
            return $rows;
        }

        public static function getScoped(PDO $pdo, string $scope, string $value, int $limit = 10, int $ttl = self::DEFAULT_TTL): array
        {
            $column = match ($scope) {
                'city' => 'city_name',
                'campus' => 'college_name',
                'state' => 'state_name',
                default => null,
            };

            if ($column === null || trim($value) === '') {
                return [];
            }

            $key = $scope . '_' . md5($value) . '_' . $limit;
            $cached = self::read($key, $ttl);
            if ($cached !== null) {
                return $cached;
            }

            $stmt = $pdo->prepare("
                SELECT full_name, total_points, college_name, level_name
                FROM sa_students
                WHERE status = 'Active' AND {$column} = ?
                ORDER BY total_points DESC
                LIMIT ?
            ");
            $stmt->bindValue(1, $value);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            self::write($key, $rows);
            return $rows;
        }

        public static function invalidateAll(): void
        {
            $dir = self::cacheDir();
            foreach (glob($dir . '/*.json') ?: [] as $file) {
                @unlink($file);
            }
        }

        private static function read(string $key, int $ttl): ?array
        {
            $path = self::cacheDir() . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) . '.json';
            if (!is_file($path)) {
                return null;
            }

            if (filemtime($path) < (time() - $ttl)) {
                @unlink($path);
                return null;
            }

            $payload = json_decode((string)file_get_contents($path), true);
            return is_array($payload) ? $payload : null;
        }

        private static function write(string $key, array $data): void
        {
            $path = self::cacheDir() . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) . '.json';
            file_put_contents($path, json_encode($data), LOCK_EX);
        }
    }
}
