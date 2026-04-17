<?php

namespace Database\Seeders;

use App\AppSettingPeriod;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\LedgerSeason;
use App\Models\User;
use Carbon\Carbon;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Categories
        $categories = [
            ['categoryName' => 'Kapas', 'created_at' => now(),'updated_at' => now()],
            ['categoryName' => 'Makai', 'created_at' => now(),'updated_at' => now()],
        ];
        Category::insert($categories);
        // ✅ Create Permissions
        $permissions = [
            // Dashboard
            'read dashboard',
            'billNumber ledger',
            'dashboardSummary ledger',

            // User
            'read user',
            'index user',
            'create user',
            'store user',
            'update user',
            'show user',
            'destroy user',
            'AllUsers user',
            'getUserRolesPermissions user',

            // Role
            'read role',
            'index role',
            'store role',
            'create role',
            'show role',
            'update role',
            'destroy role',
            'manage role',

            // User
            'allPermissions role',

            // Ledger
            'read ledger',
            'index ledger',
            'show ledger',
            'store ledger',
            'create ledger',
            'update ledger',
            'destroy ledger',
            'report ledger',
            'activeSeason ledger',

            // Ledger Season
            'read ledgerseason',
            'index ledgerseason',
            'create ledgerseason',
            'store ledgerseason',
            'show ledgerseason',
            'update ledgerseason',
            'destroy ledgerseason',
            'view ledger season summary',
            'index stock',
            'index activitylog',
            'season_summaries ledgerseason',
            'read stock',
            // Sale
            'read sale',
            'index sale',
            'create sale',
            'store sale',
            'show sale',
            'update sale',
            'destroy sale',

            // Purchase
            'read purchase',
            'index purchase',
            'create purchase',
            'store purchase',
            'show purchase',
            'update purchase',
            'destroy purchase',

            // Investment
            'read investment',
            'index investment',
            'create investment',
            'store investment',
            'show invesstment',
            'update investment',
            'destroy investment',

            // Category
            'read category',
            'index category',
            'create category',
            'store category',
            'show category',
            'update category',
            'destroy category',

            // Expense Type
            'read expensetype',
            'index expensetype',
            'create expensetype',
            'store expensetype',
            'show expensetype',
            'update expensetype',
            'destroy expensetype',

            // Activity Log
            'read activity log',
            'index activity log',
            'destroy activity log',

            // App Setting
            'read app setting',
            'index app setting',
            'create app setting',
            'update app setting',
            'destroy app setting',

            // Expense
            'read expense',
            'index expense',
            'store expense',
            'create expense',
            'show expense',
            'update expense',
            'destroy expense',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'api']);
        $superAdmin->givePermissionTo(Permission::all());
        // cutomers
        $cutomers = [
            [
                'name' => 'Admin User',
                'email' => 'admin@zee.com',
                'phone_number' => '03001034577',
                'address'=>"admin address",
                'type'=> 'owner',
                'password' => Hash::make('admin@zee$#1'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Investor User',
                'email' => 'user@zee.com',
                'phone_number' => '03001034587',
                'address'=>"investor address",
                'type'=> 'investor',
                'password' => Hash::make('112233'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Walk-in buyer',
                'email' => 'walk-in-buyer@gmail.com',
                'phone_number' => '03001034567',
                'address'=>"walkinbuyer",
                'type'=>"buyer",
                'password' => Hash::make('112233'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Walk-in supplier',
                'email' => 'walk-in-supplier@gmail.com',
                'phone_number' => '03001134567',
                'address'=>"walkinsupplier",
                'type'=>"supplier",
                'password' => Hash::make('112233'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Other User',
                'email' => 'other@user.com',
                'phone_number' => '03001234567',
                'address'=>"other city",
                'type'=>"other",
                'password' => Hash::make('112233'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        foreach ($cutomers as $customer) {
            User::firstOrCreate(
                ['email' => $customer['email']], // ✅ unique field
                $customer // ✅ data to insert if not exists
            );
        }
        
        $adminUser = User::where('email', 'admin@zee.com')->first();
        $adminUser->assignRole('Super Admin');
        // app settings
        AppSetting::updateOrCreate(
            ['key' => 'deletion_period'],
            ['value' => AppSettingPeriod::OneWeek->value]
        );
        AppSetting::updateOrCreate(
            ['key' => 'updation_period'],
            ['value' => AppSettingPeriod::OneWeek->value]
        );
        LedgerSeason::insert([
            'name'=>'Default Season',
            'description'=>'Default Season',
            'status'=>'active',
            'start_date'=>now(),
            'end_date'=>now()->addMonths(6),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }
}
