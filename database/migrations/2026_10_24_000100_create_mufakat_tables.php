<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seluruh tabel Ruci MUFAKAT.
 *
 * Kolom data_origin membedakan report_fact (diambil dari laporan), derived_from_report
 * (hasil hitung/telaah atas sumber), dan simulated (rekaan untuk menggambarkan fitur).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Organisasi dan grup perusahaan
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('short_name', 40);
            $table->string('ticker', 10)->nullable();
            $table->string('relation', 30)->default('induk'); // induk, anak, cucu, afiliasi
            $table->decimal('ownership_pct', 6, 2)->nullable();
            $table->string('sector')->nullable();
            $table->text('description')->nullable();
            $table->string('data_origin', 30)->default('simulated');
            $table->string('source_ref')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('organs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_organ_id')->nullable()->constrained('organs')->nullOnDelete();
            $table->string('kind', 20); // direksi, dekom, komite, gabungan
            $table->string('name');
            $table->string('short_name', 40);
            $table->text('mandate')->nullable();
            $table->unsignedTinyInteger('quorum_numerator')->default(1);
            $table->unsignedTinyInteger('quorum_denominator')->default(2);
            $table->boolean('quorum_strictly_more')->default(true); // "lebih dari 1/2"
            $table->unsignedSmallInteger('min_meetings_per_year')->default(0);
            $table->unsignedTinyInteger('material_days_before')->default(5);
            $table->string('color', 10)->default('#1d4ed8');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        // 2. Profil anggota dewan
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organ_id')->constrained()->cascadeOnDelete();
            $table->string('position', 40); // ketua, anggota, sekretaris, pengamat
            $table->string('position_title')->nullable();
            $table->boolean('has_vote')->default(true);
            $table->boolean('is_independent')->default(false);
            $table->date('term_start')->nullable();
            $table->date('term_end')->nullable();
            $table->unsignedTinyInteger('term_number')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['organ_id', 'is_active']);
        });

        Schema::create('board_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('expertise')->nullable();
            $table->string('education')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_independent')->default(false);
            $table->date('independence_declared_at')->nullable();
            $table->date('onboarded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('historical_officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('organ', 20);
            $table->string('position');
            $table->date('as_of');
            $table->string('appointment_basis')->nullable();
            $table->string('source_ref')->nullable();
            $table->string('data_origin', 30)->default('report_fact');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        // 3. Kebijakan dan kewenangan
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organ_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable();
            $table->string('kind', 30); // anggaran_dasar, board_manual, piagam, kebijakan, regulasi
            $table->string('title');
            $table->string('version', 20)->default('1.0');
            $table->date('effective_date')->nullable();
            $table->date('review_due')->nullable();
            $table->string('status', 20)->default('berlaku'); // draf, berlaku, dicabut
            $table->text('summary')->nullable();
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('authority_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('matter');
            $table->string('threshold')->nullable();
            $table->string('proposer_organ', 20)->default('direksi');
            $table->string('approver', 30); // direksi, dekom_persetujuan, dekom_nasihat, rups
            $table->string('basis')->nullable();
            $table->text('notes')->nullable();
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('meeting_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('organ_kind', 20);
            $table->string('title');
            $table->string('metric', 30); // min_meetings_year, min_joint_meetings_year, material_days, attendance_pct
            $table->decimal('regulatory_value', 8, 2);
            $table->decimal('internal_value', 8, 2)->nullable();
            $table->string('basis');
            $table->timestamps();
        });

        // 4. Rencana kerja tahunan
        Schema::create('work_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organ_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('kind', 30); // rapat, program_pengawasan, kunjungan_kerja, pelatihan, laporan
            $table->string('title');
            $table->string('recurrence', 20)->nullable();
            $table->string('status', 20)->default('direncanakan'); // direncanakan, terlaksana, ditunda, batal
            $table->foreignId('meeting_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 18. Pustaka dokumen (dipakai board book, ruang baca, dan AI)
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organ_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('previous_version_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('series_key', 60)->nullable();
            $table->unsignedSmallInteger('version_no')->default(1);
            $table->string('title');
            $table->string('kind', 30); // laporan_tahunan, kertas_usulan, laporan_kinerja, kebijakan, risalah, bukti, kajian, lainnya
            $table->string('classification', 20)->default('rahasia'); // internal, rahasia, sangat_rahasia
            $table->string('data_origin', 30)->default('simulated');
            $table->string('source_ref')->nullable();
            $table->string('file_path')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->unsignedSmallInteger('page_count')->default(0);
            $table->text('change_summary')->nullable();
            $table->boolean('ai_allowed')->default(true);
            $table->boolean('legal_hold')->default(false);
            $table->date('retention_until')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('document_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('page_no');
            $table->string('heading')->nullable();
            $table->longText('content');
            $table->unique(['document_id', 'page_no']);
        });

        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            Schema::table('document_pages', fn (Blueprint $table) => $table->fullText(['heading', 'content']));
        }

        Schema::create('document_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('valid_until')->nullable();
            $table->timestamps();
        });

        // 5–6. Rapat, peserta, agenda
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organ_id')->constrained()->cascadeOnDelete();
            $table->string('number', 60)->nullable();
            $table->string('title');
            $table->string('kind', 20)->default('rutin'); // rutin, khusus, gabungan
            $table->string('mode', 20)->default('hybrid'); // luring, daring, hybrid
            $table->dateTime('scheduled_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->string('online_link')->nullable();
            $table->string('status', 20)->default('draf');
            $table->foreignId('chair_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('secretary_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('materials_due_at')->nullable();
            $table->dateTime('materials_sent_at')->nullable();
            $table->boolean('is_closed_session')->default(false);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organ_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 20)->default('anggota'); // anggota, undangan, presenter, sekretaris
            $table->boolean('has_vote')->default(true);
            $table->string('rsvp', 20)->default('belum'); // belum, hadir, tidak_hadir, ragu
            $table->string('rsvp_note')->nullable();
            $table->boolean('attended')->nullable();
            $table->string('attendance_mode', 20)->nullable(); // luring, daring
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->string('absence_reason')->nullable();
            $table->dateTime('access_until')->nullable();
            $table->timestamps();
            $table->unique(['meeting_id', 'user_id']);
        });

        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('seq');
            $table->string('title');
            $table->string('purpose', 20)->default('diskusi'); // informasi, diskusi, keputusan, persetujuan, nasihat
            $table->string('deciding_organ', 20)->nullable(); // direksi, dekom, komite
            $table->foreignId('presenter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('duration_min')->default(30);
            $table->string('classification', 20)->default('rahasia');
            $table->text('objective')->nullable();
            $table->text('background')->nullable();
            $table->text('alternatives')->nullable();
            $table->text('financial_impact')->nullable();
            $table->text('risks')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('authority_basis')->nullable();
            $table->dateTime('materials_submitted_at')->nullable();
            $table->longText('discussion')->nullable();
            $table->unsignedSmallInteger('actual_minutes')->nullable();
            $table->string('status', 20)->default('direncanakan'); // direncanakan, dibahas, ditunda, selesai
            $table->timestamps();
        });

        Schema::create('agenda_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('bahan'); // bahan, lampiran, referensi
            $table->unique(['agenda_item_id', 'document_id']);
        });

        // 7. Board book
        Schema::create('board_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version_no');
            $table->string('status', 20)->default('draf'); // draf, terbit, digantikan
            $table->json('manifest')->nullable();
            $table->char('manifest_hash', 64)->nullable();
            $table->text('change_log')->nullable();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->unique(['meeting_id', 'version_no']);
        });

        Schema::create('board_book_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('opened_at');
            $table->unique(['board_book_id', 'user_id']);
        });

        // 8. Ruang baca digital
        Schema::create('annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('page_no');
            $table->string('kind', 20)->default('catatan'); // catatan, penanda, pertanyaan
            $table->string('visibility', 20)->default('pribadi'); // pribadi, bersama
            $table->string('quote')->nullable();
            $table->text('body')->nullable();
            $table->timestamps();
        });

        // 9–10. Konflik kepentingan, keputusan, voting
        Schema::create('conflict_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('nature');
            $table->boolean('recused')->default(true);
            $table->boolean('counts_for_quorum')->default(false);
            $table->dateTime('declared_at');
            $table->timestamps();
        });

        Schema::create('circular_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organ_id')->constrained()->cascadeOnDelete();
            $table->string('number', 60)->nullable();
            $table->string('title');
            $table->longText('body');
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('circulated_at')->nullable();
            $table->dateTime('deadline_at');
            $table->string('status', 20)->default('draf'); // draf, beredar, disetujui, ditolak, kedaluwarsa
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('circular_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('circular_resolution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('response', 20)->default('menunggu'); // menunggu, setuju, tidak_setuju
            $table->text('note')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->unique(['circular_resolution_id', 'user_id']);
        });

        // 13. Register keputusan
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organ_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agenda_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('circular_resolution_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supersedes_id')->nullable()->constrained('decisions')->nullOnDelete();
            $table->string('number', 60)->nullable();
            $table->string('kind', 30); // keputusan_direksi, persetujuan_dekom, nasihat_dekom, rekomendasi_komite
            $table->string('title');
            $table->longText('text');
            $table->string('method', 20)->default('musyawarah'); // musyawarah, voting, sirkuler
            $table->string('result', 30)->default('disetujui'); // disetujui, disetujui_bersyarat, ditolak, ditunda
            $table->text('conditions')->nullable();
            $table->string('basis')->nullable();
            $table->date('decided_on');
            $table->date('effective_on')->nullable();
            $table->string('status', 20)->default('berlaku'); // berlaku, diubah, dicabut
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('decision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vote', 20); // setuju, tidak_setuju, abstain
            $table->text('dissent_note')->nullable();
            $table->timestamps();
            $table->unique(['decision_id', 'user_id']);
        });

        // 12. Risalah dan pengesahan
        Schema::create('minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('corrects_id')->nullable()->constrained('minutes')->nullOnDelete();
            $table->unsignedSmallInteger('version_no')->default(1);
            $table->string('kind', 20)->default('risalah'); // risalah, addendum
            $table->string('status', 20)->default('draf'); // draf, reviu, edar_ttd, final
            $table->longText('body');
            $table->text('correction_reason')->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->foreignId('drafted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('circulated_at')->nullable();
            $table->dateTime('finalized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('minute_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('minute_id')->constrained('minutes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('menunggu'); // menunggu, ditandatangani, menolak
            $table->text('reason')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->string('provider_ref')->nullable();
            $table->unique(['minute_id', 'user_id']);
        });

        // 14. Tindak lanjut
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('decision_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agenda_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30)->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('pic_id')->constrained('users');
            $table->foreignId('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date');
            $table->date('original_due_date')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('status', 20)->default('belum_mulai'); // belum_mulai, berjalan, diajukan, selesai, dibuka_kembali
            $table->string('priority', 10)->default('sedang');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->unsignedTinyInteger('rejection_count')->default(0);
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('task_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->boolean('is_done')->default(false);
            $table->timestamps();
        });

        Schema::create('task_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitted_by')->constrained('users');
            $table->text('description');
            $table->string('file_path')->nullable();
            $table->string('status', 20)->default('diajukan'); // diajukan, diterima, ditolak
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('task_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 40);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // 15. Permintaan informasi
        Schema::create('information_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organ_id')->constrained();
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agenda_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('page_no')->nullable();
            $table->string('code', 30)->nullable();
            $table->string('subject');
            $table->text('question');
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('pic_id')->constrained('users');
            $table->string('priority', 10)->default('sedang');
            $table->dateTime('sla_due_at');
            $table->string('status', 20)->default('diajukan'); // diajukan, dijawab, klarifikasi, diterima
            $table->dateTime('answered_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('information_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('information_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('kind', 20); // pertanyaan, jawaban, klarifikasi, penerimaan
            $table->text('body');
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // 16. Pengawasan kinerja
        Schema::create('financial_facts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 40);
            $table->string('label');
            $table->string('period', 20); // 2025, 2024, 2026-Q1
            $table->string('period_type', 10)->default('tahun'); // tahun, kuartal
            $table->decimal('value', 20, 4);
            $table->string('unit', 20)->default('Rp juta');
            $table->string('data_origin', 30)->default('report_fact');
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_page', 20)->nullable();
            $table->boolean('needs_clarification')->default(false);
            $table->text('quality_note')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'metric', 'period']);
        });

        // 17. Risiko dan kepatuhan
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('title');
            $table->string('category', 30);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('likelihood');
            $table->unsignedTinyInteger('impact');
            $table->string('trend', 10)->default('tetap');
            $table->string('kri_name')->nullable();
            $table->string('kri_value')->nullable();
            $table->string('kri_threshold')->nullable();
            $table->string('kri_status', 10)->default('hijau');
            $table->text('mitigation')->nullable();
            $table->string('status', 20)->default('dipantau');
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('compliance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // kewajiban, temuan
            $table->string('title');
            $table->string('basis')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('terbuka'); // terbuka, dalam_perbaikan, selesai, terlambat
            $table->string('severity', 10)->default('sedang');
            $table->text('notes')->nullable();
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        // 19. Evaluasi dan pengembangan
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organ_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('kind', 30); // penilaian_mandiri, penilaian_komite, survei_rapat, kompetensi
            $table->string('title');
            $table->string('status', 20)->default('berjalan');
            $table->unsignedSmallInteger('respondents')->default(0);
            $table->unsignedSmallInteger('invited')->default(0);
            $table->json('scores')->nullable(); // aspek => skor 1-5
            $table->text('summary')->nullable();
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('provider')->nullable();
            $table->string('kind', 20)->default('pelatihan'); // onboarding, pelatihan, seminar, sertifikasi
            $table->date('held_on');
            $table->decimal('hours', 5, 1)->default(0);
            $table->string('data_origin', 30)->default('simulated');
            $table->timestamps();
        });

        Schema::create('declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('kind', 30); // independensi, benturan_kepentingan_tahunan, kepemilikan_saham, rangkap_jabatan
            $table->string('status', 20)->default('menunggu'); // menunggu, disampaikan
            $table->text('content')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
        });

        // 21. Asisten AI
        Schema::create('ai_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 40);
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('prompt');
            $table->json('context_refs')->nullable();
            $table->json('output')->nullable();
            $table->string('status', 30); // berhasil, bukti_tidak_cukup, timeout, gagal, referensi_demo
            $table->string('model', 60)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('error')->nullable();
            $table->boolean('is_draft')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('feature', 40);
            $table->string('title');
            $table->text('prompt');
            $table->string('subject_type', 40)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('output');
            $table->string('status', 30)->default('referensi_demo');
            $table->timestamps();
        });

        // 22. Audit
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->json('properties')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('demo_scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('stage', 60);
            $table->string('title');
            $table->text('steps');
            $table->text('expected');
            $table->string('route_hint')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'demo_scenarios', 'audit_logs', 'ai_scenarios', 'ai_interactions', 'declarations', 'trainings', 'evaluations',
            'compliance_items', 'risks', 'financial_facts', 'information_request_messages', 'information_requests',
            'task_events', 'task_evidences', 'task_milestones', 'tasks', 'minute_signatures', 'minutes', 'votes', 'decisions',
            'circular_responses', 'circular_resolutions', 'conflict_declarations', 'annotations', 'board_book_reads', 'board_books',
            'agenda_documents', 'agenda_items', 'meeting_participants', 'meetings', 'document_grants', 'document_pages', 'documents',
            'work_plan_items', 'meeting_rules', 'authority_rules', 'policies', 'historical_officials', 'board_profiles', 'memberships',
            'organs', 'companies',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
