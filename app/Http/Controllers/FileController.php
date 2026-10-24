<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Task;
use App\Models\TaskEvidence;
use App\Support\Access;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Unduhan berkas selalu melalui pemeriksaan hak akses; berkas disimpan di luar folder publik. */
class FileController
{
    public function document(Request $request, Document $document)
    {
        $user = $request->user();

        if (! Access::canReadDocument($user, $document)) {
            Access::deny($user, 'mengunduh dokumen "'.$document->title.'"', $document);
            abort(403);
        }

        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);
        Audit::log('dokumen_unduh', 'Mengunduh "'.$document->title.'" v'.$document->version_no, $document);

        return Storage::disk('local')->response($document->file_path, str($document->title)->slug().'-v'.$document->version_no.'.pdf');
    }

    public function evidence(Request $request, TaskEvidence $evidence)
    {
        $user = $request->user();

        if (! Access::scopeTasks(Task::query(), $user)->whereKey($evidence->task_id)->exists()) {
            Access::deny($user, 'mengunduh bukti tugas '.$evidence->task->code, $evidence->task);
            abort(403);
        }

        abort_unless($evidence->file_path && Storage::disk('local')->exists($evidence->file_path), 404);

        return Storage::disk('local')->download($evidence->file_path);
    }
}
