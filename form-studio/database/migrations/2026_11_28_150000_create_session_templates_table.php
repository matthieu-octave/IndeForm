<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // workspace_id NULL = modèle fourni par Indé'Form, visible (en lecture seule) par tous les comptes.
        Schema::create('session_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('steps');
            $table->timestamps();
        });

        $now = now();
        foreach ($this->providedTemplates() as $template) {
            DB::table('session_templates')->insert([
                'workspace_id' => null,
                'name' => $template['name'],
                'description' => $template['description'],
                'steps' => json_encode(array_map(
                    fn ($s) => ['title' => $s[0], 'minutes' => $s[1], 'instructions' => $s[2] ?? null],
                    $template['steps'],
                ), JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('session_templates');
    }

    private function providedTemplates(): array
    {
        return [
            [
                'name' => 'Cours de 2 h',
                'description' => 'Apport, démonstration, exercice et correction : le format classique d’une séance courte.',
                'steps' => [
                    ['Accueil et objectifs de la séance', 10, 'Rappel de la séance précédente, annonce des objectifs.'],
                    ['Apport théorique', 35],
                    ['Démonstration', 20, 'Montrer pas à pas, en verbalisant.'],
                    ['Exercice guidé', 35, 'Les apprenants pratiquent, le formateur circule.'],
                    ['Correction collective', 15],
                    ['Synthèse et questions', 5],
                ],
            ],
            [
                'name' => 'Journée complète (7 h)',
                'description' => 'Matinée et après-midi avec pauses, hors pause déjeuner.',
                'steps' => [
                    ['Accueil, tour de table et objectifs', 20],
                    ['Apport 1', 60],
                    ['Pause', 15],
                    ['Atelier pratique', 70],
                    ['Mise en commun', 15],
                    ['Réactivation (début d’après-midi)', 15, 'Quiz ou questions rapides sur la matinée.'],
                    ['Apport 2', 45],
                    ['Pause', 15],
                    ['TP', 120],
                    ['Correction du TP', 30],
                    ['Bilan de la journée', 15, 'Tour de table, évaluation à chaud.'],
                ],
            ],
            [
                'name' => 'TP guidé (3 h)',
                'description' => 'Une séance de pratique encadrée, du guidage vers l’autonomie.',
                'steps' => [
                    ['Présentation du TP et des attendus', 15],
                    ['Mise en place de l’environnement', 20],
                    ['Étape 1 guidée', 45, 'Réalisée ensemble, pas à pas.'],
                    ['Pause', 10],
                    ['Étape 2 en autonomie', 60],
                    ['Correction', 20],
                    ['Bilan', 10],
                ],
            ],
            [
                'name' => 'Évaluation (2 h)',
                'description' => 'Épreuve encadrée avec consignes, temps de composition et retour.',
                'steps' => [
                    ['Consignes et modalités', 10, 'Durée, documents autorisés, critères d’évaluation.'],
                    ['Épreuve', 90],
                    ['Correction ou retour à chaud', 15],
                    ['Bilan', 5],
                ],
            ],
        ];
    }
};
