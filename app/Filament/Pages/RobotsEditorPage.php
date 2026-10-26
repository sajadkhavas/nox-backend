<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class RobotsEditorPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Robots.txt';
    protected static ?string $navigationGroup = 'سئو';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $title           = 'ویرایش Robots.txt';
    protected static string  $view            = 'filament.pages.robots-editor';

    public ?string $content = '';

    public function mount(): void
    {
        $path = public_path('robots.txt');
        $this->content = file_exists($path) ? file_get_contents($path) : '';
        $this->form->fill(['content' => $this->content]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('content')
                ->label('محتوای robots.txt')
                ->rows(20)
                ->required(),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        file_put_contents(public_path('robots.txt'), $data['content']);
        Notification::make()->title('robots.txt ذخیره شد')->success()->send();
    }

    public function resetToDefault(): void
    {
        $default = "User-agent: Googlebot\nAllow: /\nUser-agent: Bingbot\nAllow: /\nUser-agent: *\nAllow: /\nSitemap: " . config('app.url') . "/sitemap.xml\n";
        $this->form->fill(['content' => $default]);
        Notification::make()->title('به حالت پیش‌فرض برگشت')->info()->send();
    }
}
