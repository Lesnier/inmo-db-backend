<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use TCG\Voyager\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;

class SetupTestDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-test-db {--force : Force the operation to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the testing SQLite database (Migrate, Seed, Create Admin)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up Testing Database (sqlite_testing)...');

        if ($this->option('force')) {
            $this->warn('Force mode enabled.');
        }
        
        // Ensure the database file exists
        $databasePath = config('database.connections.sqlite_testing.database');
        if (!file_exists($databasePath)) {
            $this->info("Creating database file at: $databasePath");
            touch($databasePath);
        }

        // 1. Run Migrations
        $this->info('Running Migrations on sqlite_testing...');
        $this->call('migrate:fresh', [
            '--database' => 'sqlite_testing',
            '--force' => true,
        ]);

        // 2. Run Seeders
        $this->info('Seeding Database on sqlite_testing...');
        $this->call('db:seed', [
            '--database' => 'sqlite_testing',
            '--force' => true,
        ]);

        // 3. Create Admin User
        $this->info('Creating Admin User on sqlite_testing...');
        
        $adminEmail = 'admin@admin.com';
        $adminPassword = 'password';

        // Use on('sqlite_testing') to ensure we target the correct DB
        $role = Role::on('sqlite_testing')->where('name', 'admin')->first();
        
        if (!$role) {
            $this->error('Admin role not found in sqlite_testing! Check seeders.');
            return 1;
        }

        $user = User::on('sqlite_testing')->firstOrNew(['email' => $adminEmail]);
        
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
            $user->role_id = $role->id;
            $user->save();
        }

        $this->info('--------------------------------------');
        $this->info('Testing Database Setup Successfully!');
        $this->info("DB Path: $databasePath");
        $this->info('--------------------------------------');

        return 0;
    }
}
