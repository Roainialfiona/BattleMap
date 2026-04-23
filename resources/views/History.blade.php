@extends('layouts.app')

@section('content')

<div class="container analytics-container">

    <h2 class="page-title">📋 History Perubahan</h2>

    {{-- ================= FILTER ================= --}}
    <form method="GET" action="{{ url('/history') }}"
          style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px;">

        {{-- Search --}}
        <input type="text" name="search" value="{{ $search }}"
               placeholder="🔍 Cari nama lokasi / nilai..."
               style="padding:8px 14px;border:1px solid #dee2e6;border-radius:20px;font-size:13px;min-width:220px;">

        {{-- Filter sheet --}}
        <select name="sheet"
                style="padding:8px 14px;border:1px solid #dee2e6;border-radius:20px;font-size:13px;background:#fff;">
            <option value="">Semua Sheet</option>
            @foreach($sheetOptions as $opt)
                <option value="{{ $opt }}" {{ $filterSheet === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>

        {{-- Filter field --}}
        <select name="field"
                style="padding:8px 14px;border:1px solid #dee2e6;border-radius:20px;font-size:13px;background:#fff;">
            <option value="">Semua Field</option>
            @foreach($fieldOptions as $opt)
                <option value="{{ $opt }}" {{ $filterField === $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>

        {{-- Filter tanggal --}}
        <div style="display:flex;align-items:center;gap:6px;">
            <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}"
                   style="padding:7px 10px;border:1px solid #dee2e6;border-radius:20px;font-size:13px;">
            <span style="color:#6c757d;">—</span>
            <input type="date" name="date_to" value="{{ $dateTo ?? '' }}"
                   style="padding:7px 10px;border:1px solid #dee2e6;border-radius:20px;font-size:13px;">
        </div>

        <button type="submit"
                style="padding:8px 18px;background:#ed1c24;color:#fff;border:none;border-radius:20px;
                       font-size:13px;font-weight:bold;cursor:pointer;">
            Tampilkan
        </button>

        @if($search || $filterSheet || $filterField || $dateFrom || $dateTo)
        <a href="{{ url('/history') }}"
           style="padding:8px 14px;background:#f0f0f0;color:#495057;border-radius:20px;
                  font-size:13px;text-decoration:none;">
            ✕ Reset
        </a>
        @endif

    </form>

    {{-- ================= COUNT ================= --}}
    <p style="font-size:13px;color:#6c757d;margin-bottom:12px;">
        Menampilkan <strong>{{ count($rows) }}</strong> perubahan
    </p>

    {{-- ================= TABEL ================= --}}
    @if(count($rows) === 0)
        <div style="text-align:center;padding:60px 20px;color:#adb5bd;">
            <div style="font-size:48px;margin-bottom:12px;">📭</div>
            <div style="font-size:15px;">Belum ada history perubahan</div>
        </div>
    @else
    <div style="overflow-x:auto;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,0.08);">
        <table style="width:100%;border-collapse:collapse;font-size:13px;background:#fff;">
            <thead>
                <tr style="background:#f8f9fa;border-bottom:2px solid #dee2e6;">
                    <th style="padding:12px 14px;text-align:left;color:#495057;white-space:nowrap;">⏰ Waktu</th>
                    <th style="padding:12px 14px;text-align:left;color:#495057;">Sheet</th>
                    <th style="padding:12px 14px;text-align:left;color:#495057;">Lokasi</th>
                    <th style="padding:12px 14px;text-align:left;color:#495057;">Field</th>
                    <th style="padding:12px 14px;text-align:left;color:#495057;">Nilai Lama</th>
                    <th style="padding:12px 14px;text-align:left;color:#495057;">Nilai Baru</th>
                    <th style="padding:12px 14px;text-align:left;color:#495057;">User</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                <tr style="border-bottom:1px solid #f0f0f0;
                            background: {{ $i % 2 === 0 ? '#fff' : '#fafafa' }};">

                    {{-- Timestamp --}}
                    <td style="padding:10px 14px;color:#6c757d;white-space:nowrap;">
                        {{ $row['timestamp'] }}
                    </td>

                    {{-- Sheet badge --}}
                    <td style="padding:10px 14px;">
                        @php
                            $badgeColor = match($row['sheet']) {
                                'New_Education' => '#0d6efd',
                                'New_SPPG'      => '#198754',
                                'New_KDMP'      => '#fd7e14',
                                'New_Faskes'    => '#6f42c1',
                                default         => '#6c757d',
                            };
                            $badgeLabel = match($row['sheet']) {
                                'New_Education' => 'Education',
                                'New_SPPG'      => 'SPPG',
                                'New_KDMP'      => 'KDMP',
                                'New_Faskes'    => 'Faskes',
                                default         => $row['sheet'],
                            };
                        @endphp
                        <span style="background:{{ $badgeColor }}15;color:{{ $badgeColor }};
                                     padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;">
                            {{ $badgeLabel }}
                        </span>
                    </td>

                    {{-- Nama Lokasi --}}
                    <td style="padding:10px 14px;font-weight:500;max-width:200px;">
                        {{ $row['nama_lokasi'] }}
                        <span style="color:#adb5bd;font-size:11px;display:block;">#{{ $row['row_id'] }}</span>
                    </td>

                    {{-- Field --}}
                    <td style="padding:10px 14px;">
                        <span style="background:#f0f0f0;padding:2px 8px;border-radius:4px;font-size:12px;">
                            {{ $row['field'] }}
                        </span>
                    </td>

                    {{-- Old Value --}}
                    <td style="padding:10px 14px;">
                        @if($row['old_value'] !== '')
                            @php
                                $ov = strtoupper($row['old_value']);
                                $oldColor = match(true) {
                                    $ov === 'WIN'  => '#dc354520',
                                    $ov === 'LOSE' => '#dc3545',
                                    default        => 'transparent',
                                };
                            @endphp
                            <span style="color:#dc3545;text-decoration:line-through;font-size:13px;">
                                {{ $row['old_value'] ?: '—' }}
                            </span>
                        @else
                            <span style="color:#adb5bd;">—</span>
                        @endif
                    </td>

                    {{-- New Value --}}
                    <td style="padding:10px 14px;">
                        @php
                            $nv = strtoupper($row['new_value']);
                            $newStyle = match($nv) {
                                'WIN'  => 'color:#28a745;font-weight:bold;',
                                'LOSE' => 'color:#dc3545;font-weight:bold;',
                                default => 'color:#212529;',
                            };
                        @endphp
                        <span style="{{ $newStyle }}">
                            {{ $row['new_value'] ?: '—' }}
                        </span>
                    </td>

                    {{-- User --}}
                    <td style="padding:10px 14px;color:#6c757d;">
                        {{ $row['user'] }}
                    </td>

                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

@endsection