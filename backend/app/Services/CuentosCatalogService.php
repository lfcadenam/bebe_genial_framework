<?php

namespace App\Services;

class CuentosCatalogService
{
    private static ?array $catalog = null;

    /**
     * Retorna el catálogo completo con las 12 categorías y los 365 cuentos interactivos
     */
    public static function getCatalog(): array
    {
        if (self::$catalog !== null) {
            return self::$catalog;
        }

        $jsonPath = storage_path('app/cuentos_catalog.json');
        if (file_exists($jsonPath)) {
            $data = json_decode(file_get_contents($jsonPath), true);
            if (is_array($data) && !empty($data)) {
                self::$catalog = $data;
                return self::$catalog;
            }
        }

        return [];
    }

    /**
     * Retorna todos los IDs numéricos de cuentos disponibles (1 a 365)
     */
    public static function getAllStoryIds(): array
    {
        $catalog = self::getCatalog();
        $ids = [];
        foreach ($catalog as $cat) {
            foreach ($cat['cuentos'] ?? [] as $cuento) {
                if (isset($cuento['id'])) {
                    $ids[] = (int) $cuento['id'];
                }
            }
        }
        return $ids;
    }

    /**
     * Total global de cuentos en el catálogo (365)
     */
    public static function getTotalStoriesCount(): int
    {
        $ids = self::getAllStoryIds();
        return count($ids) > 0 ? count($ids) : 365;
    }
}
