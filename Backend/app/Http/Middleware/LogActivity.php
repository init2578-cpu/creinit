<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Automatically log all mutating HTTP requests into audit_logs.
 */
class LogActivity
{
    private array $skipPrefixes = [
        'api/',
        '_ignition',
        'telescope',
        'horizon',
        'audit',    // Don't log audit page visits
    ];

    private array $mutatingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Maps URL segment → [human label, model class]
     */
    private array $resourceMap = [
        'attendances'    => ['Émargement',          \App\Models\Attendance::class],
        'attendance'     => ['Émargement',          \App\Models\Attendance::class],
        'users'          => ['Utilisateur',         \App\Models\User::class],
        'groups'         => ['Groupe',              \App\Models\Group::class],
        'nominations'    => ['Nomination',          \App\Models\Nomination::class],
        'modules'        => ['Module',              \App\Models\Module::class],
        'students'       => ['Apprenant',           \App\Models\User::class],
        'trainees'       => ['Stagiaire',           \App\Models\User::class],
        'assets'         => ['Équipement',          \App\Models\Asset::class],
        'loans'          => ['Prêt',                \App\Models\Loan::class],
        'leaves'         => ['Congé',               \App\Models\Leave::class],
        'exams'          => ['Examen',              \App\Models\Exam::class],
        'exercises'      => ['Exercice',            \App\Models\Chapter::class],
        'schedules'      => ['Emploi du temps',     \App\Models\Schedule::class],
        'certificates'   => ['Attestation',         \App\Models\Certificate::class],
        'applications'   => ['Candidature',         \App\Models\Application::class],
        'community'      => ['Annonce',             \App\Models\Announcement::class],
        'chapter-progress' => ['Progression',       null],
        'rooms'          => ['Salle',               null],
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

        $event = $this->resolveEvent($method, $path);

        [$resourceLabel, $modelClass, $resourceId] = $this->resolveResource($path);

        $description = $this->buildDescription($event, $user?->name ?? 'Système', $resourceLabel);

        AuditLog::write(
            event: $event,
            description: $description,
            userId: $user?->id,
            auditableType: $modelClass,
            auditableId: $resourceId,
        );
    }

    private function resolveEvent(string $method, string $path): string
    {
        if ($path === 'login')  return 'login';
        if ($path === 'logout') return 'logout';

        return match ($method) {
            'post'          => 'created',
            'put', 'patch'  => 'updated',
            'delete'        => 'deleted',
            default         => 'action',
        };
    }

    /**
     * Parse URL segments to extract:
     *   - Human-readable resource label
     *   - Model class (FQCN or null)
     *   - Resource ID (integer if present in URL, null otherwise)
     *
     * Examples:
     *   /students           → ['Apprenant', User::class, null]
     *   /groups/3           → ['Groupe',    Group::class, 3]
     *   /groups/3/chapter-progress → ['Groupe', Group::class, 3]
     *
     * @return array{string, string|null, int|null}
     */
    private function resolveResource(string $path): array
    {
        $segments = array_values(array_filter(explode('/', $path)));

        $label     = 'Ressource';
        $modelClass = null;
        $resourceId = null;

        foreach ($segments as $i => $segment) {
            if (isset($this->resourceMap[$segment])) {
                [$label, $modelClass] = $this->resourceMap[$segment];
                // Check if the NEXT segment is a numeric ID
                if (isset($segments[$i + 1]) && is_numeric($segments[$i + 1])) {
                    $resourceId = (int)$segments[$i + 1];
                }
                break;
            }

            // If the segment is numeric and follows an unmapped segment, look backwards
            if (is_numeric($segment) && $i > 0 && isset($this->resourceMap[$segments[$i - 1]])) {
                $resourceId = (int)$segment;
            }
        }

        return [$label, $modelClass, $resourceId];
    }

    private function buildDescription(string $event, string $userName, string $resourceLabel): string
    {
        $article = match (true) {
            in_array(mb_strtolower(mb_substr($resourceLabel, 0, 1)), ['é','é','a','i','o','u','é','è','ê']) => "un(e) {$resourceLabel}",
            default => "un(e) {$resourceLabel}",
        };

        // Use direct label
        $r = $resourceLabel;

        return match ($event) {
            'login'   => "{$userName} s'est connecté(e)",
            'logout'  => "{$userName} s'est déconnecté(e)",
            'created' => "{$userName} a créé : {$r}",
            'updated' => "{$userName} a modifié : {$r}",
            'deleted' => "{$userName} a supprimé : {$r}",
            default   => "{$userName} — {$event} sur {$r}",
        };
    }
}
