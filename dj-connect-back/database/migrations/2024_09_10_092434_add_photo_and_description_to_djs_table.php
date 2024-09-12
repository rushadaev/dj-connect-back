<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('djs', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('user_id');
            $table->text('description')->nullable()->after('stage_name');
            $table->integer('views')->default(0)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('djs', function (Blueprint $table) {
            $table->dropColumn(['photo', 'description', 'views']);
        });
    }
};