<?php

namespace Database\Seeders;

use App\Models\Accountant;
use App\Models\Lecturer;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenerateMissingUserAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $this->createAccountsFor(Student::query(), 'student');
        $this->createAccountsFor(Lecturer::query(), 'lecturer');
        $this->createAccountsFor(Accountant::query(), 'accountant');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<Model> $people
     */
    private function createAccountsFor(Builder $people, string $roleName): void
    {
        $role = Role::query()->firstOrCreate(['name' => $roleName]);

        $people->whereNull('user_id')->orderBy('id')->chunkById(100, function ($records) use ($roleName, $role): void {
            foreach ($records as $person) {
                $email = $this->accountEmail($person, $roleName);
                $user = User::query()->where('email', $email)->first();

                if ($user && $this->isLinkedToAnotherProfile($user)) {
                    $email = $this->generatedEmail($person, $roleName);
                    $user = User::query()->where('email', $email)->first();
                }

                if (! $user) {
                    $user = User::create([
                        'name' => $person->name,
                        'email' => $email,
                        'password' => 'Password123',
                        'role' => $roleName,
                        'must_change_password' => true,
                    ]);
                } else {
                    $user->forceFill(['role' => $roleName])->save();
                }

                $user->roles()->syncWithoutDetaching([$role->id]);
                $person->user()->associate($user)->save();
            }
        });
    }

    private function accountEmail(Model $person, string $roleName): string
    {
        $email = trim((string) $person->email);

        return filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $email
            : $this->generatedEmail($person, $roleName);
    }

    private function generatedEmail(Model $person, string $roleName): string
    {
        $identity = $person->getAttribute('student_number')
            ?? $person->getAttribute('lecturer_number')
            ?? $person->getAttribute('staff_number')
            ?? (string) $person->getKey();
        $prefix = Str::slug($roleName.'-'.$identity);
        $email = $prefix.'@accounts.vu.invalid';
        $suffix = 1;

        while (User::query()->where('email', $email)->exists()) {
            $email = $prefix.'-'.$suffix.'@accounts.vu.invalid';
            $suffix++;
        }

        return $email;
    }

    private function isLinkedToAnotherProfile(User $user): bool
    {
        return $user->student()->exists()
            || $user->lecturer()->exists()
            || $user->accountant()->exists();
    }
}
