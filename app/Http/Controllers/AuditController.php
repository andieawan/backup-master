<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $t = str_replace(['%', '_'], ['\\%', '\\_'], $q);

        return view('audit', ['list' => AuditLog::when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('aksi', 'like', "%$t%")->orWhere('pelaku', 'like', "%$t%")->orWhere('rincian', 'like', "%$t%")))
            ->orderByDesc('id')->paginate(100)->withQueryString(), 'q' => $q]);
    }
}
