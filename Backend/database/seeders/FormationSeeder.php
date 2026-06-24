<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Formation;

class FormationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $formations = [
            ['code' => 'DEV-01', 'titre' => 'Développement Web Fullstack'],
            ['code' => 'DEV', 'titre' => 'Développement WEB'],
            ['code' => 'M1', 'titre' => 'Module 1'],
            ['code' => 'Rbt', 'titre' => 'BootCamp (Robotique)'],
            ['code' => 'BA', 'titre' => 'Bureautique Avancée'],
            ['code' => 'DAARA', 'titre' => 'Enseignement Daara'],
            ['code' => 'DOC', 'titre' => 'Doctorat'],
        ];

        foreach ($formations as $formation) {
            Formation::updateOrCreate(['code' => $formation['code']], $formation);
        }
    }
}
