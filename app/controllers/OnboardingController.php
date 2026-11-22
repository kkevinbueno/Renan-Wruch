<?php
declare(strict_types=1);

final class OnboardingController
{
    public const SLOTS = ['front' => 'Frente', 'side' => 'Lado', 'back' => 'Costas'];

    public function show(): void
    {
        $uid = (int) Auth::id();
        $profile = Students::profile($uid);
        if ($profile['onboarding_completed_at']) {
            flash('info', 'Sua avaliação inicial já foi enviada.');
            redirect('/app');
        }
        $a = Anamnesis::current($uid);
        if (empty($a['answers']['full_name'])) {
            $a['answers']['full_name'] = Auth::user()['name'];
        }
        View::render('student/onboarding', [
            'title' => 'Avaliação inicial',
            'nav' => '',
            'anamnesis' => $a,
            'sections' => Anamnesis::sections(),
            'photos' => Files::forContext('anamnesis', (int) $a['id']),
            'slots' => self::SLOTS,
        ]);
    }

    public function save(): void
    {
        $uid = (int) Auth::id();
        $profile = Students::profile($uid);
        if ($profile['onboarding_completed_at']) {
            is_json_request() ? json_out(['ok' => false, 'error' => 'Avaliação já enviada.'], 409) : redirect('/app');
        }
        $a = Anamnesis::current($uid);
        $data = Anamnesis::clean((array) ($_POST['a'] ?? input('a', [])));
        $notes = mb_substr(trim((string) input('personal_notes', '')), 0, 6000);
        DB::update('anamneses', ['data' => json_enc($data), 'personal_notes' => $notes ?: null, 'updated_at' => now()], 'id = ?', [$a['id']]);

        if (input('action') !== 'submit') {
            if (is_json_request()) json_out(['ok' => true, 'saved_at' => fmt_time(now())]);
            flash('success', 'Rascunho salvo. Você pode continuar depois.');
            redirect('/app/avaliacao-inicial');
        }

        $missing = Anamnesis::missing($data);
        $photos = Files::forContext('anamnesis', (int) $a['id']);
        $kinds = array_column($photos, 'kind');
        foreach (self::SLOTS as $k => $label) {
            if (!in_array($k, $kinds, true)) $missing[] = "Fotografia de {$label}";
        }
        if ($missing) {
            flash('error', 'Faltam informações obrigatórias: ' . implode('; ', array_slice($missing, 0, 6)) . (count($missing) > 6 ? '…' : ''));
            redirect('/app/avaliacao-inicial');
        }
        if (empty($_POST['photo_consent'])) {
            flash('error', 'Confirme a autorização de uso das fotografias para o acompanhamento.');
            redirect('/app/avaliacao-inicial');
        }
        $mainModality = DB::val('SELECT id FROM modalities WHERE slug = ?', [$data['main_modality']]);
        DB::transaction(function () use ($a, $uid, $data, $mainModality, $profile) {
            DB::update('anamneses', ['status' => 'submitted', 'submitted_at' => now(), 'updated_at' => now()], 'id = ?', [$a['id']]);
            DB::update('student_profiles', [
                'onboarding_completed_at' => now(),
                'phone' => mb_substr($data['phone'], 0, 30),
                'birth_date' => $data['birth_date'] ?: null,
                'modality_id' => $mainModality ?: null,
                'next_assessment_on' => $profile['next_assessment_on'] ?: date_add_days(today(), max(7, (int) $profile['assessment_every_days'])),
                'updated_at' => now(),
            ], 'user_id = ?', [$uid]);
            if (trim($data['full_name']) !== '') {
                DB::update('users', ['name' => mb_substr($data['full_name'], 0, 120), 'updated_at' => now()], 'id = ?', [$uid]);
            }
        });
        Audit::log('onboarding.submit', 'anamnesis', (int) $a['id']);
        Notify::toTrainer('onboarding_submitted', ['student' => Auth::user()['name'], 'link' => url("/painel/alunos/{$uid}", ['aba' => 'dados'])], ['dedupe' => 'onboard:' . $a['id']]);
        flash('success', 'Avaliação inicial enviada! O Renan já foi avisado e vai preparar o seu treino.');
        redirect('/app?enviado=1');
    }

    public function uploadPhoto(): void
    {
        $uid = (int) Auth::id();
        if (Students::profile($uid)['onboarding_completed_at']) json_out(['ok' => false, 'error' => 'Avaliação já enviada.'], 409);
        if (!RateLimit::hit("upload:{$uid}", 60, 600)) json_out(['ok' => false, 'error' => 'Muitos envios seguidos. Aguarde alguns minutos.'], 429);
        $a = Anamnesis::current($uid);
        $slot = (string) input('slot', 'extra');
        if (!isset(self::SLOTS[$slot]) && $slot !== 'extra') json_out(['ok' => false, 'error' => 'Posição inválida.'], 422);
        try {
            $file = Files::store($_FILES['photo'] ?? [], [
                'context' => 'anamnesis', 'context_id' => (int) $a['id'], 'student_id' => $uid, 'kinds' => ['image'], 'kind_label' => $slot, 'sensitive' => 1,
            ]);
        } catch (UserError $e) {
            json_out(['ok' => false, 'error' => $e->getMessage()], 422);
        }
        if ($slot !== 'extra') {
            foreach (DB::all("SELECT id FROM files WHERE context = 'anamnesis' AND context_id = ? AND kind = ? AND id <> ? AND deleted_at IS NULL", [$a['id'], $slot, $file['id']]) as $old) {
                Files::delete((int) $old['id']);
            }
        } elseif (count(DB::col("SELECT id FROM files WHERE context = 'anamnesis' AND context_id = ? AND kind = 'extra' AND deleted_at IS NULL", [$a['id']])) > 6) {
            Files::delete((int) $file['id']);
            json_out(['ok' => false, 'error' => 'Limite de 6 fotos adicionais.'], 422);
        }
        json_out(['ok' => true, 'id' => (int) $file['id'], 'url' => Files::url((int) $file['id']), 'slot' => $slot]);
    }

    public function deletePhoto(int $id): void
    {
        $uid = (int) Auth::id();
        $a = Anamnesis::current($uid);
        $f = Files::find($id);
        if (!$f || $f['context'] !== 'anamnesis' || (int) $f['context_id'] !== (int) $a['id'] || (int) $f['student_id'] !== $uid || $a['status'] !== 'draft') {
            json_out(['ok' => false, 'error' => 'Foto não encontrada.'], 404);
        }
        Files::delete($id);
        json_out(['ok' => true]);
    }
}
