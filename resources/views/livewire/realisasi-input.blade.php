<div>
    <h3>Input Realisasi Mingguan</h3>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small">Pilih WIG</label>
                    <select wire:model.live="wig_id" class="form-select form-select-sm">
                        <option value="">- Pilih WIG -</option>
                        @foreach($wigs as $w)
                            <option value="{{ $w->id }}" title="{{ $w->nama_wig }}">
                                {{ $w->kode_wig }} — {{ \Illuminate\Support\Str::limit($w->nama_wig, 70) }}
                            </option>
                        @endforeach
                    </select>
                    @php $selectedWig = $wigs->firstWhere('id', $wig_id); @endphp
                    @if($selectedWig)
                        <div class="border rounded p-2 mt-2 bg-light small">
                            <div>
                                <span class="badge bg-primary">{{ $selectedWig->kode_wig }}</span>
                                @if($selectedWig->bidang)<span class="badge bg-info">{{ $selectedWig->bidang }}</span>@endif
                            </div>
                            <div class="mt-1 text-muted" style="white-space: pre-wrap; word-break: break-word;">{{ $selectedWig->nama_wig }}</div>
                        </div>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Pilih Kantor Cabang</label>
                    <select wire:model.live="cabang_id" class="form-select form-select-sm">
                        <option value="">- Pilih Cabang -</option>
                        @foreach($cabangs as $c)
                            <option value="{{ $c->id }}">{{ $c->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Tahun</label>
                    <input type="number" wire:model.live="tahun" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Bulan</label>
                    <select wire:model.live="bulan" class="form-select form-select-sm">
                        @foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $k=>$v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($wig_id && $cabang_id)
        @if($leads->isEmpty())
            <div class="alert alert-warning">Belum ada Lead Measure untuk kombinasi ini. Silakan setup terlebih dahulu di menu <strong>Lead</strong>.</div>
        @else
            <div class="card">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <strong>Input Realisasi — {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }}</strong>
                        <small>Pilih minggu di bawah, isi target & realisasi per Lead.</small>
                    </div>
                    <div class="card-body">
                        {{-- TAB PER MINGGU --}}
                        <style>
                            .minggu-tabs .nav-link {
                                color: #495057;
                                font-weight: 500;
                                border: 2px solid transparent;
                                border-bottom: 3px solid transparent;
                                background: #f8f9fa;
                                margin-right: 4px;
                                transition: all .15s;
                            }
                            .minggu-tabs .nav-link:hover {
                                background: #e9ecef;
                                color: #0d6efd;
                            }
                            .minggu-tabs .nav-link.active {
                                background: #0d6efd !important;
                                color: #fff !important;
                                border-color: #0d6efd #0d6efd #0a58ca !important;
                                border-bottom-width: 3px;
                                box-shadow: 0 4px 10px rgba(13,110,253,.25);
                                transform: translateY(-1px);
                            }
                            .minggu-tabs .nav-link.active .badge { box-shadow: 0 0 0 2px rgba(255,255,255,.4); }
                        </style>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="text-muted small">Sedang mengisi:</span>
                            <span class="badge bg-primary fs-6"><i class="bi bi-pencil-square"></i> Minggu {{ $minggu }}</span>
                        </div>
                        <ul class="nav nav-tabs minggu-tabs mb-3">
                            @for ($m = 1; $m <= 4; $m++)
                                <li class="nav-item">
                                    <button type="button" wire:click="$set('minggu', {{ $m }})"
                                        class="nav-link {{ $minggu == $m ? 'active' : '' }}">
                                        @if ($minggu == $m)<i class="bi bi-check-circle-fill"></i>@else<i class="bi bi-calendar-week"></i>@endif
                                        Minggu {{ $m }}
                                    </button>
                                </li>
                            @endfor
                        </ul>

                        {{-- TABEL FOKUS MINGGU TERPILIH --}}
                        <div class="table-responsive" x-data="{ editing: {} }" @lead-saved="editing[$event.detail.leadId] = false">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light text-center">
                                    <tr>
                                        <th style="width:40px">No</th>
                                        <th>Lead Measure</th>
                                        <th style="width:140px">Target</th>
                                        <th style="width:140px">Realisasi</th>
                                        <th style="width:90px">% Capaian</th>
                                        <th style="min-width:280px">Keterangan</th>
                                        <th style="width:70px">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($leads as $i => $lead)
                                    @php
                                        $tM = (float) ($rows[$lead->id][$minggu]['target'] ?? 0);
                                        $rM = (float) ($rows[$lead->id][$minggu]['realisasi'] ?? 0);
                                        $pM = $tM > 0 ? round(($rM/$tM)*100, 2) : 0;
                                        $cM = $pM >= 100 ? 'success' : ($pM >= 70 ? 'warning' : ($pM > 0 ? 'danger' : 'secondary'));
                                    @endphp
                                    <tr wire:key="lead-{{ $lead->id }}-m{{ $minggu }}">
                                        <td class="text-center">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $lead->nama_lead }} <span class="text-muted">({{ $lead->kode_lead }})</span></div>
                                            @if($lead->satuan)<small class="badge bg-light text-dark">{{ $lead->satuan }}</small>@endif
                                        </td>
                                        <td x-show="!editing[{{ $lead->id }}]" class="text-end"><span class="badge bg-light text-dark" style="font-size:.9rem">{{ number_format($tM, 2, ',', '.') }}</span></td>
                                        <td x-show="editing[{{ $lead->id }}]" style="display:none"><input type="number" step="0.01" wire:key="t-{{ $lead->id }}-m{{ $minggu }}" wire:model.live.debounce.400ms="rows.{{ $lead->id }}.{{ $minggu }}.target" class="form-control form-control-sm text-end" @focus="editing[{{ $lead->id }}] = true"></td>

                                        <td x-show="!editing[{{ $lead->id }}]" class="text-end"><span class="badge bg-light text-dark" style="font-size:.9rem">{{ number_format($rM, 2, ',', '.') }}</span></td>
                                        <td x-show="editing[{{ $lead->id }}]" style="display:none"><input type="number" step="0.01" wire:key="r-{{ $lead->id }}-m{{ $minggu }}" wire:model.live.debounce.400ms="rows.{{ $lead->id }}.{{ $minggu }}.realisasi" class="form-control form-control-sm text-end"></td>

                                        <td class="text-center"><span class="badge bg-{{ $cM }}" style="font-size:.9rem">{{ $pM }}%</span></td>

                                        <td x-show="!editing[{{ $lead->id }}]" style="white-space: pre-wrap;"><small class="text-muted">{{ $rows[$lead->id][$minggu]['keterangan'] ?: '-' }}</small></td>
                                        <td x-show="editing[{{ $lead->id }}]" style="display:none"><textarea rows="1" wire:key="k-{{ $lead->id }}-m{{ $minggu }}" wire:model="rows.{{ $lead->id }}.{{ $minggu }}.keterangan" class="form-control form-control-sm" placeholder="Catatan minggu {{ $minggu }}..."></textarea></td>

                                        <td class="text-center">
                                            <button type="button" x-show="!editing[{{ $lead->id }}]" @click="editing[{{ $lead->id }}] = true" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button type="button" x-show="editing[{{ $lead->id }}]" @click="editing[{{ $lead->id }}] = false; setTimeout(() => $wire.saveLead({{ $lead->id }}), 50)" class="btn btn-sm btn-success" title="Simpan" style="display:none">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" x-show="editing[{{ $lead->id }}]" @click="editing[{{ $lead->id }}] = false; $wire.cancelEdit({{ $lead->id }})" class="btn btn-sm btn-secondary" title="Batal" style="display:none">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">Tidak ada Lead Measure.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
            </div>
        @endif
    @else
        <div class="alert alert-info">Pilih <strong>WIG</strong> dan <strong>Kantor Cabang</strong> dulu untuk menampilkan grid input.</div>
    @endif
</div>
