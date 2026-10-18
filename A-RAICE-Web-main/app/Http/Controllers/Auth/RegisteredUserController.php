<?php

namespace App\Http\Controllers\Auth;

use App\Enums\MunicipalityEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'municipalities' => array_column(MunicipalityEnum::cases(), 'value'),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'barangay' => 'required|string|max:255',
            'municipality' => ['required', Rule::enum(MunicipalityEnum::class)],
            'contact_number' => 'required|string|max:20',
            'farm_lat' => 'required|numeric|between:14.3,14.95',
            'farm_long' => 'required|numeric|between:120.2,120.7',
        ],
            [
                'farm_lat.between' => 'Latitude must be within Bataan (between 14.3 and 14.95).',
                'farm_long.between' => 'Longitude must be within Bataan (between 120.2 and 120.7).',
            ]);

        if (! $this->insideMunicipality(
            $validated['municipality'],
            (float) $validated['farm_lat'],
            (float) $validated['farm_long'],
        )) {
            throw ValidationException::withMessages([
                'farm_lat' => 'These coordinates are not inside the selected municipality.',
            ]);
        }

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            if (! config('auth.require_email_verification')) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->farmerProfile()->create([
                'full_name' => $validated['name'],
                'barangay' => $validated['barangay'],
                'municipality' => $validated['municipality'],
                'contact_number' => $validated['contact_number'],
                'farm_lat' => $validated['farm_lat'],
                'farm_long' => $validated['farm_long'],
            ]);

            return $user;
        });
        if (config('auth.require_email_verification')) {
            event(new Registered($user));
        }

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function insideMunicipality(string $slug, float $lat, float $lng): bool
    {
        $json = file_get_contents(public_path('geo/bataan-municipalities.geojson'));

        if ($json === false) {
            return false;
        }

        /** @var array{features: list<array{properties: array<string, mixed>, geometry: array{type: string, coordinates: array<mixed>}}>} $collection */
        $collection = json_decode($json, true);

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
