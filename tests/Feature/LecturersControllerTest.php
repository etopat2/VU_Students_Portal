<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LecturersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_lecturer_record(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('lecturers.store'), [
                'lecturer_number' => 'VU-LEC-001',
                'name' => 'Alex Morgan',
                'email' => 'alex.morgan@example.test',
                'department' => 'Computing',
                'qualification' => 'MSc Information Technology',
                'phone' => '+256700000001',
                'status' => 'Active',
            ])
            ->assertRedirect(route('lecturers.index'));

        $this->assertDatabaseHas('lecturers', [
            'lecturer_number' => 'VU-LEC-001',
            'name' => 'Alex Morgan',
            'email' => 'alex.morgan@example.test',
            'department' => 'Computing',
            'status' => 'Active',
        ]);
    }

    public function test_admin_can_edit_and_delete_a_lecturer(): void
    {
        $lecturer = Lecturer::create([
            'lecturer_number' => 'VU-LEC-001',
            'name' => 'Alex Morgan',
            'email' => 'alex.morgan@example.test',
            'department' => 'Computing',
            'qualification' => 'BSc Computing',
            'phone' => null,
            'status' => 'Active',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('lecturers.update', $lecturer), [
                'lecturer_number' => 'VU-LEC-001',
                'name' => 'Alex Morgan Updated',
                'email' => 'alex.morgan@example.test',
                'department' => 'Information Systems',
                'qualification' => 'MSc Computing',
                'phone' => '+256700000002',
                'status' => 'On leave',
            ])
            ->assertRedirect(route('lecturers.index'));

        $this->assertDatabaseHas('lecturers', [
            'id' => $lecturer->id,
            'name' => 'Alex Morgan Updated',
            'department' => 'Information Systems',
            'status' => 'On leave',
        ]);

        $this->delete(route('lecturers.destroy', $lecturer))
            ->assertRedirect(route('lecturers.index'));

        $this->assertDatabaseMissing('lecturers', ['id' => $lecturer->id]);
    }

    public function test_lecturer_form_rejects_missing_required_fields(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->from(route('lecturers.create'))
            ->post(route('lecturers.store'), [])
            ->assertRedirect(route('lecturers.create'))
            ->assertSessionHasErrors([
                'lecturer_number',
                'name',
                'email',
                'department',
                'qualification',
                'status',
            ]);

        $this->assertDatabaseCount('lecturers', 0);
    }

    public function test_admin_can_open_lecturer_directory_and_ajax_forms(): void
    {
        $lecturer = Lecturer::create([
            'lecturer_number' => 'VU-LEC-002',
            'name' => 'Jamie Lee',
            'email' => 'jamie.lee@example.test',
            'department' => 'Business',
            'qualification' => 'MBA',
            'phone' => null,
            'status' => 'Active',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('lecturers.index'))
            ->assertOk()
            ->assertSee('Lecturer directory')
            ->assertSee('Jamie Lee');

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('lecturers.create'))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Add lecturer')
            ->assertSee('Staff ID');

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('lecturers.edit', $lecturer))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Edit lecturer')
            ->assertSee('Jamie Lee');
    }

    public function test_students_cannot_access_lecturer_management(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'student']))
            ->get(route('lecturers.index'))
            ->assertForbidden();
    }
}
