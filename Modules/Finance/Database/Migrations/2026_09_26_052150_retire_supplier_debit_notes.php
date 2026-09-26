<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supplier debit notes are retired: they could never post (the journal was unbalanced and on the wrong side)
 * and a credit received from a supplier is recorded as a supplier credit note. Unposted debit notes become draft
 * supplier credit notes (without lines, to be completed before posting). A posted debit note stops the migration
 * rather than lose its link to the ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_debit_notes')) {
            return;
        }

        if (DB::table('supplier_debit_notes')->whereNotNull('journal_id')->orWhere('status', 'POSTED')->exists()) {
            throw new RuntimeException('Posted supplier debit notes exist; convert them to supplier credit notes by hand before retiring debit notes.');
        }

        DB::transaction(function () {
            foreach (DB::table('supplier_debit_notes')->where('status', '!=', 'CANCELLED')->get() as $note) {
                $number = $note->note_number;

                if (DB::table('supplier_credit_notes')->where('company_id', $note->company_id)->where('credit_note_number', $number)->exists()) {
                    $number .= '-DN';
                }

                DB::table('supplier_credit_notes')->insert([
                    'company_id' => $note->company_id,
                    'supplier_id' => $note->supplier_id,
                    'credit_note_number' => $number,
                    'credit_note_date' => $note->note_date,
                    'subtotal' => $note->amount,
                    'tax_amount' => 0,
                    'total_amount' => $note->amount,
                    'reason' => trim(($note->description ?? '').' (converted from debit note '.$note->note_number.')'),
                    'status' => 'DRAFT',
                    'created_by' => $note->created_by,
                    'updated_by' => $note->updated_by,
                    'created_at' => $note->created_at,
                    'updated_at' => now(),
                ]);
            }

            DB::table('permissions')->where('slug', 'like', 'finance.supplier-debit-notes.%')->delete();
        });

        Schema::drop('supplier_debit_notes');
    }

    public function down(): void
    {
        Schema::create('supplier_debit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('note_number', 50);
            $table->date('note_date');
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 20, 4)->default(0);
            $table->string('status', 30)->default('DRAFT');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'note_number']);
            $table->index('status');
        });
    }
};
