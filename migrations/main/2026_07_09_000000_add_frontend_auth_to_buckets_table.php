<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kvdb_buckets', function (Blueprint $table) {
            // Per-bucket JWT verification settings, e.g. {"secret": "...", "algo": "HS256"}.
            // Falls back to the `kvdb.jwt` config defaults when null.
            $table->json('jwt_config')->nullable()->after('user_ref');

            // Declarative, static path-matching rules for frontend access, e.g.
            // [{"pattern": ["todos", "{user_id}", "*"], "abilities": ["read", "write"]}].
            // Absent or empty means no frontend JWT access is allowed for this bucket.
            $table->json('frontend_rules')->nullable()->after('jwt_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kvdb_buckets', function (Blueprint $table) {
            $table->dropColumn(['jwt_config', 'frontend_rules']);
        });
    }
};
