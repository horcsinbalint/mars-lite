<?php

namespace Database\Seeders;

use App\Models\SemesterEvaluation;
use Illuminate\Database\Seeder;

class SemesterEvaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SemesterEvaluation::factory()->count(50)->create();
    }
}
