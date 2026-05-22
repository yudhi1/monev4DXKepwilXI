<div>
    <h3>Target WIG per Cabang</h3>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small">Pilih WIG</label>
                    <select wire:model.live="wig_id" class="form-select form-select-sm">
                        <option value="">-</option>
                        @foreach ($wigs as $w)
                            <option value="{{ $w->id }}" title="{{ $w->nama_wig }}">
                                [{{ $w->tahun }}] {{ $w->kode_wig }} —
                                {{ \Illuminate\Support\Str::limit($w->nama_wig, 70) }}
                            </option>
                        @endforeach
                    </select>
                    @php $selectedWig = $wigs->firstWhere('id', $wig_id); @endphp
                    @if ($selectedWig)
                        <div class="border rounded p-2 mt-2 bg-light small">
                            <div>
                                <span class="badge bg-primary">{{ $selectedWig->kode_wig }}</span>
                                @if ($selectedWig->bidang)
                                    <span class="badge bg-info">{{ $selectedWig->bidang }}</span>
                                @endif
                            </div>
                            <div class="mt-1 text-muted" style="white-space: pre-wrap; word-break: break-word;">
                                {{ $selectedWig->nama_wig }}</div>
                        </div>
                    @endif
                </div>
                @if ($wig)
                    <div class="col-md-4 text-end">
                        @if ($wig->bidang)
                            <span class="badge bg-info">{{ $wig->bidang }}</span>
                        @endif
                        <span class="badge bg-secondary">Tahun {{ $wig->tahun }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($wig)
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ $wig->nama_wig }}</strong>
                <small class="text-muted">{{ $wig->indikator_output }}</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px">#</th>
                                <th style="width:200px">Kantor Cabang</th>
                                <th style="width:160px">Satuan</th>
                                <th style="width:170px">Nilai Awal</th>
                                <th style="width:170px">Nilai Target</th>
                                <th style="width:170px">Tanggal Target</th>
                                <th style="width:120px" class="text-end">Selisih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $cid => $r)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $r['cabang_nama'] }}</td>

                                    {{-- Dropdown satuan --}}
                                    <td x-data="satuanInput($wire.entangle('rows.{{ $cid }}.satuan'))">
                                        <div class="d-flex gap-1 align-items-center">
                                            <select :value="selectVal" @change="onSelectChange"
                                                class="form-select form-select-sm">
                                                <option value="Rp">Rp</option>
                                                <option value="%">%</option>
                                                <option value="unit">jiwa</option>
                                                <option value="__lainnya__">Lainnya...</option>
                                            </select>
                                            <input x-show="isLainnya" type="text" :value="custom"
                                                @input="onCustomInput" placeholder="isi satuan"
                                                class="form-control form-control-sm" style="width:80px; display:none">
                                        </div>
                                    </td>

                                    {{-- Nilai Awal dengan masking --}}
                                    <td x-data="numInput($wire.entangle('rows.{{ $cid }}.nilai_awal'), $wire.entangle('rows.{{ $cid }}.satuan'))">
                                        <input type="text" inputmode="decimal" :value="display"
                                            @focus="onFocus" @blur="onBlur" @input="onInput"
                                            class="form-control form-control-sm text-end">
                                    </td>

                                    {{-- Nilai Target dengan masking --}}
                                    <td x-data="numInput($wire.entangle('rows.{{ $cid }}.nilai_target'), $wire.entangle('rows.{{ $cid }}.satuan'))">
                                        <input type="text" inputmode="decimal" :value="display"
                                            @focus="onFocus" @blur="onBlur" @input="onInput"
                                            class="form-control form-control-sm text-end">
                                    </td>

                                    <td><input type="date" wire:model="rows.{{ $cid }}.tanggal_target"
                                            class="form-control form-control-sm"></td>
                                    <td class="text-end">
                                        {{ \App\Support\Format::nilai(((float) ($r['nilai_target'] ?? 0)) - ((float) ($r['nilai_awal'] ?? 0)), $r['satuan'] ?? '') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada cabang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-primary btn-sm"
                    @click="swalConfirm('Simpan target untuk semua cabang?', () => $wire.save())">Simpan Semua</button>
            </div>
        </div>
    @endif
</div>
