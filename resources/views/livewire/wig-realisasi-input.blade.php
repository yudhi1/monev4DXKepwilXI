<div>
    <h3>Input Realisasi WIG Bulanan</h3>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-5">
                    <label class="form-label small">Pilih WIG</label>
                    <select wire:model.live="wig_id" class="form-select form-select-sm">
                        <option value="">- Pilih WIG -</option>
                        @foreach ($wigs as $w)
                            <option value="{{ $w->id }}" title="{{ $w->nama_wig }}">
                                {{ $w->kode_wig }} — {{ \Illuminate\Support\Str::limit($w->nama_wig, 70) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small">Kantor Cabang</label>
                    <select wire:model.live="cabang_id" class="form-select form-select-sm"
                        @if(auth()->user()?->hasRole('kantor_cabang')) disabled @endif>
                        <option value="">- Pilih Cabang -</option>
                        @foreach ($cabangs as $c)
                            <option value="{{ $c->id }}">{{ $c->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Tahun</label>
                    <input type="number" wire:model.live="tahun" class="form-control form-control-sm" min="2000" max="2100">
                </div>
            </div>
        </div>
    </div>

    @if ($wig_id && $cabang_id)
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <small class="text-muted d-block">Nilai Awal</small>
                        <strong>{{ number_format((float) ($target->nilai_awal ?? 0), 2, ',', '.') }}</strong>
                        <span class="text-muted small">{{ $target->satuan ?? '' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Nilai Target</small>
                        <strong>{{ number_format((float) ($target->nilai_target ?? 0), 2, ',', '.') }}</strong>
                        <span class="text-muted small">{{ $target->satuan ?? '' }}</span>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Total Realisasi {{ $tahun }}</small>
                        <strong>{{ number_format($totalRealisasi, 2, ',', '.') }}</strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block">Progres Tahun</small>
                        <div class="progress" style="height: 22px;">
                            <div class="progress-bar bg-{{ $progres >= 100 ? 'success' : ($progres >= 70 ? 'warning' : 'danger') }}"
                                style="width: {{ min(100, max(0, $progres)) }}%;">{{ $progres }}%</div>
                        </div>
                    </div>
                </div>
                @unless ($target)
                    <div class="alert alert-warning mt-3 mb-0 py-2 small">
                        Target WIG untuk cabang ini belum diisi. Hubungi Kedeputian Wilayah untuk mengisi target terlebih dahulu.
                    </div>
                @endunless
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Realisasi Per Bulan</strong></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Bulan</th>
                                <th style="width:220px">Nilai Realisasi</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bulanLabels as $no => $label)
                                <tr>
                                    <td>{{ $no }}</td>
                                    <td>{{ $label }}</td>
                                    <td x-data="numInput($wire.entangle('rows.{{ $no }}.nilai'), '{{ $target?->satuan ?? 'Rp' }}')">
                                        <input type="text" inputmode="decimal"
                                            :value="display"
                                            @focus="onFocus" @blur="onBlur" @input="onInput"
                                            class="form-control form-control-sm text-end"
                                            @if(!$target) disabled @endif>
                                    </td>
                                    <td>
                                        <input type="text"
                                            wire:model="rows.{{ $no }}.catatan"
                                            class="form-control form-control-sm"
                                            placeholder="Catatan opsional...">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Total</th>
                                <th class="text-end">{{ number_format($totalRealisasi, 2, ',', '.') }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-primary btn-sm"
                        @click="swalConfirm('Simpan realisasi WIG bulanan?', () => $wire.save())">
                        <i class="bi bi-save"></i> Simpan Realisasi
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">Pilih <strong>WIG</strong> dan <strong>Kantor Cabang</strong> dulu untuk mulai input realisasi.</div>
    @endif
</div>
