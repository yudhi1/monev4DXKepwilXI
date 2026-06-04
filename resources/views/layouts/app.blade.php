<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Monitoring 4DX Wilayah XI' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }

        /* Navbar styling */
        .app-navbar {
            background: linear-gradient(120deg, #006837 0%, #009E60 25%, #00A99D 55%, #0086C9 85%, #1B4F8F 100%);
            box-shadow: 0 4px 14px rgba(0, 0, 0, .15);
        }

        .app-navbar .brand-logo {
            background: rgba(255, 255, 255, .22) !important;
        }

        .app-navbar .navbar-brand {
            font-weight: 700;
            letter-spacing: .3px;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .app-navbar .navbar-brand .brand-logo {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, .18);
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .app-navbar .nav-link {
            color: rgba(255, 255, 255, .92) !important;
            font-weight: 500;
            font-size: .88rem;
            padding: .45rem .65rem;
            border-radius: 8px;
            margin: 0 1px;
            transition: background .15s, color .15s;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            white-space: nowrap;
        }

        .app-navbar .nav-link i {
            font-size: .95rem;
        }

        @media (min-width: 992px) and (max-width: 1399px) {
            .app-navbar .nav-link {
                padding: .4rem .55rem;
                font-size: .82rem;
            }
        }

        .app-navbar .nav-link:hover,
        .app-navbar .nav-item.dropdown:hover>.nav-link {
            background: rgba(255, 255, 255, .14);
            color: #fff !important;
        }

        .app-navbar .nav-link.active {
            background: rgba(255, 255, 255, .22);
            color: #fff !important;
        }

        .app-navbar .dropdown-menu {
            border: none;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
            padding: .35rem;
            margin-top: .35rem;
        }

        .app-navbar .dropdown-item {
            border-radius: 6px;
            padding: .45rem .7rem;
            font-size: .92rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .app-navbar .dropdown-item:hover {
            background: #e8f0ff;
            color: #0b3d91;
        }

        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            background: rgba(255, 255, 255, .15);
            color: #fff;
            padding: .35rem .75rem;
            border-radius: 999px;
            font-size: .85rem;
        }

        .user-chip .avatar {
            width: 28px;
            height: 28px;
            background: #fff;
            color: #0b3d91;
            font-weight: 700;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .8rem;
        }

        .user-chip .role-badge {
            background: rgba(255, 255, 255, .25);
            padding: .1rem .5rem;
            border-radius: 999px;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        /* Form styling */
        .form-control,
        .form-select {
            border-width: 2px;
            border-color: #adb5bd;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, .15);
        }

        .form-control[readonly] {
            background-color: #f1f3f5;
            border-color: #ced4da;
        }
    </style>
    @livewireStyles
</head>

<body class="bg-light">
    @auth
        @php
            $u = auth()->user();
            $initial = strtoupper(mb_substr($u->name, 0, 1));
            $roleLabel =
                [
                    'admin' => 'Admin',
                    'kedeputian_wilayah' => 'Wilayah',
                    'kantor_cabang' => 'Cabang',
                ][$u->getRoleNames()->first()] ?? '-';
        @endphp
        <nav class="navbar navbar-expand-xl app-navbar sticky-top">
            <div class="container-fluid px-4">
                <a class="navbar-brand text-white" href="{{ url('/dashboard') }}">
                    <span class="brand-logo"><i class="bi bi-graph-up-arrow"></i></span>
                    <span>Monev <span class="fw-light">4DX</span></span>
                </a>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="nav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}"
                                href="{{ url('/dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Dashboard-
                            </a>
                        </li>
                        @role('admin')
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->is('users*', 'wilayahs*', 'cabangs*') ? 'active' : '' }}"
                                    href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-gear"></i> Master
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ url('/users') }}"><i class="bi bi-people"></i>
                                            User</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/wilayahs') }}"><i class="bi bi-geo-alt"></i>
                                            Wilayah</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/cabangs') }}"><i class="bi bi-building"></i>
                                            Cabang</a></li>
                                </ul>
                            </li>
                        @endrole
                        @hasanyrole('admin|kedeputian_wilayah')
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->is('wigs*', 'wig-targets*', 'wig-realisasi*') ? 'active' : '' }}"
                                    href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-bullseye"></i> WIG
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ url('/wigs') }}"><i class="bi bi-list-task"></i>
                                            Input Data WIG</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/wig-targets') }}"><i class="bi bi-flag"></i>
                                            Input Target WIG</a></li>
                                    <li><a class="dropdown-item" href="{{ url('/wig-realisasi') }}"><i
                                                class="bi bi-clipboard-data"></i> Input Realisasi WIG Bulanan</a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('lag-measures*') ? 'active' : '' }}"
                                    href="{{ url('/lag-measures') }}">
                                    <i class="bi bi-graph-down"></i> Lag
                                </a>
                            </li>
                        @endhasanyrole
                        @role('kantor_cabang')
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('wig-realisasi*') ? 'active' : '' }}"
                                    href="{{ url('/wig-realisasi') }}">
                                    <i class="bi bi-clipboard-data"></i> Realisasi WIG
                                </a>
                            </li>
                        @endrole
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('lead-measures*', 'realisasi*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-graph-up"></i> Lead Measure
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ url('/lead-measures') }}"><i
                                            class="bi bi-list-task"></i> Input Data Lead Measure</a></li>
                                <li><a class="dropdown-item" href="{{ url('/realisasi') }}"><i
                                            class="bi bi-clipboard-check"></i> Input Realisasi Lead Measure</a></li>
                            </ul>
                        </li>
                        @hasanyrole('admin|kedeputian_wilayah|kantor_cabang')
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->is('monitoring-prioritas*') ? 'active' : '' }}"
                                    href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-star"></i> Prioritas
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ url('/monitoring-prioritas/iuran') }}"><i
                                                class="bi bi-cash-coin"></i> Iuran</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header">Monev Iuran</h6>
                                    </li>
                                    @hasanyrole('admin|kedeputian_wilayah')
                                        <li><a class="dropdown-item" href="{{ route('monev-iuran.segmen') }}"><i
                                                    class="bi bi-list-ul"></i> Master Segmen</a></li>
                                    @endhasanyrole
                                    <li><a class="dropdown-item" href="{{ route('monev-iuran.input') }}"><i
                                                class="bi bi-pencil-square"></i> Input Realisasi</a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('laporan*') ? 'active' : '' }}"
                                    href="{{ url('/laporan') }}">
                                    <i class="bi bi-file-earmark-bar-graph"></i> Laporan
                                </a>
                            </li>
                        @endhasanyrole
                    </ul>
                    <div class="d-flex align-items-center gap-2">
                        <a class="nav-link {{ request()->is('panduan*') ? 'active' : '' }}"
                            href="{{ url('/panduan') }}">
                            <i class="bi bi-book"></i> Panduan
                        </a>
                        <span class="user-chip">
                            <span class="avatar">{{ $initial }}</span>
                            <span class="d-flex flex-column lh-1">
                                <span class="fw-semibold">{{ $u->name }}</span>
                                <span class="role-badge mt-1">{{ $roleLabel }}</span>
                            </span>
                        </span>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i>
                                Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    @endauth

    <main class="container-fluid py-4">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts

    <script>
        window.swalConfirm = function(message, callback, opts = {}) {
            Swal.fire({
                title: opts.title || 'Konfirmasi',
                text: message,
                icon: opts.icon || 'question',
                showCancelButton: true,
                confirmButtonColor: opts.confirmColor || '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: opts.confirmText || 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                reverseButtons: true,
            }).then((r) => {
                if (r.isConfirmed) callback();
            });
        };

        window.swalToast = function(type, message) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type || 'success',
                title: message,
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
            });
        };

        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (payload) => {
                const data = Array.isArray(payload) ? payload[0] : payload;
                swalToast(data.type || 'success', data.message || 'Berhasil');
            });
        });

        // Alpine component: input angka dengan format ribuan Indonesia (real-time masking)
        document.addEventListener('alpine:init', () => {
            Alpine.data('numInput', (entangled, satuanRef) => ({
                entangled,
                satuanRef,
                display: '',
                focused: false,

                get satuan() {
                    return typeof this.satuanRef === 'object' ? (this.satuanRef ?? '') : (this
                        .satuanRef || '');
                },
                get isRp() {
                    return /^rp$/i.test(this.satuan.trim());
                },

                addSeparator(str) {
                    // Tambahkan titik sebagai pemisah ribuan pada bagian integer
                    const parts = str.split(',');
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    return parts.join(',');
                },

                toFloat(display) {
                    return parseFloat(display.replace(/\./g, '').replace(',', '.')) || 0;
                },

                formatFull(val) {
                    // Untuk display awal (dari data tersimpan), format lengkap
                    const n = parseFloat(val);
                    if (isNaN(n) || n === 0) return '';
                    if (this.isRp) {
                        return n.toLocaleString('id-ID', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        });
                    }
                    return n.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                },

                init() {
                    this.display = this.formatFull(parseFloat(this.entangled) || 0);
                    this.$watch('entangled', (val) => {
                        if (!this.focused) this.display = this.formatFull(parseFloat(val) || 0);
                    });
                    this.$watch('satuanRef', () => {
                        if (!this.focused) this.display = this.formatFull(this.toFloat(this
                            .display));
                    });
                },

                onFocus() {
                    this.focused = true;
                },

                onBlur() {
                    this.focused = false;
                    const val = this.toFloat(this.display);
                    this.entangled = val;
                    // Normalisasi tampilan akhir (tambah ,00 jika bukan Rp)
                    this.display = this.formatFull(val);
                },

                onInput(e) {
                    const el = e.target;
                    const selEnd = el.selectionEnd;
                    const oldVal = el.value;

                    // Hitung karakter non-titik sebelum kursor (untuk restore posisi kursor)
                    const charsBeforeCursor = oldVal.slice(0, selEnd).replace(/\./g, '').length;

                    // Bersihkan input: hanya angka dan koma (koma = desimal)
                    let raw = oldVal.replace(/\./g, '').replace(/[^\d,]/g, '');
                    if (this.isRp) raw = raw.replace(/,/g, ''); // Rp tidak pakai desimal
                    const parts = raw.split(',');
                    if (parts.length > 2) raw = parts[0] + ',' + parts[1]; // maks 1 koma

                    // Tambah titik pemisah ribuan secara real-time
                    const formatted = this.addSeparator(raw);
                    this.display = formatted;

                    // Restore posisi kursor setelah Alpine update DOM
                    this.$nextTick(() => {
                        let count = 0,
                            newPos = formatted.length;
                        for (let i = 0; i < formatted.length; i++) {
                            if (formatted[i] !== '.') count++;
                            if (count === charsBeforeCursor) {
                                newPos = i + 1;
                                break;
                            }
                        }
                        el.setSelectionRange(newPos, newPos);
                    });
                },
            }));

            // Alpine component: dropdown satuan + opsi lainnya
            Alpine.data('satuanInput', (entangled) => ({
                entangled,
                PRESET: ['Rp', '%', 'unit', 'orang', 'rekening', 'nasabah'],
                custom: '',
                isLainnya: false,

                init() {
                    const val = this.entangled ?? 'Rp';
                    if (this.PRESET.includes(val)) {
                        this.isLainnya = false;
                    } else {
                        this.isLainnya = true;
                        this.custom = val;
                    }
                    this.$watch('entangled', (val) => {
                        if (!this.isLainnya && this.PRESET.includes(val)) return;
                    });
                },

                onSelectChange(e) {
                    const v = e.target.value;
                    if (v === '__lainnya__') {
                        this.isLainnya = true;
                        this.entangled = this.custom || '';
                    } else {
                        this.isLainnya = false;
                        this.custom = '';
                        this.entangled = v;
                    }
                },

                onCustomInput(e) {
                    this.custom = e.target.value;
                    this.entangled = this.custom;
                },

                get selectVal() {
                    return this.isLainnya ? '__lainnya__' : (this.entangled || 'Rp');
                },
            }));
        });

        @if (session('success'))
            document.addEventListener('DOMContentLoaded', () => swalToast('success', @json(session('success'))));
        @endif
        @if (session('error'))
            document.addEventListener('DOMContentLoaded', () => swalToast('error', @json(session('error'))));
        @endif
    </script>
</body>

</html>
