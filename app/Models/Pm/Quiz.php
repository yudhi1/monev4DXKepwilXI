<?php

namespace App\Models\Pm;

use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $table = 'pm_quizzes';

    protected $fillable = [
        'judul', 'deskripsi', 'unit_kerja_id', 'dibuat_oleh', 'status', 'sasaran_tipe',
        'durasi_menit', 'acak_soal', 'acak_opsi', 'jumlah_soal',
        'nilai_lulus', 'maks_percobaan', 'tampilkan_pembahasan',
    ];

    protected $casts = [
        'acak_soal' => 'boolean',
        'acak_opsi' => 'boolean',
        'tampilkan_pembahasan' => 'boolean',
    ];

    public function soals(): HasMany
    {
        return $this->hasMany(QuizSoal::class, 'quiz_id')->orderBy('urutan');
    }

    public function percobaans(): HasMany
    {
        return $this->hasMany(QuizPercobaan::class, 'quiz_id');
    }

    /** Baris sasaran; kosong bila quiz ditujukan untuk semua orang. */
    public function sasarans(): HasMany
    {
        return $this->hasMany(QuizSasaran::class, 'quiz_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
    }

    /** Quiz yang boleh dikerjakan peserta. */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', 'terbit');
    }

    /**
     * Berapa soal yang akan keluar dalam satu percobaan.
     *
     * `jumlah_soal` boleh melebihi isi bank soal — misalnya diisi 20 padahal
     * soalnya baru 12. Dibatasi di sini supaya tidak ada percobaan yang
     * menjanjikan soal lebih banyak daripada yang benar-benar ada.
     */
    public function jumlahSoalDipakai(): int
    {
        $tersedia = $this->soals()->count();

        return $this->jumlah_soal ? min($this->jumlah_soal, $tersedia) : $tersedia;
    }

    /**
     * Apakah quiz ini ditujukan kepada seseorang.
     *
     * Sasaran hanya menentukan siapa PESERTANYA. Pengelola quiz tetap bisa
     * membuka dan mencoba quiz buatannya sendiri walau tidak termasuk sasaran
     * — pemeriksaannya ada di QuizPolicy, bukan di sini.
     */
    public function menyasar(User $user): bool
    {
        if ($this->sasaran_tipe === 'semua') {
            return true;
        }

        $daftar = $this->sasarans->pluck('sasaran_id');

        return match ($this->sasaran_tipe) {
            'cabang' => $user->cabang_id !== null && $daftar->contains($user->cabang_id),
            'unit_kerja' => $user->unit_kerja_id !== null && $daftar->contains($user->unit_kerja_id),
            'pegawai' => $daftar->contains($user->id),
            default => false,
        };
    }

    /**
     * Quiz yang menyasar seorang peserta.
     *
     | Disaring di query, bukan setelah data diambil: daftar quiz akan terus
     | bertambah dan menyaringnya di PHP berarti setiap pegawai selalu memuat
     | seluruh quiz yang pernah dibuat siapa pun.
     */
    public function scopeUntukPeserta(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('sasaran_tipe', 'semua');

            $q->orWhere(fn (Builder $sub) => $sub
                ->where('sasaran_tipe', 'cabang')
                ->when(
                    $user->cabang_id,
                    fn ($x) => $x->whereHas('sasarans', fn ($s) => $s->where('sasaran_id', $user->cabang_id)),
                    fn ($x) => $x->whereRaw('1 = 0'),
                ));

            $q->orWhere(fn (Builder $sub) => $sub
                ->where('sasaran_tipe', 'unit_kerja')
                ->when(
                    $user->unit_kerja_id,
                    fn ($x) => $x->whereHas('sasarans', fn ($s) => $s->where('sasaran_id', $user->unit_kerja_id)),
                    fn ($x) => $x->whereRaw('1 = 0'),
                ));

            $q->orWhere(fn (Builder $sub) => $sub
                ->where('sasaran_tipe', 'pegawai')
                ->whereHas('sasarans', fn ($s) => $s->where('sasaran_id', $user->id)));
        });
    }

    /**
     * Pegawai yang menjadi sasaran quiz ini.
     *
     * Dipakai papan peringkat untuk menghitung siapa yang belum mengerjakan —
     * angka itu hanya bermakna kalau penyebutnya orang yang memang disasar.
     *
     * @return Builder<User>
     */
    public function pesertaSasaran(): Builder
    {
        $query = User::query()->pegawai()->where('is_active', true);
        $daftar = $this->sasarans->pluck('sasaran_id');

        return match ($this->sasaran_tipe) {
            'cabang' => $query->whereIn('cabang_id', $daftar),
            'unit_kerja' => $query->whereIn('unit_kerja_id', $daftar),
            'pegawai' => $query->whereIn('id', $daftar),
            default => $query,
        };
    }

    /** Keterangan singkat sasaran untuk ditampilkan di layar. */
    public function labelSasaran(): string
    {
        if ($this->sasaran_tipe === 'semua') {
            return 'Semua pegawai';
        }

        $jumlah = $this->sasarans->count();

        return match ($this->sasaran_tipe) {
            'cabang' => $jumlah.' kantor cabang',
            'unit_kerja' => $jumlah.' bidang',
            'pegawai' => $jumlah.' pegawai',
            default => '—',
        };
    }

    /** Quiz tanpa soal tidak boleh diterbitkan — pesertanya tidak dapat apa-apa. */
    public function siapTerbit(): bool
    {
        return $this->soals()->count() > 0;
    }
}
