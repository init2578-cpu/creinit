<?php

namespace App\Console\Commands;

use App\Models\Leave;
use App\Models\User;
use App\Notifications\LeaveEndingSoonUserNotification;
use App\Notifications\LeaveEndingSoonDirectorNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendLeaveEndingSoonNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaves:notify-ending-soon';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie une notification aux agents et directeurs 72h avant la fin d\'un congé.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = Carbon::today()->addDays(3)->format('Y-m-d');

        $leaves = Leave::with('user')
            ->where('status', 'approuve')
            ->whereDate('date_fin', $targetDate)
            ->get();

        if ($leaves->isEmpty()) {
            $this->info("Aucun congé ne se termine le {$targetDate}.");
            return;
        }

        $directors = User::role('Directeur')->get();
        $count = 0;

        foreach ($leaves as $leave) {
            // Notify the user
            if ($leave->user) {
                $leave->user->notify(new LeaveEndingSoonUserNotification($leave));
            }

            // Notify all directors
            foreach ($directors as $director) {
                $director->notify(new LeaveEndingSoonDirectorNotification($leave));
            }
            $count++;
        }

        $this->info("{$count} notifications de fin de congé envoyées pour la date {$targetDate}.");
    }
}
