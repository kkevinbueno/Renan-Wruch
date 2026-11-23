<?php
declare(strict_types=1);

/** Configurações da consultoria dentro da própria interface do Renan (sem painel administrativo separado). */
final class TrainerSettingsController
{
    public const TABS = ['consultoria' => 'Consultoria', 'pagina' => 'Página pública', 'notificacoes' => 'Notificações', 'modalidades' => 'Modalidades', 'operacao' => 'Operação', 'lgpd' => 'Privacidade (LGPD)', 'conta' => 'Minha conta', 'registros' => 'Registros'];

    public function index(): void
    {
        $tab = (string) input('aba', 'conta');
        if (!isset(self::TABS[$tab])) $tab = 'conta';
        $data = ['title' => 'Configurações', 'nav' => 'settings', 'tab' => $tab, 'tabs' => self::TABS, 's' => Settings::all(), 'user' => Auth::user()];
        if ($tab === 'modalidades') $data['modalities'] = Modalities::all(false);
        if ($tab === 'lgpd') $data['requests'] = DB::all('SELECT r.*, u.name, u.email FROM data_requests r JOIN users u ON u.id = r.user_id ORDER BY r.status = \'open\' DESC, r.id DESC LIMIT 100');
        if ($tab === 'registros') {
            $data['logs'] = DB::all('SELECT a.*, u.name, u.email, u.role FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.id DESC LIMIT 200');
            $data['emails'] = DB::all('SELECT id, to_email, template, status, attempts, last_error, created_at, sent_at FROM email_queue ORDER BY id DESC LIMIT 50');
        }
        if ($tab === 'operacao') {
            $data['cronLast'] = Settings::get('cron_last_run');
            $data['mailTransport'] = config('mail.transport');
        }
        View::render('trainer/settings', $data);
    }

    /** Somente chaves conhecidas, por aba. */
    private const EDITABLE = [
        'consultoria' => ['brand_name', 'trainer_name', 'accent_color'],
        'pagina' => ['public_headline', 'public_intro', 'public_about', 'contact_whatsapp', 'contact_whatsapp_label', 'contact_instagram', 'contact_email'],
        'notificacoes' => ['notify_trainer_new_signup', 'notify_trainer_assessment', 'notify_trainer_requests', 'notify_trainer_daily_digest', 'trainer_digest_hour', 'chat_digest_minutes'],
        'operacao' => ['billing_policy', 'billing_grace_days', 'workout_stale_days', 'default_self_eval_days', 'default_assessment_days', 'upload_image_mb', 'upload_video_mb', 'upload_audio_mb', 'upload_document_mb'],
        'lgpd' => ['retention_months_after_end'],
    ];

    private const CHECKBOXES = ['notify_trainer_new_signup', 'notify_trainer_assessment', 'notify_trainer_requests', 'notify_trainer_daily_digest'];
    private const NUMERIC = ['trainer_digest_hour' => [0, 23], 'chat_digest_minutes' => [5, 240], 'billing_grace_days' => [0, 60], 'workout_stale_days' => [7, 365], 'default_self_eval_days' => [1, 60], 'default_assessment_days' => [7, 365], 'upload_image_mb' => [1, 50], 'upload_video_mb' => [5, 1024], 'upload_audio_mb' => [1, 200], 'upload_document_mb' => [1, 100], 'retention_months_after_end' => [0, 120]];

    public function save(): void
    {
        $tab = (string) input('tab');
        if (!isset(self::EDITABLE[$tab])) abort(422);
        foreach (self::EDITABLE[$tab] as $key) {
            if (in_array($key, self::CHECKBOXES, true)) {
                $v = input($key) ? '1' : '0';
            } else {
                $v = trim((string) input($key, ''));
                if (isset(self::NUMERIC[$key])) {
                    [$min, $max] = self::NUMERIC[$key];
                    $v = (string) max($min, min($max, (int) $v));
                }
                if ($key === 'accent_color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $v)) $v = Settings::DEFAULTS['accent_color'];
                if ($key === 'billing_policy' && !in_array($v, ['off', 'restrict'], true)) $v = 'off';
                if ($key === 'contact_whatsapp') $v = preg_replace('/\D/', '', $v);
                if ($key === 'contact_instagram' && $v !== '' && !preg_match('~^https://(www\.)?instagram\.com/~', $v)) {
                    flash('error', 'Use o endereço completo do Instagram (https://www.instagram.com/...).');
                    redirect('/painel/configuracoes?aba=' . $tab);
                }
                if ($key === 'contact_email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    flash('error', 'E-mail de contato inválido.');
                    redirect('/painel/configuracoes?aba=' . $tab);
                }
                $v = mb_substr($v, 0, 4000);
            }
            Settings::set($key, $v);
        }
        Audit::log('settings.update', 'settings', null, ['tab' => $tab]);
        flash('success', 'Configurações salvas.');
        redirect('/painel/configuracoes?aba=' . $tab);
    }

    public function photo(): void
    {
        try {
            $f = Files::store($_FILES['photo'] ?? [], ['context' => 'public', 'kinds' => ['image'], 'sensitive' => 0]);
        } catch (UserError $e) {
            flash('error', $e->getMessage());
            redirect('/painel/configuracoes?aba=pagina');
        }
        $old = (int) Settings::get('public_photo_file_id');
        if ($old) Files::delete($old);
        Settings::set('public_photo_file_id', (string) $f['id']);
        flash('success', 'Foto da página pública atualizada.');
        redirect('/painel/configuracoes?aba=pagina');
    }

    public function modalities(): void
    {
        foreach (Modalities::all(false) as $m) {
            $name = mb_substr(trim((string) ($_POST['name'][$m['id']] ?? $m['name'])), 0, 80) ?: $m['name'];
            $fields = Modalities::sanitizeFields((array) ($_POST['fields'][$m['id']] ?? []));
            DB::update('modalities', [
                'name' => $name, 'active' => !empty($_POST['active'][$m['id']]) ? 1 : 0,
                'fields' => json_enc($fields ?: $m['fields']), 'sort_order' => (int) ($_POST['sort'][$m['id']] ?? $m['sort_order']),
            ], 'id = ?', [$m['id']]);
        }
        Audit::log('settings.modalities');
        flash('success', 'Modalidades atualizadas.');
        redirect('/painel/configuracoes?aba=modalidades');
    }

    public function resolveDataRequest(int $id): void
    {
        $r = DB::one('SELECT * FROM data_requests WHERE id = ?', [$id]) ?? abort(404);
        $response = mb_substr(trim((string) input('response', '')), 0, 3000);
        DB::update('data_requests', ['status' => 'done', 'response' => $response ?: null, 'resolved_at' => now()], 'id = ?', [$id]);
        Audit::log('lgpd.resolve', 'data_request', $id);
        flash('success', 'Solicitação marcada como atendida. Responda ao titular pelo e-mail cadastrado.');
        redirect('/painel/configuracoes?aba=lgpd');
    }

    public function testEmail(): void
    {
        $u = Auth::user();
        try {
            [$subject, $html, $text] = EmailTemplates::render('trainer_digest', ['name' => first_name($u['name']), 'lines' => ['Este é um e-mail de teste da configuração de envio.']]);
            Mailer::send($u['email'], $u['name'], 'Teste de e-mail — ' . Settings::get('brand_name'), $html, $text);
            flash('success', 'E-mail de teste enviado para ' . $u['email'] . '.');
        } catch (Throwable $e) {
            flash('error', 'Falha no envio: ' . $e->getMessage());
        }
        redirect('/painel/configuracoes?aba=operacao');
    }
}
