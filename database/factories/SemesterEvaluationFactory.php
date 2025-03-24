<?php

namespace Database\Factories;

use App\Models\SemesterEvaluation;
use App\Models\SemesterStatus;
use App\Models\StudyLine;
use Illuminate\Database\Eloquent\Factories\Factory;

class SemesterEvaluationFactory extends Factory
{
    protected $model = SemesterEvaluation::class;

    /**
     * Provides what kind of content Faker should generate
     * for a dummy user.
     */
    public function definition()
    {
        return [
            'user_id' =>  \App\Models\User::where('verified', true)->get()->random()->id,
            'semester_id' =>  \App\Models\Semester::get()->random()->id,
            'alfonso_note' => $this->faker->paragraph(rand(1, 3)),
            'courses_note' => $this->faker->paragraph(rand(1, 3)),
            'current_avg' => $this->faker->randomFloat(1,5),
            'last_avg' => $this->faker->randomFloat(1,5),
            'general_assembly_note' => $this->faker->paragraph(rand(0, 3)),
            'professional_results' => $this->faker->paragraphs(rand(0, 3)),
            'research' => $this->faker->paragraphs(rand(0, 3)),
            'publications' => $this->faker->paragraphs(rand(0, 3)),
            'conferences' => $this->faker->paragraphs(rand(0, 3)),
            'scholarships' => $this->faker->paragraphs(rand(0, 3)),
            'educational_activity' => $this->faker->paragraphs(rand(0, 3)),
            'public_life_activities' => $this->faker->paragraphs(rand(0, 3)),
            'can_be_shared' => $this->faker->boolean(),
            'feedback' => $this->faker->paragraph(rand(0, 1)),
            'resign_residency' => $this->faker->boolean(),
            'next_status' => $this->faker->randomElement([SemesterStatus::ACTIVE, SemesterStatus::PASSIVE]),
            'next_status_note' => $this->faker->paragraph(rand(0, 1)),
            'will_write_request' => $this->faker->boolean()
        ];
    }
}
