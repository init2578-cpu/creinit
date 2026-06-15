<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\ChapterProgressController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DirectorDashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\NominationController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ExerciseController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\Scolarite\AdminExamController;
use App\Http\Controllers\Scolarite\AdminExerciseController;
use App\Http\Controllers\Scolarite\ReportController;
use App\Http\Middleware\EnsureWithinPremises;
use Illuminate\Support\Facades\Route;

use Inertia\Inertia;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

Route::get('/', [\App\Http\Controllers\ApplicationController::class, 'welcome']);

Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

Route::post('login', [AuthenticatedSessionController::class, 'store']);

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    ->name('password.request');

Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    ->name('password.email');

Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    ->name('password.reset');

Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->name('password.update');

Route::middleware('auth')->group(function () {
    Route::get('change-password', [AuthenticatedSessionController::class, 'changePassword'])
        ->name('password.change');
    Route::post('change-password', [AuthenticatedSessionController::class, 'updatePassword'])
        ->name('password.change.update');
});

// -----------------------------------------------------------------------
// Public Routes
// -----------------------------------------------------------------------
Route::get('/apply', [\App\Http\Controllers\ApplicationController::class, 'create'])
    ->name('applications.create');
Route::post('/apply', [\App\Http\Controllers\ApplicationController::class, 'store'])
    ->name('applications.store');

Route::get('/vision', function () {
    return Inertia::render('Vision');
})->name('vision');

Route::get('/actualites', [\App\Http\Controllers\PublicPostController::class, 'index'])->name('public.posts.index');
Route::get('/actualites/{slug}', [\App\Http\Controllers\PublicPostController::class, 'show'])->name('public.posts.show');

Route::get('/partenaires', [\App\Http\Controllers\EcosystemController::class, 'publicPartenaires'])->name('public.partenaires');

Route::get('/curriculum', function () {
    return Inertia::render('Curriculum');
})->name('curriculum');

Route::get('/plateforme', function () {
    return Inertia::render('Plateforme');
})->name('plateforme');

Route::get('/verify-certificate/{uuid}', [CertificateVerificationController::class, 'verify'])
    ->name('certificates.verify');

Route::get('/certificates/v/{uuid}', [CertificateVerificationController::class, 'show'])
    ->name('certificates.view');
Route::get('/certificates/{certificate}/download', [CertificateVerificationController::class, 'download'])
    ->name('certificates.download');

Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// -----------------------------------------------------------------------
// Authenticated Routes
// -----------------------------------------------------------------------
Route::middleware(['auth'])->group(function (): void {
    // Student LMS (PROMPT 9)
    // -----------------------------------------------------------------------
    Route::group(['prefix' => 'student', 'as' => 'student.'], function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/courses', [CourseController::class, 'index'])->name('courses');
        Route::get('/courses/{module}/{chapter}', [CourseController::class, 'showChapter'])->name('courses.show');

        // Exercises
        Route::get('/exercises', [ExerciseController::class, 'index'])->name('exercises.index');
        Route::get('/exercises/{chapter}/start', [ExerciseController::class, 'showOnline'])->name('exercises.start');
        Route::post('/exercises/{chapter}/submit', [ExerciseController::class, 'submit'])->name('exercises.submit');
        Route::get('/exercises/{submission}/result', [ExerciseController::class, 'showResult'])->name('exercises.result');
        Route::get('/exercises/{submission}/download', [ExerciseController::class, 'download'])->name('exercises.download');

        // Exams
        Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
        Route::post('/exams/{exam}/start', [ExamController::class, 'start'])->name('exams.start');
        Route::post('/exams/{exam}/submit', [ExamController::class, 'submit'])->name('exams.submit');
        
        Route::middleware(\App\Http\Middleware\EnsureWithinPremises::class)->group(function () {
            Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
            Route::get('/exams/{exam}/download', [ExamController::class, 'download'])->name('exams.download');
        });
    });

    // Profile Management
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])
        ->name('profile.update');
    Route::get('/profile/settings', [\App\Http\Controllers\ProfileController::class, 'settings'])
        ->name('profile.settings');

    // Notifications
    Route::post('/notifications/mark-all-as-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-as-read');



    // -----------------------------------------------------------------------
    // Staff & Management (Directeur & Secrétaire)
    // -----------------------------------------------------------------------
    Route::middleware(['role:Directeur|Secrétaire'])->group(function () {
        // Director Dashboard
        Route::get('/dashboard/director', [DirectorDashboardController::class, 'index'])
            ->name('dashboard.director');
        Route::get('/dashboard/director/export-pdf', [DirectorDashboardController::class, 'exportPdf'])
            ->name('dashboard.director.export-pdf');

        // Actualités (Posts)
        Route::resource('admin/posts', \App\Http\Controllers\Admin\PostController::class)->names([
            'index' => 'admin.posts.index',
            'create' => 'admin.posts.create',
            'store' => 'admin.posts.store',
            'edit' => 'admin.posts.edit',
            'update' => 'admin.posts.update',
            'destroy' => 'admin.posts.destroy',
        ]);

        // Contact Messages Management
        Route::get('/contact-messages', [ContactController::class, 'adminIndex'])
            ->name('contact-messages.index');
        Route::delete('/contact-messages/{contactMessage}', [ContactController::class, 'adminDestroy'])
            ->name('contact-messages.destroy');
        Route::patch('/contact-messages/{contactMessage}/read', [ContactController::class, 'markAsRead'])
            ->name('contact-messages.read');

        // Ecosystem & CRM
        Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])
            ->name('users.index');
        Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])
            ->name('users.store');
        Route::put('/users/{user}', [\App\Http\Controllers\UserController::class, 'update'])
            ->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])
            ->name('users.destroy');

        // Advanced Stats
        Route::get('/stats/reports', [\App\Http\Controllers\StatsController::class, 'index'])
            ->name('stats.index');

        // Admissions (Scolarité)
        Route::get('/applications', [\App\Http\Controllers\ApplicationController::class, 'index'])
            ->name('applications.index');
        Route::get('/applications/preview/{application}/{type}', [\App\Http\Controllers\ApplicationController::class, 'previewFile'])
            ->name('applications.preview');
        Route::patch('/applications/{application}/status', [\App\Http\Controllers\ApplicationController::class, 'updateStatus'])
            ->name('applications.status.update');
        Route::post('/applications/enroll-manual', [\App\Http\Controllers\ApplicationController::class, 'enrollManual'])
            ->name('applications.enroll.manual');
        Route::put('/applications/{application}', [\App\Http\Controllers\ApplicationController::class, 'update'])
            ->name('applications.update');

        // Group Management
        Route::post('/groups', [\App\Http\Controllers\Scolarite\GroupController::class, 'store'])
            ->name('groups.store');
        Route::put('/groups/{group}', [\App\Http\Controllers\Scolarite\GroupController::class, 'update'])
            ->name('groups.update');
        Route::delete('/groups/{group}', [\App\Http\Controllers\Scolarite\GroupController::class, 'destroy'])
            ->name('groups.destroy');
        Route::patch('/groups/{group}/close', [\App\Http\Controllers\Scolarite\GroupController::class, 'close'])
            ->name('groups.close');
        Route::patch('/groups/{group}/reopen', [\App\Http\Controllers\Scolarite\GroupController::class, 'reopen'])
            ->name('groups.reopen');

        // Group Student Management
        Route::post('/groups/{group}/students', [\App\Http\Controllers\Scolarite\GroupStudentController::class, 'store'])
            ->name('groups.students.store');
        Route::delete('/groups/{group}/students/{student}', [\App\Http\Controllers\Scolarite\GroupStudentController::class, 'destroy'])
            ->name('groups.students.destroy');

        // Room Management (Directeur only for modification)
        Route::middleware(['role:Directeur'])->group(function () {
            Route::post('/rooms', [\App\Http\Controllers\Scolarite\RoomController::class, 'store'])
                ->name('rooms.store');
            Route::put('/rooms/{room}', [\App\Http\Controllers\Scolarite\RoomController::class, 'update'])
                ->name('rooms.update');
            Route::delete('/rooms/{room}', [\App\Http\Controllers\Scolarite\RoomController::class, 'destroy'])
                ->name('rooms.destroy');
        });

        // Curriculum Management
        Route::resource('modules', \App\Http\Controllers\ModuleController::class)->except(['index', 'show']);
        Route::post('/modules/{module}/chapters', [\App\Http\Controllers\ModuleController::class, 'storeChapter'])
            ->name('modules.chapters.store');
        Route::post('/modules/{module}/chapters/reorder', [\App\Http\Controllers\ModuleController::class, 'reorderChapters'])
            ->name('modules.chapters.reorder');
        Route::post('/chapters/{chapter}/update', [\App\Http\Controllers\ModuleController::class, 'updateChapter'])
            ->name('modules.chapters.update');
        Route::delete('/chapters/{chapter}', [\App\Http\Controllers\ModuleController::class, 'destroyChapter'])
            ->name('modules.chapters.destroy');
        Route::delete('/chapters/{chapter}/attachments/{index}', [\App\Http\Controllers\ModuleController::class, 'destroyAttachment'])
            ->name('modules.chapters.attachments.destroy');
        Route::get('/chapters/{chapter}/attachments/{index}', [\App\Http\Controllers\ModuleController::class, 'downloadAttachment'])
            ->name('modules.chapters.attachments.download');

        // Student & Trainee Management
        Route::resource('students', \App\Http\Controllers\StudentsController::class)->except(['index']);
        Route::resource('trainees', \App\Http\Controllers\TraineesController::class)->except(['index']);

        // Settings (Directeur only — system configuration)
        Route::middleware(['role:Directeur'])->group(function () {
            Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])
                ->name('settings.index');
            Route::post('/settings', [\App\Http\Controllers\SettingController::class, 'update'])
                ->name('settings.update');
            Route::post('/settings/initialize', [\App\Http\Controllers\SettingController::class, 'initialize'])
                ->name('settings.initialize');
        });

        // Nominations Approval
        Route::patch('/nominations/{nomination}/approve', [NominationController::class, 'approve'])
            ->name('nominations.approve');

        // Admin Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    });

    // -----------------------------------------------------------------------
    // Staff & Trainers (Directeur, Secrétaire & Formateur)
    // -----------------------------------------------------------------------
    Route::middleware(['trainer_or_staff'])->group(function () {
        // Attendance Management
        Route::get('/attendances/groups', [AttendanceController::class, 'trainerGroups'])->name('attendances.trainer-groups');
        Route::get('/attendances/take/{group}', [AttendanceController::class, 'takeAttendance'])->name('attendances.take');
        Route::post('/attendances/store-batch', [AttendanceController::class, 'storeBatch'])
            ->middleware(EnsureWithinPremises::class)->name('attendances.store-batch');
        Route::post('/attendances/individual', [AttendanceController::class, 'store'])
            ->middleware(EnsureWithinPremises::class)->name('attendances.store');
        Route::get('/attendance', [\App\Http\Controllers\Scolarite\AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/history/{schedule}', [\App\Http\Controllers\Scolarite\AttendanceController::class, 'history'])->name('attendance.history');
        Route::get('/attendance/{schedule}/{date}', [\App\Http\Controllers\Scolarite\AttendanceController::class, 'take'])->name('attendance.take');
        Route::post('/attendance', [\App\Http\Controllers\Scolarite\AttendanceController::class, 'store'])
            ->middleware(EnsureWithinPremises::class)->name('attendance.store');



        // Trainer Resources
        Route::get('/trainer/groups', [App\Http\Controllers\TrainerGroupsController::class, 'index'])->name('trainer.groups');
        Route::post('/trainer/exercises/{submission}/grade', [ExerciseController::class, 'grade'])->name('trainer.exercises.grade');

        // Admin Exam/Exercise Management (Scolarité)
        Route::group(['prefix' => 'admin-scolarite'], function () {
            Route::resource('exams', AdminExamController::class);
            Route::patch('exams/{exam}/approve', [AdminExamController::class, 'approve'])->name('exams.approve');
            Route::get('exams/{exam}/results', [AdminExamController::class, 'getResults'])->name('exams.results');
            Route::post('exams/{exam}/grades', [AdminExamController::class, 'enterGrades'])->name('exams.enter-grades');
            Route::post('exams/{exam}/unlock/{user}', [AdminExamController::class, 'unlock'])->name('exams.unlock');
            Route::get('exercises', [AdminExerciseController::class, 'index'])->name('exercises.index');
            Route::post('exercises', [AdminExerciseController::class, 'store'])->name('exercises.store');
            Route::put('exercises/{chapter}', [AdminExerciseController::class, 'update'])->name('exercises.update');
            Route::delete('exercises/{chapter}', [AdminExerciseController::class, 'destroy'])->name('exercises.destroy');
            Route::post('exercises/{chapter}/attachments', [AdminExerciseController::class, 'updateAttachment'])->name('exercises.attachments.store');
            Route::delete('exercises/{chapter}/attachments/{index}', [AdminExerciseController::class, 'deleteAttachment'])->name('exercises.attachments.destroy');
            Route::post('exercises/{submission}/grade', [AdminExerciseController::class, 'gradeSubmission'])->name('exercises.grade-submission');
            Route::post('exercises/{chapter}/questions', [AdminExerciseController::class, 'storeQuestion'])->name('exercises.questions.store');
            Route::post('exams/{exam}/questions', [AdminExamController::class, 'storeQuestion'])->name('exams.questions.store');
            Route::post('exams/{exam}/duplicate', [AdminExamController::class, 'duplicate'])->name('exams.duplicate');
            Route::patch('questions/{question}', [AdminExerciseController::class, 'updateQuestion'])->name('questions.update');
            Route::delete('questions/{question}', [AdminExerciseController::class, 'destroyQuestion'])->name('questions.destroy');
            Route::resource('certificates', \App\Http\Controllers\Scolarite\AdminCertificateController::class)->only(['index', 'destroy']);
            Route::post('certificates/{student}/{module}', [\App\Http\Controllers\Scolarite\AdminCertificateController::class, 'generate'])->name('certificates.generate');
        });

        // Schedules
        Route::get('/schedules', [\App\Http\Controllers\ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/schedules', [\App\Http\Controllers\ScheduleController::class, 'store'])->name('schedules.store');
        Route::put('/schedules/{schedule}', [\App\Http\Controllers\ScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('/schedules/{schedule}', [\App\Http\Controllers\ScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    // -----------------------------------------------------------------------
    // All Authenticated Users (Common Resources)
    // -----------------------------------------------------------------------
    // User Profile
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/settings', [\App\Http\Controllers\ProfileController::class, 'settings'])->name('profile.settings');
    Route::post('/notifications/mark-all-as-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');

    // Ecosystem View
    Route::get('/ecosystem', [\App\Http\Controllers\EcosystemController::class, 'index'])->name('ecosystem.index');
    
    // Partnerships
    Route::get('/ecosystem/partnerships', [\App\Http\Controllers\EcosystemController::class, 'partnerships'])->name('ecosystem.partnerships');
    
    Route::middleware(['role:Directeur'])->group(function () {
        Route::post('/ecosystem/partnerships', [\App\Http\Controllers\EcosystemController::class, 'storePartnership'])->name('ecosystem.partnerships.store');
        Route::put('/ecosystem/partnerships/{partnership}', [\App\Http\Controllers\EcosystemController::class, 'updatePartnership'])->name('ecosystem.partnerships.update');
        Route::delete('/ecosystem/partnerships/{partnership}', [\App\Http\Controllers\EcosystemController::class, 'destroyPartnership'])->name('ecosystem.partnerships.destroy');
        Route::patch('/ecosystem/partnerships/{partnership}/toggle', [\App\Http\Controllers\EcosystemController::class, 'togglePartnershipStatus'])->name('ecosystem.partnerships.toggle');
    });
    
    // Events
    Route::get('/ecosystem/events', [\App\Http\Controllers\EcosystemController::class, 'events'])->name('ecosystem.events');
    
    Route::middleware(['role:Directeur'])->group(function () {
        Route::post('/ecosystem/events', [\App\Http\Controllers\EcosystemController::class, 'storeEvent'])->name('ecosystem.events.store');
        Route::put('/ecosystem/events/{event}', [\App\Http\Controllers\EcosystemController::class, 'updateEvent'])->name('ecosystem.events.update');
        Route::delete('/ecosystem/events/{event}', [\App\Http\Controllers\EcosystemController::class, 'destroyEvent'])->name('ecosystem.events.destroy');
        Route::patch('/ecosystem/events/{event}/toggle', [\App\Http\Controllers\EcosystemController::class, 'toggleEventStatus'])->name('ecosystem.events.toggle');
    });

    // Media Mentions
    Route::post('/ecosystem/media', [\App\Http\Controllers\EcosystemController::class, 'storeMediaMention'])->name('ecosystem.media.store');

    // Lists (Read Only for most)
    Route::get('/groups', [\App\Http\Controllers\Scolarite\GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/{group}/students', [\App\Http\Controllers\Scolarite\GroupStudentController::class, 'index'])->name('groups.students.index');
    Route::get('/rooms', [\App\Http\Controllers\Scolarite\RoomController::class, 'index'])->name('rooms.index');
    Route::get('/modules', [\App\Http\Controllers\ModuleController::class, 'index'])->name('modules.index');
    Route::get('/modules/{module}', [\App\Http\Controllers\ModuleController::class, 'show'])->name('modules.show');
    Route::get('/students', [\App\Http\Controllers\StudentsController::class, 'index'])->name('students.index');
    Route::get('/trainees', [\App\Http\Controllers\TraineesController::class, 'index'])->name('trainees.index');
    Route::get('/trainees/preview/{trainee}/{type}', [\App\Http\Controllers\TraineesController::class, 'previewFile'])->name('trainees.preview');

    // Logistics & Physical Inventory
    Route::resource('assets', \App\Http\Controllers\AssetController::class);
    Route::patch('assets/{asset}/approve', [\App\Http\Controllers\AssetController::class, 'approve'])->name('assets.approve');
    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::post('/loans/checkout', [LoanController::class, 'checkout'])->name('loans.checkout');
    Route::patch('/loans/{loan}/return', [LoanController::class, 'returnAsset'])->name('loans.return');
    Route::patch('/loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
    Route::patch('/loans/{loan}/reject', [LoanController::class, 'reject'])->name('loans.reject');
    Route::get('/loans/{loan}/signature', [LoanController::class, 'signature'])->name('loans.signature');

    // Nominations Creation
    Route::get('/nominations', [NominationController::class, 'index'])->name('nominations.index');
    Route::post('/nominations', [NominationController::class, 'store'])->name('nominations.store');


    // Chapter Progress (Accessible by trainers, students/group leaders with appropriate internal checks)
    Route::get('/chapter-progress/groups', [\App\Http\Controllers\ChapterProgressController::class, 'groupsIndex'])->name('chapter-progress.groups');
    Route::get('/groups/{group}/chapter-progress', [\App\Http\Controllers\ChapterProgressController::class, 'index'])->name('chapter-progress.index');
    Route::post('/chapter-progress', [\App\Http\Controllers\ChapterProgressController::class, 'submit'])->name('chapter-progress.submit');
    Route::patch('/chapter-progress/{chapterGroupProgress}/approve', [\App\Http\Controllers\ChapterProgressController::class, 'approve'])->name('chapter-progress.approve');
    Route::patch('/chapter-progress/{chapterGroupProgress}/reject', [\App\Http\Controllers\ChapterProgressController::class, 'reject'])->name('chapter-progress.reject');
    Route::delete('/chapter-progress/{chapterGroupProgress}', [\App\Http\Controllers\ChapterProgressController::class, 'cancel'])->name('chapter-progress.cancel');

    // Community Hub
    Route::get('/community', [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('community.index');
    Route::post('/community', [\App\Http\Controllers\AnnouncementController::class, 'store'])->name('community.store');
    Route::put('/community/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'update'])->name('community.update');
    Route::delete('/community/{announcement}', [\App\Http\Controllers\AnnouncementController::class, 'destroy'])->name('community.destroy');
    Route::post('/community/{announcement}/replies', [\App\Http\Controllers\AnnouncementReplyController::class, 'store'])->name('community.replies.store');
    Route::delete('/community/replies/{reply}', [\App\Http\Controllers\AnnouncementReplyController::class, 'destroy'])->name('community.replies.destroy');
    Route::post('/community/{announcement}/like', [\App\Http\Controllers\AnnouncementLikeController::class, 'toggle'])->name('community.likes.toggle');

    // Leaves Management
    Route::get('/leaves', [\App\Http\Controllers\LeaveController::class, 'index'])->name('leaves.index');
    Route::post('/leaves', [\App\Http\Controllers\LeaveController::class, 'store'])->name('leaves.store');
    Route::post('/leaves/{leaf}/update', [\App\Http\Controllers\LeaveController::class, 'update'])->name('leaves.update');
    Route::get('/leaves/{leaf}/document', [\App\Http\Controllers\LeaveController::class, 'downloadDocument'])->name('leaves.document');
    Route::delete('/leaves/{leaf}', [\App\Http\Controllers\LeaveController::class, 'destroy'])->name('leaves.destroy');
    Route::middleware(['role:Directeur'])->group(function () {
        Route::patch('/leaves/{leaf}/status', [\App\Http\Controllers\LeaveController::class, 'updateStatus'])->name('leaves.status.update');
    });

    // AI Agent Assane
    Route::post('/assane/chat', [\App\Http\Controllers\Scolarite\AssaneChatController::class, 'chat'])->name('assane.chat');
});
