<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('user_invitations', function (Blueprint $table) {
            $table->boolean('multiple')->default(false)->after('valid_till');
            $table->unsignedInteger('multiple_count')->default(0)->after('multiple');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('user_invitations', function (Blueprint $table) {
            $table->dropColumns(['multiple', 'multiple_count']);
        });
    }
};