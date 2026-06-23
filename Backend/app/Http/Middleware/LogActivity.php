<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatically log all mutating HTTP requests into audit_logs.
 * Attach to the web middleware group in bootstrap/app.php or specific routes.
 */
class LogActivity
{
    // Routes to skip (they create their own detailed logs via Auditable trait)
    private array $skipPrefixes = [
        'api/',
        '_ignition',
        'telescope',
        'horizon',
    ];

    private array $mutatingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    // Human-readable event labels per HTTP method + URL pattern
    private array $eventMap = [
        'login'      => ['post:login'],
        'logout'     => ['post:logout'],
        'created'    => ['post:*'],
        'updated'    => ['put:*', 'patch:*'],
        'deleted'    => ['delete:*'],
        'exported'   => ['get:*export*', 'get:*download*'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log for authenticated users and mutating requests
        if (!$request->user() || !in_array($request->method(), $this->mutatingMethods)) {
            return $response;
        }

        // Skip internal framework routes
        foreach ($this->skipPrefixes as $prefix) {
            if (str_starts_with(ltrim($request->path(), '/'), $prefix)) {
                return $response;
            }
        }

        // Only log successful responses (2xx and 3xx)
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $this->writeLog($request);

        return $response;
    }

    private function writeLog(Request $request): void
    {
        $method = strtolower($request->method());
        $path   = $request->path();
        $user   = $request->user();

        // Determine event type
        $event = $this->resolveEvent($method, $path);

        // Build a human-readable description
        $description = $this->buildDescription($event, $user?->name ?? 'Système', $path);

        AuditLog::write(
            event: $event,
            description: $description,
            userId: $user?->id,
        );
    }

    private function resolveEvent(string $method, string $path): string
    {
        if ($path === 'login')  return 'login';
        if ($path === 'logout') return 'logout';

        return match ($method) {
            'post'   => 'created',
            'put',
            'patch'  => 'updated',
            'delete' => 'deleted',
            default  => 'action',
        };
    }

    private function buildDescription(string $event, string $userName, string $path): string
    {
        $parts = array_filter(explode('/', $path));
        $resource = match (true) {
            in_array('attendances', $parts) || in_array('attendance', $parts) => 'un émargement',
            in_array('users', $parts)       => 'un utilisateur',
            in_array('groups', $parts)      => 'un groupe',
            in_array('nominations', $parts) => 'une nomination',
            in_array('modules', $parts)     => 'un module',
            in_array('students', $parts)    => 'un apprenant',
            in_array('trainees', $parts)    => 'un stagiaire',
            in_array('assets', $parts)      => 'un équipement',
            in_array('loans', $parts)       => 'un prêt',
            in_array('leaves', $parts)      => 'un congé',
            in_array('exams', $parts)       => 'un examen',
            in_array('exercises', $parts)   => 'un exercice',
            in_array('schedules', $parts)   => 'un emploi du temps',
            in_array('certificates', $parts)=> 'une attestation',
            in_array('applications', $parts)=> 'une candidature',
            default => 'une ressource',
        };

        return match ($event) {
            'login'   => "{$userName} s'est connecté(e)",
            'logout'  => "{$userName} s'est déconnecté(e)",
            'created' => "{$userName} a créé {$resource}",
            'updated' => "{$userName} a modifié {$resource}",
            'deleted' => "{$userName} a supprimé {$resource}",
            default   => "{$userName} — action sur /{$path}",
        };
    }
}
