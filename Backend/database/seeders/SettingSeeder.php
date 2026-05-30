<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // ÉMARGEMENT & GÉOLOCALISATION
            ['key' => 'cre_latitude', 'value' => '12.8897', 'group' => 'attendance', 'type' => 'string'],
            ['key' => 'cre_longitude', 'value' => '-14.9482', 'group' => 'attendance', 'type' => 'string'],
            ['key' => 'cre_radius', 'value' => '20', 'group' => 'attendance', 'type' => 'integer'],
            ['key' => 'attendance_buffer_before', 'value' => '10', 'group' => 'attendance', 'type' => 'integer'],
            ['key' => 'attendance_buffer_after', 'value' => '15', 'group' => 'attendance', 'type' => 'integer'],

            // VITRINE & GÉNÉRAL
            ['key' => 'site_name', 'value' => 'E-CRE Kolda', 'group' => 'general', 'type' => 'string'],
            ['key' => 'site_description', 'value' => 'Plateforme de gestion du Centre de Recherche et d\'Essais de Kolda', 'group' => 'general', 'type' => 'string'],
            ['key' => 'contact_email', 'value' => 'contact@cre-kolda.sn', 'group' => 'general', 'type' => 'string'],
            ['key' => 'enable_registration', 'value' => '1', 'group' => 'general', 'type' => 'boolean'],

            // NOTIFICATIONS
            ['key' => 'notify_new_application', 'value' => '1', 'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_exercise_graded', 'value' => '1', 'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_exam_result', 'value' => '1', 'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_certificate_issued', 'value' => '1', 'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_new_exercise', 'value' => '1', 'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_new_exam', 'value' => '1', 'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_chapter_submitted', 'value' => '1', 'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_absence_student', 'value' => '0', 'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'notify_schedule_change', 'value' => '1', 'group' => 'notifications', 'type' => 'boolean'],
            ['key' => 'email_sender_name', 'value' => 'E-CRE Kolda', 'group' => 'notifications', 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
