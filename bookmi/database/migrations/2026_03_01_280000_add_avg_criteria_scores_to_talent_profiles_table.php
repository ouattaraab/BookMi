<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table): void {
            $table->float('avg_punctuality_score', 4, 2)->nullable()->after('average_rating');
            $table->float('avg_quality_score', 4, 2)->nullable()->after('avg_punctuality_score');
            $table->float('avg_professionalism_score', 4, 2)->nullable()->after('avg_quality_score');
            $table->float('avg_contract_respect_score', 4, 2)->nullable()->after('avg_professionalism_score');
        });
    }

    public function down(): void
    {
        Schema::table('talent_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'avg_punctuality_score',
                'avg_quality_score',
                'avg_professionalism_score',
                'avg_contract_respect_score',
            ]);
        });
    }
};
