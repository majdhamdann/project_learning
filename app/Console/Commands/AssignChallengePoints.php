<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Challenge;
use App\Models\ChallengeReport;
use App\Models\Point;
use Carbon\Carbon;

class AssignChallengePoints extends Command
{
    /**
     * اسم التعليمة اللي بتشغل الكوماند
     */
    protected $signature = 'challenges:assign-points';

    /**
     * وصف الكوماند
     */
    protected $description = 'Assign points to students after challenge ends and update points table';

    /**
     * الكود الأساسي
     */
    public function handle()
    {
        $now = Carbon::now();

        // كل التحديات المنتهية
        $expiredChallenges = Challenge::where('start_time', '<', $now)
            ->whereRaw("DATE_ADD(start_time, INTERVAL duration_minutes MINUTE) <= ?", [$now])
            ->get();

        foreach ($expiredChallenges as $challenge) {
            // جلب تقارير الطلاب لهالتحدي
            $reports = ChallengeReport::where('challenge_id', $challenge->id)
                ->orderByDesc('correct_answers_count')
                ->get();

            if ($reports->isEmpty()) {
                continue;
            }

            // توزيع النقاط: الأول 10 - الثاني 5 - الثالث 2
            $pointsDistribution = [10, 5, 2];

            foreach ($reports->take(3) as $index => $report) {
                $points = $pointsDistribution[$index];

                // إذا النقاط ما نُقلت لهالطالب
                if (!$report->points_transferred) {
                    // تحديث عمود challenge_points بالتقرير
                    $report->update([
                        'challenge_points' => $points,
                        'points_transferred' => true,
                    ]);

                    // إضافة النقاط لجدول points
                    $this->addPointsToStudent(
                        $report->student_id,
                        $challenge->teacher_id,
                        $points
                    );
                }
            }
        }

        $this->info('Challenge points assigned successfully.');
    }

    /**
     * إضافة النقاط لجدول points
     */
    protected function addPointsToStudent($studentId, $teacherId, $points)
    {
        // تحقق من وجود سجل مسبق
        $pointsRecord = Point::firstOrCreate([
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
        ], [
            'points' => 0,
        ]);

        // إضافة النقاط الجديدة
        $pointsRecord->points += $points;
        $pointsRecord->save();
    }
}
