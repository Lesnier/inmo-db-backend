<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use TCG\Voyager\Models\Role;
use Illuminate\Support\Facades\Hash;

class InstallSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the application (Migrate, Seed, Create Admin, Setup Voyager)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting System Installation...');

        if ($this->option('force')) {
            $this->warn('Force mode enabled.');
        }

        // 1. Run Migrations
        $this->info('Running Migrations...');
        $this->call('migrate:fresh', [
            '--force' => true,
        ]);

        // 2. Create Admin User (Voyager Admin)
        $this->info('Creating Admin User...');
        // Ensure roles exist first by running a partial seed or just creating the admin role manually here if needed.
        // However, the seeders will run next. But to create the admin user LIKE voyager:admin does, we need the role first.
        
        // Let's run the seeders FIRST to ensure roles and base data exist.
        $this->info('Seeding Database (Global Seeders)...');
        $this->call('db:seed', [
            '--force' => true,
        ]);

        // NOW create the admin user if it doesn't exist (or update it)
        $adminEmail = 'admin@admin.com';
        $adminPassword = 'password';

        $role = Role::where('name', 'admin')->first();
        if (!$role) {
            // Fallback if seeder didn't create 'admin' text role, but Voyager seeder should have.
            $this->error('Admin role not found! Check Voyager seeders.');
            return 1;
        }

        $user = User::firstOrNew(['email' => $adminEmail]);
        if (!$user->exists) {
            $user->fill([
                'name' => 'Admin',
                'password' => Hash::make($adminPassword),
            ]);
            $user->role_id = $role->id;
            $user->save();
            $this->info("Admin User Created: $adminEmail / $adminPassword");
        } else {
            $this->info("Admin User already exists: $adminEmail");
            // Optional: Reset password or ensure role
            $user->role_id = $role->id;
            $user->save();
        }

        // 3. Link Storage
        $this->info('Linking Storage...');
        $this->call('storage:link');

        // 4. Clear Caches
        $this->info('Clearing Caches...');
        $this->call('config:clear');
        $this->call('cache:clear');

        $this->info('--------------------------------------');
        $this->info('System Installed Successfully!');
        $this->info('Admin URL: /admin');
        $this->info('Swagger API Docs: /api/documentation');
        $this->info("Login: $adminEmail");
        $this->info("Password: $adminPassword");
        $this->info('--------------------------------------');

        return 0;
    }
}
