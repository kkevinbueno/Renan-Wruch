<?php
declare(strict_types=1);

final class PublicController
{
    public function home(): void
    {
        View::render('public/home', [
            'title' => Settings::get('brand_name'),
            'modalities' => Modalities::all(),
            'plans' => Billing::plans(),
            'photoUrl' => Files::publicUrl((int) Settings::get('public_photo_file_id') ?: null),
        ], 'public');
    }

    public function privacy(): void
    {
        View::render('public/privacy', ['title' => 'Política de Privacidade'], 'public');
    }

    public function terms(): void
    {
        View::render('public/terms', ['title' => 'Termos de Uso'], 'public');
    }

    public function offline(): void
    {
        View::render('public/offline', ['title' => 'Sem conexão'], 'public');
    }
}
