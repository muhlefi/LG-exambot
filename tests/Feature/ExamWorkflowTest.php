<?php

namespace Tests\Feature;

use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_structure_and_generate_questions(): void
    {
        $this->withoutVite();

        $user = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($user)
            ->post(route('sessions.store'), [
                'title' => 'PTS IPA Kelas VIII',
                'teacher_name' => 'Guru Demo',
                'school_name' => 'SMP Demo',
                'education_level' => 'SMP',
                'learning_phase' => 'Fase D',
                'class_level' => 'VIII',
                'semester' => 'Ganjil',
                'academic_year' => '2026/2027',
                'subject' => 'IPA',
                'topic' => 'Ekosistem',
                'subtopic' => 'Rantai makanan',
            ])
            ->assertRedirect();

        $session = ExamSession::firstOrFail();

        $this->actingAs($user)
            ->post(route('sessions.structures.store', $session), [
                'question_type' => 'Pilihan Ganda',
                'option_count' => 4,
                'easy_count' => 1,
                'medium_count' => 1,
                'hard_count' => 1,
                'cognitive_levels' => ['C1 Mengingat', 'C4 Menganalisis'],
            ])
            ->assertSessionHas('status');

        $this->actingAs($user)
            ->post(route('sessions.generate', $session))
            ->assertRedirect(route('sessions.results', $session));

        $this->assertDatabaseCount('questions', 3);
        $this->assertDatabaseCount('question_options', 12);
        $this->assertDatabaseCount('blueprints', 3);
    }
}
