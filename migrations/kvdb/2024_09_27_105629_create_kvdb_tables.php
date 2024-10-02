<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kvdb_leases', function (Blueprint $table) {
            $table->bigIncrements('lease_id');
            $table->integer('ttl')
                ->unsigned();
            $table->timestamp('granted_at')
                ->useCurrent();
            $table->timestamps();
        });

        Schema::create('kvdb_store', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key_path')
                ->unique();
            $table->text('value');
            $table->boolean('encrypted')
                ->default(false);
            $table->timestamp('created_at')
                ->useCurrent();
            $table->timestamp('updated_at')
                ->useCurrent()
                ->nullable()
                ->useCurrentOnUpdate();
            $table->integer('version')
                ->default(1);
            $table->unsignedBigInteger('lease_id')
                ->nullable();
            $table->foreign('lease_id')
                ->references('lease_id')
                ->on('kvdb_leases')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kvdb_store');
        Schema::dropIfExists('kvdb_leases');
    }
};
