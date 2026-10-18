<?php

namespace App\Services;

use App\Models\Disease;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ViTService
{
    /**
     * @return array{disease: ?Disease, confidence: float, raw: array<string, mixed>}
     */
    public function predict(string $path): array
    {
        $this->wake();

        $data = Http::timeout(config('services.vit.timeout'))
            ->attach('file', (string) file_get_contents($path), basename($path))
            ->post(config('services.vit.url').'/predict')
            ->throw()
            ->json();

        $prediction = $data['data']['prediction'] ?? null;
        $className = $prediction['class_name'] ?? null;

        return [
            'disease' => $className
                ? Disease::all()->first(
                    fn (Disease $d) => Str::slug($d->name, '_') === Str::slug($className, '_')
                )
                : null,
            'confidence' => ((float) ($prediction['confidence'] ?? 0)) / 100,
            'raw' => $data ?? [],
        ];
    }

    private function wake(): void
    {
        try {
            Http::timeout(config('services.vit.wake_timeout'))
                ->get(config('services.vit.url').'/health')
                ->throw();
        } catch (Throwable) {
        }
    }
}
