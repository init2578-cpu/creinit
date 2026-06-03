<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\GeofencingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWithinPremises
{
    public function __construct(
        private readonly GeofencingService $geofencing,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $latitude  = (float) $request->input('latitude');
        $longitude = (float) $request->input('longitude');

        if (!$latitude || !$longitude) {
            return $this->denyAccess($request, 'Coordonnées GPS requises.');
        }

        // Read radius from DB settings (set by admin), fall back to env config
        $maxRadius = (float) (\App\Models\Setting::getValue('cre_radius') ?: config('geofencing.max_radius', 50));

        if (!$this->geofencing->isWithinPremises($latitude, $longitude, $maxRadius)) {
            $distance = round($this->geofencing->distanceFromCre($latitude, $longitude), 1);

            return $this->denyAccess(
                $request,
                "Action refusée : Vous devez être présent au CRE. (Distance détectée : {$distance}m, max autorisé : {$maxRadius}m)",
            );
        }

        return $next($request);
    }

    /**
     * Return a response compatible with Inertia or JSON.
     */
    private function denyAccess(Request $request, string $message): Response|\Illuminate\Http\RedirectResponse
    {
        if ($request->header('X-Inertia')) {
            return redirect()
                ->back()
                ->with('error', $message);
        }
        
        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
            ], Response::HTTP_FORBIDDEN);
        }

        abort(Response::HTTP_FORBIDDEN, $message);
    }
}
