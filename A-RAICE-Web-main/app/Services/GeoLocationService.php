<?php

namespace App\Services;

use App\Enums\MunicipalityEnum;

class GeoLocationService
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $geoJsonCache = null;

    /**
     * Determine if a given lat/lng coordinate falls inside the boundaries of a municipality.
     */
    public function insideMunicipality(string $slug, float $lat, float $lng): bool
    {
        $collection = $this->loadGeoJson();

        if ($collection === null) {
            return false;
        }

        foreach ($collection['features'] as $feature) {
            if (($feature['properties']['municipality'] ?? null) !== $slug) {
                continue;
            }

            $geometry = $feature['geometry'];

            /** @var list<list<list<array{0: float, 1: float}>>> $polygons */
            $polygons = $geometry['type'] === 'Polygon'
                ? [$geometry['coordinates']]
                : $geometry['coordinates'];

            foreach ($polygons as $rings) {
                $outer = array_shift($rings);

                if (! $this->insideRing($outer, $lat, $lng)) {
                    continue;
                }

                foreach ($rings as $hole) {
                    if ($this->insideRing($hole, $lat, $lng)) {
                        continue 2;
                    }
                }

                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Return list of all valid municipalities in Bataan.
     *
     * @return array<int, array{name: string, slug: string, value: string, psgc: ?string}>
     */
    public function getMunicipalities(): array
    {
        $collection = $this->loadGeoJson();
        $psgcMap = [];

        if ($collection !== null) {
            foreach ($collection['features'] as $feature) {
                $slug = $feature['properties']['municipality'] ?? null;
                $psgc = $feature['properties']['psgc'] ?? null;
                if ($slug) {
                    $psgcMap[$slug] = $psgc;
                }
            }
        }

        $municipalities = [];
        foreach (MunicipalityEnum::cases() as $case) {
            $municipalities[] = [
                'name' => ucfirst($case->value),
                'slug' => $case->value,
                'value' => $case->value,
                'psgc' => $psgcMap[$case->value] ?? null,
            ];
        }

        return $municipalities;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadGeoJson(): ?array
    {
        if ($this->geoJsonCache !== null) {
            return $this->geoJsonCache;
        }

        $path = public_path('geo/bataan-municipalities.geojson');
        if (! file_exists($path)) {
            return null;
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return null;
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);
        $this->geoJsonCache = $decoded;

        return $this->geoJsonCache;
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring  GeoJSON ring of [lng, lat] points
     */
    private function insideRing(array $ring, float $lat, float $lng): bool
    {
        $inside = false;

        for ($i = 0, $j = count($ring) - 1; $i < count($ring); $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if (($yi > $lat) !== ($yj > $lat)
                && $lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
