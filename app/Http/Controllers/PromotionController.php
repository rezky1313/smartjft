<?php

namespace App\Http\Controllers;

use App\Models\{Promotion, PromotionFile, PromotionLog, Sdmmodels, SdmRiwayat, JenjangJabatan, Formasijabatan, Rumahsakit};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;   // ⬅️ tambahkan

//use App\Models\{Sdm, FormasiJabatan, JenjangJabatan, UnitKerja};

class PromotionController extends Controller
{
    public function sdmSearch(Request $r)
{
    $this->authorizeAdmin();
    $q = trim($r->get('q',''));

    $rows = Sdmmodels::with(['unitKerja', 'formasi.jabatan'])
        ->when($q !== '', function ($w) use ($q) {
            $w->where(function ($x) use ($q) {
                $x->where('nama_lengkap','like',"%{$q}%")
                  ->orWhere('nip','like',"%{$q}%")
                  ->orWhere('nik','like',"%{$q}%");
            });
        })
        ->orderBy('nama_lengkap')
        ->limit(20)
        ->get();

    $results = $rows->map(function ($s) {
        $unit   = $s->unitKerja->nama_rumahsakit ?? $s->unitKerja->nama_rumahsakit ?? '-';
        $jenjangNama = optional(optional($s->formasi)->jabatan)->nama_jenjang ?? '-';

        return [
            'id'      => $s->id,
            'text'    => "{$s->nama_lengkap} — ".($s->nip ?: '-')." — {$unit}",
            'nama'    => $s->nama_lengkap,
            'nip'     => $s->nip ?: '-',
            'unit'    => $unit,
            'jenjang' => $jenjangNama,
        ];
    });

    return response()->json(['results' => $results]);
}

    // Asumsi: User punya relasi ke SDM: users.sdm_id
    // protected function currentSdmId(): int {
    //     return (int) (Auth::user()->sdm_id ?? 0);
    // }
    protected function currentSdmId(): int
{
    $u = Auth::user();
    if (!$u) return 0;

    // 1) utama: users.sdm_id
    if (!empty($u->sdm_id)) return (int) $u->sdm_id;

    // 2) fallback: cari di tabel sdm yang punya user_id = users.id
    $sid = \App\Models\Sdmmodels::where('user_id', $u->id)->value('id');

    return (int) ($sid ?? 0);
}


    // ADD — helper admin check (fallback, tanpa Spatie)
    private function isAdmin(): bool
    {
    $u = Auth::user();
    if (!$u) return false;

    // Dukungan beberapa skema yang umum dipakai di proyek lama:
    // - kolom boolean: users.is_admin
    // - kolom string: users.role atau users.level
    $role = $u->role ?? $u->level ?? null;

    return (bool)($u->is_admin ?? false)
        || in_array($role, ['users','user','admin','Admin','superadmin','super_admin'], true);
}


    protected function logStatus(Promotion $p, ?string $from, string $to, ?string $note = null): void {
        PromotionLog::create([
            'promotion_id' => $p->id,
            'from_status'  => $from,
            'to_status'    => $to,
            'actor_id'     => Auth::id(),
            'note'         => $note
        ]);
    }

    public function index(Request $req) {
    $this->authorizeAdmin();
    $isAdmin = true; // semua alur oleh admin
    $rows = Promotion::with(['sdm','jenjangAsal','jenjangTarget'])
            ->latest()->paginate(15);
    return view('promotions.index', compact('rows','isAdmin'));
}


     
public function create() {
    $this->authorizeAdmin();
  
$jenjangList = JenjangJabatan::orderBy('id')->get();
return view('promotions.create', compact('jenjangList'));

}



    public function store(Request $r) {

    $r->validate([
        'sdm_id'            => 'required|exists:sumber_daya_manusia,id',
        'jenjang_target_id' => 'required|exists:jenjang_jabatan,id',
        'sk_terakhir'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
        'skp'               => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
        'sertifikat'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
    ]);

    $this->authorizeAdmin();

    $sdm = Sdmmodels::with('formasi.jabatan')->findOrFail((int)$r->sdm_id);
    $jenjangAsalId = optional(optional($sdm->formasi)->jabatan)->id;

    if (!$jenjangAsalId) {
        return back()->with('err','SDM belum memiliki Jenjang pada Formasi. Lengkapi `formasi_jabatan_id`/`jenjang_jabatan_id` lebih dulu.')
                     ->withInput();
    }

    $p = Promotion::create([
        'sdm_id'            => $sdm->id,
        'jenjang_asal_id'   => $jenjangAsalId,
        'jenjang_target_id' => (int)$r->jenjang_target_id,
        'status'            => Promotion::ST_DRAFT,
        'created_by'        => Auth::id(),
        'updated_by'        => Auth::id(),
    ]);

        foreach (['sk_terakhir','skp','sertifikat'] as $kind) {
            if ($r->hasFile($kind)) {
                $f = $r->file($kind);
                $path = $f->store('promotion_files','public');
                PromotionFile::create([
                    'promotion_id' => $p->id,
                    'kind'         => $kind,
                    'path'         => $path,
                    'original_name'=> $f->getClientOriginalName(),
                    'size'         => $f->getSize(),
                    'mime'         => $f->getMimeType(),
                    'is_valid'     => true,
                ]);
            }
        }

        $this->logStatus($p, null, Promotion::ST_DRAFT, 'Usulan dibuat sebagai DRAFT');
        return redirect()->route('user.promotions.show',$p->id)->with('ok','Draft usulan disimpan.');
    }

    public function show($id) {
   $this->authorizeAdmin();
$p = Promotion::with(['sdm','files','jenjangAsal','jenjangTarget','logs.actor'])->findOrFail($id);
return view('promotions.show', ['p'=>$p, 'isAdmin'=>true]);
    }



    // === Actions ===

    // Pemangku submit
    public function submit($id) {
      $this->authorizeAdmin();
$p = Promotion::with('files')->findOrFail($id); 
if (!in_array($p->status, [Promotion::ST_DRAFT, Promotion::ST_NEED_FIX])) {
    return back()->with('err','Hanya DRAFT/NEED_FIX yang bisa diajukan.');
}


        // Pastikan 3 berkas ada
        $must = ['sk_terakhir','skp','sertifikat'];
        $kinds = $p->files->pluck('kind')->all();
        foreach ($must as $m) if (!in_array($m, $kinds)) return back()->with('err','Berkas wajib belum lengkap.');

        $from = $p->status;
        $p->update([
            'status'       => Promotion::ST_SUBMITTED,
            'submitted_at' => now(),
            'updated_by'   => Auth::id(),
        ]);
        $this->logStatus($p, $from, Promotion::ST_SUBMITTED, 'Diajukan Admin Instansi');
        return back()->with('ok','Usulan diajukan.');
    }

    // Admin kembalikan untuk perbaikan
    public function returnBack(Request $r, $id) {
        $this->authorizeAdmin();
        $r->validate(['note' => 'required|string|min:5']);

        $p = Promotion::findOrFail($id);
        $from = $p->status;
        $p->update(['status'=>Promotion::ST_NEED_FIX,'updated_by'=>Auth::id(),'notes'=>$r->note]);
        $this->logStatus($p,$from,Promotion::ST_NEED_FIX,$r->note);

        return back()->with('ok','Dikembalikan untuk perbaikan.');
    }

    // Admin verifikasi
    public function verify($id) {
        $this->authorizeAdmin();
        $p = Promotion::with('files')->findOrFail($id);
        if ($p->status !== Promotion::ST_SUBMITTED) return back()->with('err','Status bukan SUBMITTED.');

        $from = $p->status;
        $p->update(['status'=>Promotion::ST_VERIFIED,'verified_at'=>now(),'updated_by'=>Auth::id()]);
        $this->logStatus($p,$from,Promotion::ST_VERIFIED,'Lolos verifikasi admin');

        return back()->with('ok','Usulan diverifikasi.');
    }

    // Admin isi SK (opsional di MVP)
    public function approve(Request $r, $id) {
        $this->authorizeAdmin();
        $r->validate([
            'sk_number' => 'required|string|min:3',
            'tmt_sk'    => 'required|date',
            'sk_file'   => 'nullable|file|mimes:pdf|max:4096'
        ]);

        // $p = Promotion::findOrFail($id);
        // if ($p->status !== Promotion::ST_VERIFIED) return back()->with('err','Harus VERIFIED dulu.');
        $p = Promotion::findOrFail($id);
        if ($p->status !== Promotion::ST_VERIFIED) return back()->with('err','Harus VERIFIED dulu.');

        // SEPARATION OF DUTIES
        if ((int)$p->created_by === (int)Auth::id()) {
            return back()->with('err','Approval harus dilakukan oleh admin yang berbeda dari pembuat usulan.');
        }

        $data = [
            'sk_number' => $r->sk_number,
            'tmt_sk'    => $r->tmt_sk,
            'updated_by'=> Auth::id(),
        ];
        if ($r->hasFile('sk_file')) {
            $data['sk_file_path'] = $r->file('sk_file')->store('promotion_sk','public');
        }
        $p->update($data);

        // tidak ganti status; penerapan di step apply
        return back()->with('ok','Nomor Surat tersimpan. Lanjutkan Terapkan.');
    }

    // Admin terapkan (update SDM + histori) — TRANSAKSI
    public function apply($id) {
        $this->authorizeAdmin();
        $p = Promotion::with(['sdm'])->findOrFail($id);
        if ($p->status !== Promotion::ST_VERIFIED) return back()->with('err','Usulan belum VERIFIED.');

        if (!$p->tmt_sk || !$p->sk_number) return back()->with('err','Isi SK number & TMT dahulu.');

        // DB::transaction(function () use ($p) {
        //     // 1) Tutup histori jenjang/formasi lama
        //     SdmRiwayat::where('sdm_id',$p->sdm_id)
        //         ->whereNull('tmt_selesai')
        //         ->update(['tmt_selesai' => $p->tmt_sk]);

        //     // 2) Catat histori baru
        //     SdmRiwayat::create([
        //         'sdm_id'     => $p->sdm_id,
        //         'jenjang_id' => $p->jenjang_target_id,
        //         'formasi_id' => $p->sdm->formasi_id ?? null, // asumsi ada kolom formasi_id di sdm
        //         'tmt_mulai'  => $p->tmt_sk,
        //         'tmt_selesai'=> null,
        //         'reason'     => 'kenaikan_jenjang',
        //     ]);

        //     // 3) Update SDM (JANGAN lepas relasi formasi)
        //     $p->sdm->update([
        //         'jenjang_id'  => $p->jenjang_target_id,
        //         'tmt_jenjang' => $p->tmt_sk,
        //     ]);

        //     // 4) Update usulan -> APPLIED
        //     $from = $p->status;
        //     $p->update([
        //         'status'     => Promotion::ST_APPLIED,
        //         'applied_at' => now(),
        //         'updated_by' => Auth::id(),
        //     ]);
        //     $this->logStatus($p,$from,Promotion::ST_APPLIED,'Perubahan diterapkan ke data SDM');
        // });
        DB::transaction(function () use ($p) {
    // Tutup histori aktif (jika ada)
    SdmRiwayat::where('sdm_id',$p->sdm_id)
        ->whereNull('tmt_selesai')
        ->update(['tmt_selesai' => $p->tmt_sk]);

    // Catat histori baru (pakai formasi_jabatan_id dari SDM saat ini)
    SdmRiwayat::create([
        'sdm_id'              => $p->sdm_id,
        'jenjang_id'          => $p->jenjang_target_id,
        'formasi_jabatan_id'  => $p->sdm->formasi_jabatan_id ?? null,
        'tmt_mulai'           => $p->tmt_sk,
        'tmt_selesai'         => null,
        'reason'              => 'kenaikan_jenjang',
    ]);

    // Tidak mengubah tabel sumber_daya_manusia pada MVP
    $from = $p->status;
    $p->update([
        'status'     => Promotion::ST_APPLIED,
        'applied_at' => now(),
        'updated_by' => Auth::id(),
    ]);
    $this->logStatus($p,$from,Promotion::ST_APPLIED,'Perubahan diterapkan (riwayat dibuat)');
});


        return back()->with('ok','Kenaikan jenjang telah diterapkan.');
    }

  private function authorizeAdmin(): void {
    abort_unless($this->isAdmin(), 403);
}

public function fileInline(int $file)
{
    $this->authorizeAdmin();

    $f = PromotionFile::findOrFail($file);

    // Bangun path absolut ke storage/app/public/...
    $rel = ltrim($f->path, '/\\'); // aman dari leading slash/backslash
    $abs = storage_path('app/public/'.$rel);

    if (!File::exists($abs)) {
        abort(404, 'File tidak ditemukan');
    }

    $mime = $f->mime ?: (File::mimeType($abs) ?: 'application/octet-stream');

    // Tampilkan inline di browser
    return response()->file($abs, [
        'Content-Type'        => $mime,
        'Content-Disposition' => 'inline; filename="'.$f->original_name.'"',
    ]);
}

public function fileDownload(int $file)
{
    $this->authorizeAdmin();

    $f = PromotionFile::findOrFail($file);

    $rel = ltrim($f->path, '/\\');
    $abs = storage_path('app/public/'.$rel);

    if (!File::exists($abs)) {
        abort(404, 'File tidak ditemukan');
    }

    // Paksa unduh
    return response()->download($abs, $f->original_name);
}





}
