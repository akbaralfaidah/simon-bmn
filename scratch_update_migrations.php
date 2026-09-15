<?php
$dir = __DIR__ . '/database/migrations/';
$files = scandir($dir);

$migrations = [
    'create_organization_units_table' => <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('organization_units', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name');
            \$table->string('code')->nullable();
            \$table->foreignId('parent_id')->nullable()->constrained('organization_units')->nullOnDelete();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('organization_units'); }
};
PHP,

    'create_buildings_table' => <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('buildings', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('unit_id')->constrained('organization_units')->cascadeOnDelete();
            \$table->string('name');
            \$table->string('location')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('buildings'); }
};
PHP,

    'create_rooms_table' => <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rooms', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('building_id')->nullable()->constrained('buildings')->nullOnDelete();
            \$table->foreignId('unit_id')->constrained('organization_units')->cascadeOnDelete();
            \$table->string('name');
            \$table->string('code')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('rooms'); }
};
PHP,

    'create_employee_profiles_table' => <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_profiles', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            \$table->foreignId('unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            \$table->string('nip')->nullable();
            \$table->string('phone')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('employee_profiles'); }
};
PHP,

    'create_roles_table' => <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('roles', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name')->unique();
            \$table->string('description')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('roles'); }
};
PHP,

    'create_permissions_table' => <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('permissions', function (Blueprint \$table) {
            \$table->id();
            \$table->string('name')->unique();
            \$table->string('description')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('permissions'); }
};
PHP,

    'create_role_assignments_table' => <<<PHP
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('role_assignments', function (Blueprint \$table) {
            \$table->id();
            \$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            \$table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            \$table->foreignId('unit_id')->nullable()->constrained('organization_units')->nullOnDelete();
            \$table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            \$table->timestamp('starts_at')->nullable();
            \$table->timestamp('ends_at')->nullable();
            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('role_assignments'); }
};
PHP,
];

foreach ($files as $file) {
    foreach ($migrations as $key => $content) {
        if (strpos($file, $key) !== false) {
            file_put_contents($dir . $file, $content);
            echo "Updated: \$file\n";
        }
    }
}
?>
