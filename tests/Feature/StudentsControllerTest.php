<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_be_created_with_valid_details(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $response = $this->post(route('students.store'), [
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Business Computing',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', [
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
        ]);
    }

    public function test_student_updates_and_deletes_through_resource_routes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Business Computing',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);

        $this->put(route('students.update', $student), [
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan Updated',
            'email' => 'alex@example.test',
            'programme' => 'Data Analytics',
            'year' => 'Year 2',
            'status' => 'Active',
        ])->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Alex Morgan Updated',
            'programme' => 'Data Analytics',
        ]);

        $this->delete(route('students.destroy', $student))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    public function test_student_creation_rejects_missing_required_details(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->from(route('students.create'))
            ->post(route('students.store'), [])
            ->assertRedirect(route('students.create'))
            ->assertSessionHasErrors(['student_number', 'name', 'email', 'programme', 'year', 'status']);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_create_and_edit_forms_render_as_ajax_content_fragments(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $student = Student::create([
            'student_number' => 'VU-2026-001',
            'name' => 'Alex Morgan',
            'email' => 'alex@example.test',
            'programme' => 'Business Computing',
            'year' => 'Year 1',
            'status' => 'Active',
        ]);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('students.create'))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Add student')
            ->assertSee('Student ID')
            ->assertDontSee('id="appSidebar"', false);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('students.edit', $student))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Edit student')
            ->assertSee('Alex Morgan')
            ->assertSee('alex@example.test');
    }
}
