<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Sdmmodels;
use App\Models\PegawaiDiklat;
use App\Models\UnitKerja;

/**
 * PKR-02 -- Riwayat Diklat. Admin Unit boleh input/edit/hapus data diklat pegawai DI
 * UNITNYA SENDIRI (bukan cuma Admin Pusbin) -- setiap query WAJIB di-scope server-side,
 * lihat unitScopeQuery()/pastikanAksesSdm(). Pola scoping direuse persis dari
 * UjikomPendaftaranController (users.unit_kerja_id), lihat CHANGELOG untuk detail diagnostik.
 */
class PegawaiDiklatController extends Controller
{
    private const ROLE_TULIS = ['admin', 'super_admin', 'admin_unit'];

    /** Terapkan scope unit_kerja Admin Unit ke query builder ($column = kolom unit_kerja_id di query). */
    private function scopeUnit($query, string $column = 'unit_kerja_id')
    {
        $user = auth()->user();
        if ($user->hasRole('admin_unit')) {
            $query->where($column, $user->unit_kerja_id);
        }
        return $query;
    }

    /**
     * Pastikan Admin Unit yang login berhak akses SDM ini (unit sama). WAJIB dipanggil di
     * SETIAP method yang menyentuh data 1 pegawai spesifik (riwayat/create/store/edit/update) --
     * validasi server-side, BUKAN cuma sembunyikan tombol UI.
     */
    private function pastikanAksesSdm(Sdmmodels $sdm): void
    {
        $user = auth()->user();
        if ($user->hasRole('admin_unit') && (int) $sdm->unit_kerja_id !== (int) $user->unit_kerja_id) {
            abort(403, 'Anda tidak berhak mengakses data pegawai di unit kerja lain.');
        }
    }

    /** Pastikan Admin Unit berhak akses entri diklat ini (lewat unit pegawai pemiliknya). */
    private function pastikanAksesDiklat(PegawaiDiklat $diklat): void
    {
        $this->pastikanAksesSdm($diklat->sdm);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $unitQuery = UnitKerja::orderBy('nama_unit_kerja');
        if ($user->hasRole('admin_unit')) {
            $unitQuery->where('id', $user->unit_kerja_id);
        }
        $unitKerjaList = $unitQuery->get(['id', 'nama_unit_kerja']);

        $belumDiklatCount = $this->hitungBelumDiklat();

        return view('diklat.index', [
            'unitKerjaList' => $unitKerjaList,
            'belumDiklatCount' => $belumDiklatCount,
            'unitTerkunci' => $user->hasRole('admin_unit') ? $user->unit_kerja_id : null,
            'daftarJenis' => ['teknis' => 'Teknis', 'fungsional' => 'Fungsional', 'kepemimpinan' => 'Kepemimpinan', 'lainnya' => 'Lainnya'],
        ]);
    }

    /**
     * Endpoint AJAX server-side DataTables -- SEJAK AWAL server-side (bukan client-side lalu
     * diperbaiki belakangan seperti PKR-01). Satu baris = 1 pegawai (agregat), bukan 1 entri
     * diklat -- "jumlah diklat" & "jenis terakhir" dihitung batch HANYA utk baris hasil
     * paginasi (bukan query per-SDM), pola sama dgn PkrController::data() (PKR-01 Bagian 3).
     */
    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = max(1, (int) $request->input('length', 25));
        $searchValue = trim((string) $request->input('search.value', ''));

        $baseQuery = Sdmmodels::query()->where('aktif', 1);
        $this->scopeUnit($baseQuery);

        if ($request->filled('unit_kerja_id')) {
            $baseQuery->where('unit_kerja_id', $request->input('unit_kerja_id'));
        }
        if ($searchValue !== '') {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->where('nama_lengkap', 'like', "%{$searchValue}%")
                  ->orWhere('nip', 'like', "%{$searchValue}%");
            });
        }
        if ($request->filled('jenis_diklat')) {
            $jenis = $request->input('jenis_diklat');
            $baseQuery->whereExists(function ($q) use ($jenis) {
                $q->selectRaw('1')->from('pegawai_diklat')
                  ->whereColumn('pegawai_diklat.sdm_id', 'sumber_daya_manusia.id')
                  ->where('pegawai_diklat.jenis_diklat', $jenis);
            });
        }

        $recordsTotal = (clone $baseQuery)->count();
        $recordsFiltered = $recordsTotal;

        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';
        $kolomUrut = [0 => 'nama_lengkap', 1 => 'nip'];
        $baseQuery->orderBy($kolomUrut[$orderColumnIndex] ?? 'nama_lengkap', $orderDir);

        $page = $baseQuery->with('unitKerja')->skip($start)->take($length)->get();

        $sdmIds = $page->pluck('id');
        $jumlahMap = DB::table('pegawai_diklat')->whereIn('sdm_id', $sdmIds)
            ->selectRaw('sdm_id, COUNT(*) as jumlah')->groupBy('sdm_id')->pluck('jumlah', 'sdm_id');

        $maxTanggalPerSdm = DB::table('pegawai_diklat')->whereIn('sdm_id', $sdmIds)
            ->selectRaw('sdm_id, MAX(tanggal_mulai) as max_tanggal')->groupBy('sdm_id')->get()
            ->keyBy('sdm_id');
        $terakhirMap = [];
        foreach ($maxTanggalPerSdm as $sdmId => $row) {
            $terakhir = DB::table('pegawai_diklat')->where('sdm_id', $sdmId)->where('tanggal_mulai', $row->max_tanggal)->first();
            $terakhirMap[$sdmId] = $terakhir->jenis_diklat ?? null;
        }

        $labelJenis = ['teknis' => 'Teknis', 'fungsional' => 'Fungsional', 'kepemimpinan' => 'Kepemimpinan', 'lainnya' => 'Lainnya'];

        $rows = $page->map(function ($sdm) use ($jumlahMap, $terakhirMap, $labelJenis) {
            $jenisTerakhir = $terakhirMap[$sdm->id] ?? null;
            return [
                'nama_lengkap' => e($sdm->nama_lengkap),
                'nip' => e($sdm->nip ?? '-'),
                'unit_kerja' => e($sdm->unitKerja->nama_unit_kerja ?? '-'),
                'jenis_terakhir' => $jenisTerakhir ? e($labelJenis[$jenisTerakhir] ?? $jenisTerakhir) : '<span class="text-muted">-</span>',
                'jumlah' => (int) ($jumlahMap[$sdm->id] ?? 0),
                'aksi' => '<a href="' . route('karir.diklat.riwayat', $sdm->id) . '" class="btn btn-sm btn-outline-primary" title="Riwayat Diklat"><i class="fas fa-graduation-cap"></i></a>',
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->values(),
        ]);
    }

    /** Riwayat diklat 1 pegawai (entry point dari tombol "Diklat" di sdm.index). */
    public function riwayat($sdmId)
    {
        $sdm = Sdmmodels::with('unitKerja')->findOrFail($sdmId);
        $this->pastikanAksesSdm($sdm);

        $riwayat = PegawaiDiklat::where('sdm_id', $sdmId)
            ->with('inputBy')
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get();

        return view('diklat.riwayat', compact('sdm', 'riwayat'));
    }

    public function create(Request $request)
    {
        $this->otorisasiTulis();

        $sdmTerpilih = null;
        if ($request->filled('sdm_id')) {
            $sdmTerpilih = Sdmmodels::with('unitKerja')->find($request->input('sdm_id'));
            if ($sdmTerpilih) {
                $this->pastikanAksesSdm($sdmTerpilih);
            }
        }

        return view('diklat.form', [
            'mode' => 'create',
            'item' => null,
            'pegawaiList' => collect(),
            'sdmTerpilih' => $sdmTerpilih,
            'daftarJenis' => ['teknis' => 'Teknis', 'fungsional' => 'Fungsional', 'kepemimpinan' => 'Kepemimpinan', 'lainnya' => 'Lainnya'],
        ]);
    }

    /**
     * AJAX Select2 (search-as-you-type) utk dropdown pegawai -- BUKAN dump semua pegawai
     * scoped sekaligus ke <select> (sempat begitu, payload 630KB utk super_admin/3.940
     * pegawai tanpa filter unit -- pelajaran sama dgn PKR-01: jangan render semua di awal).
     * Pola AJAX-nya mengikuti UjikomPendaftaranController::getPegawai(), TAPI scope unit
     * SELALU dipaksa server-side dari role user login (bukan dari parameter request),
     * beda dari pola aslinya yang percaya unit_kerja_id kiriman client.
     */
    public function pegawaiOptions(Request $request)
    {
        $query = Sdmmodels::where('aktif', 1)->with('unitKerja');
        $this->scopeUnit($query);

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $results = $query->orderBy('nama_lengkap')->limit(50)->get()->map(fn ($p) => [
            'id' => $p->id,
            'text' => "{$p->nama_lengkap} — {$p->nip} (" . ($p->unitKerja->nama_unit_kerja ?? '-') . ')',
        ]);

        return response()->json(['results' => $results]);
    }

    public function store(Request $request)
    {
        $this->otorisasiTulis();

        $validated = $request->validate([
            'sdm_id' => 'required|exists:sumber_daya_manusia,id',
            'nama_diklat' => 'required|string|max:255',
            'penyelenggara' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis_diklat' => 'required|in:teknis,fungsional,kepemimpinan,lainnya',
            'sertifikat' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $sdm = Sdmmodels::findOrFail($validated['sdm_id']);
        $this->pastikanAksesSdm($sdm);

        $path = $request->file('sertifikat')->store('diklat/sertifikat', 'public');

        PegawaiDiklat::create([
            'sdm_id' => $sdm->id,
            'nama_diklat' => $validated['nama_diklat'],
            'penyelenggara' => $validated['penyelenggara'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'jenis_diklat' => $validated['jenis_diklat'],
            'path_sertifikat' => $path,
            'input_by' => auth()->id(),
        ]);

        return redirect()->route('karir.diklat.riwayat', $sdm->id)->with('success', 'Data diklat berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $this->otorisasiTulis();

        $diklat = PegawaiDiklat::with('sdm')->findOrFail($id);
        $this->pastikanAksesDiklat($diklat);

        $daftarJenis = ['teknis' => 'Teknis', 'fungsional' => 'Fungsional', 'kepemimpinan' => 'Kepemimpinan', 'lainnya' => 'Lainnya'];

        return view('diklat.form', [
            'mode' => 'edit',
            'item' => $diklat,
            'pegawaiList' => collect([$diklat->sdm]),
            'sdmTerpilih' => $diklat->sdm,
            'daftarJenis' => $daftarJenis,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->otorisasiTulis();

        $diklat = PegawaiDiklat::with('sdm')->findOrFail($id);
        $this->pastikanAksesDiklat($diklat);

        $validated = $request->validate([
            'nama_diklat' => 'required|string|max:255',
            'penyelenggara' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis_diklat' => 'required|in:teknis,fungsional,kepemimpinan,lainnya',
            'sertifikat' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $payload = [
            'nama_diklat' => $validated['nama_diklat'],
            'penyelenggara' => $validated['penyelenggara'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'jenis_diklat' => $validated['jenis_diklat'],
        ];

        if ($request->hasFile('sertifikat')) {
            $pathLama = $diklat->path_sertifikat;
            $payload['path_sertifikat'] = $request->file('sertifikat')->store('diklat/sertifikat', 'public');
            $diklat->update($payload);
            if ($pathLama) {
                Storage::disk('public')->delete($pathLama);
            }
        } else {
            $diklat->update($payload);
        }

        return redirect()->route('karir.diklat.riwayat', $diklat->sdm_id)->with('success', 'Data diklat berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $this->otorisasiTulis();

        $diklat = PegawaiDiklat::with('sdm')->findOrFail($id);
        $this->pastikanAksesDiklat($diklat);

        $sdmId = $diklat->sdm_id;
        $path = $diklat->path_sertifikat;
        $diklat->delete();
        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('karir.diklat.riwayat', $sdmId)->with('success', 'Data diklat berhasil dihapus.');
    }

    /** Rekapitulasi jumlah diklat per unit kerja + breakdown jenis -- SQL agregat murni. */
    public function rekapPerUnit()
    {
        $query = DB::table('pegawai_diklat as pd')
            ->join('sumber_daya_manusia as s', 's.id', '=', 'pd.sdm_id')
            ->whereNull('s.deleted_at');
        $this->scopeUnit($query, 's.unit_kerja_id');

        $rows = $query->selectRaw('s.unit_kerja_id, pd.jenis_diklat, COUNT(*) as jumlah')
            ->groupBy('s.unit_kerja_id', 'pd.jenis_diklat')
            ->get();

        $unitMap = UnitKerja::whereIn('id', $rows->pluck('unit_kerja_id')->unique())->pluck('nama_unit_kerja', 'id');

        $rekap = [];
        foreach ($rows as $r) {
            if (!isset($rekap[$r->unit_kerja_id])) {
                $rekap[$r->unit_kerja_id] = [
                    'unit_kerja_id' => $r->unit_kerja_id,
                    'nama_unit_kerja' => $unitMap[$r->unit_kerja_id] ?? "Unit Kerja #{$r->unit_kerja_id}",
                    'total' => 0,
                    'per_jenis' => ['teknis' => 0, 'fungsional' => 0, 'kepemimpinan' => 0, 'lainnya' => 0],
                ];
            }
            $rekap[$r->unit_kerja_id]['total'] += (int) $r->jumlah;
            $rekap[$r->unit_kerja_id]['per_jenis'][$r->jenis_diklat] = (int) $r->jumlah;
        }

        usort($rekap, fn ($a, $b) => $b['total'] <=> $a['total']);

        return view('diklat.rekap', ['rekap' => array_values($rekap)]);
    }

    /** Pegawai yang TIDAK PERNAH punya entri diklat sama sekali -- NOT EXISTS, bukan loop PHP. */
    public function pegawaiBelumDiklat(Request $request)
    {
        $query = Sdmmodels::where('aktif', 1)
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('pegawai_diklat')
                  ->whereColumn('pegawai_diklat.sdm_id', 'sumber_daya_manusia.id');
            })
            ->with('unitKerja');
        $this->scopeUnit($query);

        if ($request->filled('unit_kerja_id')) {
            $query->where('unit_kerja_id', $request->input('unit_kerja_id'));
        }

        $pegawai = $query->orderBy('nama_lengkap')->paginate(50)->withQueryString();

        $unitQuery = UnitKerja::orderBy('nama_unit_kerja');
        $this->scopeUnit($unitQuery, 'id');
        $unitKerjaList = $unitQuery->get(['id', 'nama_unit_kerja']);

        return view('diklat.belum', compact('pegawai', 'unitKerjaList'));
    }

    private function hitungBelumDiklat(): int
    {
        $query = Sdmmodels::where('aktif', 1)
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')->from('pegawai_diklat')
                  ->whereColumn('pegawai_diklat.sdm_id', 'sumber_daya_manusia.id');
            });
        $this->scopeUnit($query);

        return $query->count();
    }

    private function otorisasiTulis(): void
    {
        abort_unless(auth()->user()->hasAnyRole(self::ROLE_TULIS), 403);
    }
}
