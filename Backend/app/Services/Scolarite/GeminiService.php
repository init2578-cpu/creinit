<?php

namespace App\Services\Scolarite;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model = 'gemini-2.5-flash-lite'; 
    protected int $timeout = 60; // Increased timeout for heavier models

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
    }

    public function generateActivityAnalysis(array $data): array
    {
        if (empty($this->apiKey)) {
            return [];
        }

        $prompt = $this->buildPrompt($data);

        try {
            $response = Http::timeout($this->timeout)->withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $this->apiKey,
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                return json_decode($content, true) ?: [];
            }

            Log::error('GeminiService API Error (' . $this->model . '): ' . $response->body());
        } catch (\Exception $e) {
            Log::error('GeminiService Exception (' . $this->model . '): ' . $e->getMessage());
        }

        return [];
    }

    public function chatWithAssane(string $message, array $history = []): string
    {
        if (empty($this->apiKey)) {
            return "Désolé, je ne peux pas discuter pour le moment car ma configuration AI est incomplète.";
        }

        $systemInstruction = "Tu es ASSANE (Agent de Soutien Système et d'Assistance Numérique aux Élèves), l'assistant IA premium de la plateforme e-CRE de Kolda, Sénégal.
        
        TON IDENTITÉ :
        - Expert de la plateforme e-CRE (Scolarité, Logistique, Examens).
        - Ambassadeur de la mission des CRE (Centre de Recherche et d'Essais) : démocratiser la science et la technologie.
        - Ton : Chaleureux, professionnel, africain (Sénégalais), et extrêmement serviable.
        
        TES CAPACITÉS :
        1. Aider les APPRENANTS à naviguer dans leurs cours, exercices et examens.
        2. Assister les FORMATEURS dans la gestion des groupes et des notes.
        3. Aider la DIRECTION à interpréter les KPI du tableau de bord.
        
        CONSIGNES :
        - Sois concis mais complet.
        - Utilise des emojis avec parcimonie pour rester professionnel.
        - Si tu ne sais pas une information spécifique à un utilisateur réel, suggère de contacter l'administration du CRE de Kolda.
        - Réponds toujours en Français.";

        $contents = [];
        
        foreach ($history as $msg) {
            $contents[] = [
                'role' => $msg['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $msg['content']]]
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];

        try {
            $response = Http::timeout($this->timeout)->withHeaders([
                'Content-Type' => 'application/json',
                'X-goog-api-key' => $this->apiKey,
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", [
                'system_instruction' => [
                    'parts' => [['text' => $systemInstruction]]
                ],
                'contents' => $contents,
            ]);

            if ($response->successful()) {
                return $response->json('candidates.0.content.parts.0.text') ?? "Je n'ai pas pu générer de réponse.";
            }

            Log::error('Assane Chat API Error (' . $this->model . '): ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Assane Chat Exception (' . $this->model . '): ' . $e->getMessage());
        }

        return "Oups ! J'ai eu un petit problème technique (Quota ou Modèle). Peux-tu reformuler ta question ou réessayer dans une minute ?";
    }

    private function buildPrompt(array $data): string
    {
        $metrics = json_encode($data['metrics'], JSON_PRETTY_PRINT);
        $period = $data['period']['label'];

        return <<<PROMPT
Tu es un expert en audit stratégique et gestion de centres de formation (Centre de Recherche et d'Essais - CRE au Sénégal). 
Voici les statistiques d'activité pour la période $period :
$metrics

Rédige un rapport professionnel en français comprenant :
1. Une section "Analyse et Constats" (points forts et points faibles basés sur les chiffres).
2. Une section "Perspectives" (projections basées sur les tendances actuelles).
3. Une section "Recommandations" (actions stratégiques concrètes à mener).

Règles de rédaction :
- Ton institutionnel, précis et expert.
- Analyse lucide des taux d'assiduité, de parité et de matériel opérationnel.
- Recommandations opérationnelles adaptées au contexte local (Sénégal/Kolda).

Réponds EXCLUSIVEMENT au format JSON suivant :
{
  "analysis": ["constat 1", "constat 2", ...],
  "projections": ["projection 1", ...],
  "recommendations": ["recommandation 1", ...]
}
PROMPT;
    }
}
