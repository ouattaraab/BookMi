<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->unsignedTinyInteger('punctuality_score')->nullable()->after('rating');
            $table->unsignedTinyInteger('quality_score')->nullable()->after('punctuality_score');
            $table->unsignedTinyInteger('professionalism_score')->nullable()->after('quality_score');
            $table->unsignedTinyInteger('contract_respect_score')->nullable()->after('professionalism_score');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropColumn([
                'punctuality_score',
                'quality_score',
                'professionalism_score',
                'contract_respect_score',
            ]);
        });
    }
};
