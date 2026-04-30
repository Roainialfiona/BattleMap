@extends('layouts.app')

@section('content')

<div class="analytics-outer">
    <div class="analytics-container">

        <!-- HEADER -->
        <div class="analytics-header">
            <h2 class="page-title">Analytics Dashboard</h2>
            
            <!-- ================= FILTER PERIODE ================= -->
            <div class="filter-container">

                <a href="{{ url('/analytics') }}"
                class="filter-btn {{ $periode === 'all' ? 'active' : '' }}">
                    Semua
                </a>

                <a href="{{ url('/analytics?periode=week') }}"
                class="filter-btn {{ $periode === 'week' ? 'active' : '' }}">
                    Minggu Ini
                </a>

                <a href="{{ url('/analytics?periode=month') }}"
                class="filter-btn {{ $periode === 'month' ? 'active' : '' }}">
                    Bulan Ini
                </a>

                <!-- Custom range -->
                <form method="GET" action="{{ url('/analytics') }}" class="date-filter">
                    
                    <input type="hidden" name="periode" value="custom">

                    <input type="date" name="from" value="{{ $from ?? '' }}" class="date-input">

                    <span class="date-separator">—</span>

                    <input type="date" name="to" value="{{ $to ?? '' }}" class="date-input">

                    <button type="submit"
                        class="date-btn {{ $periode === 'custom' ? 'active' : '' }}">
                        Tampilkan
                    </button>

                </form>

                @if($periode !== 'all')
                <span class="periode-label">
                    📅
                    @if($periode === 'week') Minggu ini
                    @elseif($periode === 'month') Bulan ini
                    @else {{ $from }} s/d {{ $to }}
                    @endif
                </span>
                @endif

            </div>
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

            <div class="mini-card unknown">
                <p>UNKNOWN</p>
                <h3>{{ $notVisit }}</h3>
            </div>

            {{-- ← CARD BARU: DONE VISIT --}}
            <div class="mini-card Done">
                <p>DONE VISIT</p>
                <h3>{{ $doneVisit }}</h3>
            </div>

        </div>

        <!-- ================= TOP WILAYAH ================= -->
        <div class="chart-card">
            <h4>🔥 Top Wilayah</h4>
            <div class="wilayah-list">
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

            <div class="kategori-grid">
                @foreach($kategoriDetail as $kat => $val)

                <div class="kategori-card">

                    <div class="kategori-header">
                        {{ $kat }}
                        <span>{{ $val['total'] }}</span>
                    </div>

                    <div class="kategori-bar">
                        <div class="win" style="width:{{ $val['win_pct'] }}%"></div>
                        <div class="lose" style="width:{{ $val['lose_pct'] }}%"></div>
                        <div class="unknown" style="width:{{ $val['unknown_pct'] }}%"></div>
                    </div>

                    <div class="kategori-footer">
                        🟢 {{ $val['win'] }}
                        🔴 {{ $val['lose'] }}
                        🟡 {{ $val['unknown'] }}

                        <span class="win-rate">
                            Win: {{ $val['total'] > 0 ? round(($val['win'] / $val['total']) * 100, 1) : 0 }}%
                        </span>
                    </div>

                </div>

                @endforeach
            </div>
        </div>    
    

        <!-- ================= CHARTS ================= -->
        <div class="chart-section">
            
            <div class="chart-grid">

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
                    label: 'UNKNOWN',
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
