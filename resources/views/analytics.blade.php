@extends('layouts.app')

@section('content')

<div class="container analytics-container">

    <h2 class="page-title">Analytics Dashboard</h2>

    <!-- ================= FILTER PERIODE ================= -->
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;">

        <a href="{{ url('/analytics') }}"
           style="padding:7px 16px;border-radius:20px;font-size:13px;font-weight:bold;text-decoration:none;
                  {{ $periode === 'all' ? 'background:#ed1c24;color:#fff;' : 'background:#f0f0f0;color:#495057;' }}">
            Semua
        </a>

        <a href="{{ url('/analytics?periode=week') }}"
           style="padding:7px 16px;border-radius:20px;font-size:13px;font-weight:bold;text-decoration:none;
                  {{ $periode === 'week' ? 'background:#ed1c24;color:#fff;' : 'background:#f0f0f0;color:#495057;' }}">
            Minggu Ini
        </a>

        <a href="{{ url('/analytics?periode=month') }}"
           style="padding:7px 16px;border-radius:20px;font-size:13px;font-weight:bold;text-decoration:none;
                  {{ $periode === 'month' ? 'background:#ed1c24;color:#fff;' : 'background:#f0f0f0;color:#495057;' }}">
            Bulan Ini
        </a>

        <!-- Custom range -->
        <form method="GET" action="{{ url('/analytics') }}"
              style="display:flex;align-items:center;gap:6px;">
            <input type="hidden" name="periode" value="custom">
            <input type="date" name="from" value="{{ $from ?? '' }}"
                   style="padding:6px 10px;border:1px solid #dee2e6;border-radius:8px;font-size:13px;">
            <span style="color:#6c757d;">—</span>
            <input type="date" name="to" value="{{ $to ?? '' }}"
                   style="padding:6px 10px;border:1px solid #dee2e6;border-radius:8px;font-size:13px;">
            <button type="submit"
                    style="padding:7px 14px;background:{{ $periode === 'custom' ? '#ed1c24' : '#6c757d' }};
                           color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:13px;font-weight:bold;">
                Tampilkan
            </button>
        </form>

        @if($periode !== 'all')
        <span style="font-size:12px;color:#6c757d;margin-left:4px;">
            📅
            @if($periode === 'week') Minggu ini
            @elseif($periode === 'month') Bulan ini
            @else {{ $from }} s/d {{ $to }}
            @endif
        </span>
        @endif

    </div>

    <!-- ================= MINI STATS ================= -->
    <div class="stats-grid">

<div class="mini-card">
            <p>Total Lokasi</p>
            <h3>{{ $totalAll }}</h3>
        </div>

        <div class="mini-card win">
            <p>WIN</p>
            <h3>{{ $win }}</h3>
        </div>

        <div class="mini-card lose">
            <p>LOSE</p>
            <h3>{{ $lose }}</h3>
        </div>

        <div class="mini-card">
            <p>NOT VISIT</p>
            <h3>{{ $notVisit }}</h3>
        </div>


    </div>

    <!-- ================= TOP WILAYAH ================= -->
    <div class="chart-card">
        <h4>🔥 Top Wilayah</h4>
        <div class="wilayah-list" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:12px;">
            @foreach($topWilayah as $wil => $val)
            <div class="wilayah-item compact">

                <div class="top-row">
                    🔥 {{ $wil }}
                    <span>{{ $val['total'] }}</span>
                </div>

                <div class="progress multi">
                    <div class="win"     style="width: {{ $val['win_pct'] }}%"></div>
                    <div class="lose"    style="width: {{ $val['lose_pct'] }}%"></div>
                    <div class="unknown" style="width: {{ $val['unknown_pct'] }}%"></div>
                </div>

                <div class="mini-footer">
                    🟢 {{ $val['win'] }}
                    🔴 {{ $val['lose'] }}
                    🟡 {{ $val['unknown'] }}
                    • {{ round($val['conversion'], 1) }}%
                </div>

            </div>
            @endforeach
        </div>
    </div>

    <!-- ================= KATEGORI DETAIL ================= -->
    <div class="chart-card">
        <h4>📂 Breakdown per Kategori</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-top:12px;">
            @foreach($kategoriDetail as $kat => $val)
            <div style="background:#f8f9fa;border-radius:8px;padding:12px;">

                <div style="display:flex;justify-content:space-between;font-weight:bold;margin-bottom:6px;">
                    {{ $kat }}
                    <span>{{ $val['total'] }}</span>
                </div>

                <div style="height:10px;border-radius:4px;overflow:hidden;display:flex;margin-bottom:6px;">
                    <div style="width:{{ $val['win_pct'] }}%;background:#28a745;"></div>
                    <div style="width:{{ $val['lose_pct'] }}%;background:#dc3545;"></div>
                    <div style="width:{{ $val['unknown_pct'] }}%;background:#ffc107;"></div>
                </div>

                <div style="font-size:12px;color:#6c757d;">
                    🟢 {{ $val['win'] }}
                    🔴 {{ $val['lose'] }}
                    🟡 {{ $val['unknown'] }}
                </div>

            </div>
            @endforeach
        </div>
    </div>

    <!-- ================= CHARTS ================= -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">

        <div class="chart-card">
            <h4>📊 Distribusi Wilayah</h4>
            <canvas id="wilayahChart"></canvas>
        </div>

        <div class="chart-card">
            <h4>📈 Trend Harian</h4>
            <canvas id="dailyChart"></canvas>
        </div>

    </div>

</div>

@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // ================= WILAYAH CHART =================
    const wilayah = @json($wilayah);

    new Chart(document.getElementById('wilayahChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(wilayah),
            datasets: [{
                label: 'Total Lokasi',
                data: Object.values(wilayah),
                backgroundColor: 'rgba(237,28,36,0.7)',
                borderColor: '#ed1c24',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    // ================= DAILY CHART (multi-dataset) =================
    const daily = @json($daily);

    const sortedDates = Object.keys(daily).sort();
    const winData     = sortedDates.map(d => daily[d].win     ?? 0);
    const loseData    = sortedDates.map(d => daily[d].lose    ?? 0);
    const unknownData = sortedDates.map(d => daily[d].unknown ?? 0);

    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: sortedDates,
            datasets: [
                {
                    label: 'WIN',
                    data: winData,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40,167,69,0.08)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
                {
                    label: 'LOSE',
                    data: loseData,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,0.08)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                },
                {
                    label: 'NOT VISIT',
                    data: unknownData,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255,193,7,0.08)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

});
</script>
@endpush