<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_can_be_created_with_valid_details(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $response = $this->post(route('courses.store'), [
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'schedule' => 'Mon, Wed · 10:00 AM',
            'status' => 'In progress',
        ]);

        $response->assertRedirect(route('courses.index'));
        $this->assertDatabaseHas('courses', [
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'credits' => 4,
        ]);
    }

    public function test_course_updates_and_deletes_through_resource_routes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $course = Course::create([
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);

        $this->put(route('courses.update', $course), [
            'code' => 'BCS 210',
            'title' => 'Advanced Database Systems',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 5,
            'schedule' => 'Tue, Thu · 10:00 AM',
            'status' => 'In progress',
        ])->assertRedirect(route('courses.index'));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Advanced Database Systems',
            'credits' => 5,
        ]);

        $this->delete(route('courses.destroy', $course))
            ->assertRedirect(route('courses.index'));

        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_course_creation_rejects_invalid_credit_units(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->from(route('courses.create'))
            ->post(route('courses.store'), [
                'code' => 'BCS 210',
                'title' => 'Database Systems',
                'department' => 'Computing',
                'instructor' => 'Dr. Morgan',
                'credits' => 0,
                'status' => 'In progress',
            ])
            ->assertRedirect(route('courses.create'))
            ->assertSessionHasErrors('credits');

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_create_and_edit_forms_render_as_ajax_content_fragments(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $course = Course::create([
            'code' => 'BCS 210',
            'title' => 'Database Systems',
            'department' => 'Computing',
            'instructor' => 'Dr. Morgan',
            'credits' => 4,
            'status' => 'In progress',
        ]);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('courses.create'))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Add course')
            ->assertSee('Course code')
            ->assertDontSee('id="appSidebar"', false);

        $this->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('courses.edit', $course))
            ->assertOk()
            ->assertHeader('X-Page-Title', 'Edit course')
            ->assertSee('Database Systems')
            ->assertSee('BCS 210');
    }
}
