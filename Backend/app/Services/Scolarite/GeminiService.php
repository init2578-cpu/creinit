<?php

namespace App\Services\Scolarite;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model = 'gemini-1.5-flash-8b'; 
    protected int $timeout = 30; // Reduced timeout for better UX

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
                $decoded = json_decode($content, true);
                
                if (json_last_error() === JSON_ERROR_NONE && !empty($decoded)) {
                    return $decoded;
                }
                
                Log::warning('GeminiService: Invalid JSON received from model.');
            }

            Log::error('GeminiService API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('GeminiService Exception: ' . $e->getMessage());
        }

        return [];
    }

    public function chatWithAssane(string $message, array $history = [], bool $isPublic = false): string
    {
        if (empty($this->apiKey)) {
            return "Désolé, je ne peux pas discuter pour le moment car ma configuration AI est incomplète.";
        }

        if ($isPublic) {
            $systemInstruction = "Tu es ASSANE (Agent de Soutien Système et d'Assistance Numérique aux Élèves), l'assistant IA de la plateforme e-CRE de Kolda, Sénégal.
            
            TON IDENTITÉ PUBLIQUE :
            - Tu es l'ambassadeur de l'E-CRE pour les visiteurs et futurs apprenants.
            - Ton est Chaleureux, professionnel et accueillant.
            
            TES LIMITES :
            - Tu dois répondre EXCLUSIVEMENT aux questions concernant :
                1. Les formations proposées par le CRE (IA, Développement, Robotique, etc.).
                2. Comment s'inscrire ou candidater à une formation.
                3. Les dernières actualités et activités du CRE (événements, hackathons, ateliers).
                4. Des informations générales sur la mission du CRE.
            - Si on te demande autre chose (gestion administrative, notes, logs techniques, etc.), décline poliment en expliquant que tu es là pour guider les futurs apprenants et suggère de contacter l'administration.
            - Réponds toujours en Français.";
        } else {
            $systemInstruction = "Tu es ASSANE (Agent de Soutien Système et d'Assistance Numérique aux Élèves), l'assistant IA premium de la plateforme e-CRE de Kolda, Sénégal.
            
            TON IDENTITÉ ADMINISTRATIVE :
            - Expert de la plateforme e-CRE (Scolarité, Logistique, Examens).
            - Ambassadeur de la mission des CRE.
            - Ton : Chaleureux, professionnel, africain (Sénégalais), et extrêmement serviable.
            
            TES CAPACITÉS :
            1. Aider les APPRENANTS à naviguer dans leurs cours, exercices et examens.
            2. Assister les FORMATEURS dans la gestion des groupes et des notes.
            3. Aider la DIRECTION à interpréter les KPI du tableau de bord.
            
            CONSIGNES :
            - Sois concis mais complet.
            - Si tu ne sais pas une information spécifique, suggère de contacter l'administration.
            - Réponds toujours en Français.";
        }

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

            Log::error('Assane Chat API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Assane Chat Exception: ' . $e->getMessage());
        }

        return "Oups ! J'ai eu un petit problème technique. Peux-tu reformuler ta question ?";
    }

    private function buildPrompt(array $data): string
    {
        $metrics = json_encode($data['metrics'], JSON_PRETTY_PRINT);
        $period = $data['period']['label'];

        return <<<PROMPT
Tu es un expert en audit stratégique et gestion de centres de formation (Centre de Recherche et d'Essais - CRE au Sénégal). 
Voici les statistiques d'activité pour la période $period :
$metrics

Rédige un rapport stratégique d'expert en français comprenant :
1. Une section "Analyse de Performance" (Interprétation lucide des taux d'assiduité, de réussite et de parité).
2. Une section "Diagnostic Matériel" (Analyse de la santé du parc informatique et son impact sur la formation).
3. Une section "Projections" (Tendances basées sur les données actuelles).
4. Une section "Recommandations Stratégiques" (Actions concrètes adaptées au contexte socio-économique de Kolda et alignées sur la mission des CRE qui est de 'démocratiser l'accès à la science et à la technologie').

Règles de rédaction :
- Ton institutionnel et extrêmement professionnel.
- Focus sur l'impact social et l'équité de genre.
- Langage orienté vers la prise de décision pour le Directeur.

Réponds EXCLUSIVEMENT au format JSON strict suivant :
{
  "analysis": ["constat stratégique 1", "constat stratégique 2", ...],
  "projections": ["projection 1", ...],
  "recommendations": ["recommandation concrète 1", ...]
}
PROMPT;
    }
}
