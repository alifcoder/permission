<?php
/**
 * Created by Shukhratjon Yuldashev on 2025-05-15
 * Contact: https://t.me/alif_coder
 * Time: 6:05 PM
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * ATTENTION!
     * Don't change table and column names
     * But you can add new columns
     *
     * If you use UUIDs in your project, you can change id to uuid
     * After change you need to override Role and Permission models.
     * Extend core models to your own models
     */
    public function up(): void
    {
        // create permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
        });

        // create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable()->index();
            $table->string('s_code')->nullable()->index();

            $table->softDeletes();
            $table->timestamps();

            // do unique name in deleted at is null
            $table->unique(['name', 'deleted_at'], 'roles_name_deleted_at_null_unique_index');
            $table->unique(['s_code', 'deleted_at'], 'roles_s_code_deleted_at_null_unique_index');
        });

        // create role_permission table
        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('permission_id')->constrained('permissions');
            $table->primary(['role_id', 'permission_id']);
        });

        // create user_role table
        Schema::create('user_role', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('role_id')->constrained('roles');
            $table->primary(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        // drop role_permission table
        Schema::dropIfExists('role_permission');

        // drop user_role table
        Schema::dropIfExists('user_role');

        // drop permissions table
        Schema::dropIfExists('permissions');

        // drop roles table
        Schema::dropIfExists('roles');
    }
};