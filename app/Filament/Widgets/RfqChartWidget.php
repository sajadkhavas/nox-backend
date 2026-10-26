<?php
namespace App\Filament\Widgets;
use App\Models\RfqRequest;
use Filament\Widgets\ChartWidget;

class RfqChartWidget extends ChartWidget
{
    protected static ?string $heading = 'استعلام‌های قیمت';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 2;
    public ?string $filter = 'week';

    protected function getFilters(): ?array
    {
        return [
            'week'  => '۷ روز',
            'month' => '۳۰ روز',
        ];
    }

    protected function getData(): array
    {
        $days = $this->filter === 'week' ? 7 : 30;
        $labels = $pending = $processing = $quoted = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[]     = $date->locale('fa')->isoFormat('D MMM');
            $pending[]    = RfqRequest::whereDate('created_at', $date)->where('status', 'pending')->count();
            $processing[] = RfqRequest::whereDate('created_at', $date)->where('status', 'processing')->count();
            $quoted[]     = RfqRequest::whereDate('created_at', $date)->where('status', 'quoted')->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'در انتظار',
                    'data'            => $pending,
                    'borderColor'     => '#a78bfa',
                    'backgroundColor' => 'rgba(167,139,250,0.08)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'borderWidth'     => 2,
                    'pointRadius'     => 3,
                ],
                [
                    'label'           => 'در بررسی',
                    'data'            => $processing,
                    'borderColor'     => '#7c3aed',
                    'backgroundColor' => 'rgba(124,58,237,0.08)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'borderWidth'     => 2,
                    'pointRadius'     => 3,
                ],
                [
                    'label'           => 'قیمت داده شده',
                    'data'            => $quoted,
                    'borderColor'     => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,0.06)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'borderWidth'     => 2,
                    'pointRadius'     => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string { return 'line'; }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'top', 'labels' => ['boxWidth' => 10, 'padding' => 12, 'font' => ['size' => 11]]],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['stepSize' => 1], 'grid' => ['color' => 'rgba(139,92,246,0.06)']],
                'x' => ['grid' => ['display' => false]],
            ],
            'interaction' => ['mode' => 'nearest', 'axis' => 'x', 'intersect' => false],
        ];
    }
}
