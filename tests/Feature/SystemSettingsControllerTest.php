<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_updates_institution_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('system-settings.update'), [
                'name' => 'Victoria University',
                'code' => 'VU',
                'email' => 'info@vu.ac.ug',
                'phone' => '+256700000000',
                'address' => 'Kampala, Uganda',
            ])
            ->assertRedirect(route('system-settings.edit'));

        $this->assertDatabaseHas('institutions', [
            'name' => 'Victoria University',
            'code' => 'VU',
            'email' => 'info@vu.ac.ug',
            'phone' => '+256700000000',
            'address' => 'Kampala, Uganda',
        ]);
    }

    public function test_admin_can_edit_existing_institution_details_without_duplicate_codes(): void
    {
        $institution = Institution::create([
            'name' => 'Example University',
            'code' => 'EXU',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('system-settings.update'), [
                'name' => 'Example University Updated',
                'code' => 'EXU',
                'email' => null,
                'phone' => null,
                'address' => null,
            ])
            ->assertRedirect(route('system-settings.edit'));

        $this->assertDatabaseHas('institutions', [
            'id' => $institution->id,
            'name' => 'Example University Updated',
            'code' => 'EXU',
        ]);
        $this->assertDatabaseCount('institutions', 1);
    }

    public function test_registrar_cannot_change_system_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'registrar']))
            ->get(route('system-settings.edit'))
            ->assertForbidden();
    }
}
