<?php
declare(strict_types=1);

final class ProfileController
{
    public function show(): void
    {
        $u = Auth::user();
        $prefs = [];
        foreach (Notify::PREFS as $k => $label) {
            $prefs[$k] = ['label' => $label, 'enabled' => Notify::prefEnabled((int) $u['id'], $k)];
        }
        View::render('student/profile', [
            'title' => 'Meu perfil', 'nav' => 'profile', 'user' => $u, 'profile' => Students::profile((int) $u['id']),
            'prefs' => $prefs, 'requests' => DB::all('SELECT * FROM data_requests WHERE user_id = ? ORDER BY id DESC', [$u['id']]),
            'standing' => Billing::standing((int) $u['id']),
        ]);
    }

    public function update(): void
    {
        $name = mb_substr(trim((string) input('name', '')), 0, 120);
        if (mb_strlen($name) < 3) {
            flash('error', 'Informe seu nome completo.');
            redirect('/app/perfil');
        }
        $phone = mb_substr(preg_replace('/[^\d\+\-\(\) ]/', '', (string) input('phone', '')), 0, 30);
        $birth = (string) input('birth_date', '');
        DB::update('users', ['name' => $name, 'updated_at' => now()], 'id = ?', [Auth::id()]);
        DB::update('student_profiles', ['phone' => $phone ?: null, 'birth_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth) ? $birth : null, 'updated_at' => now()], 'user_id = ?', [Auth::id()]);
        flash('success', 'Dados atualizados.');
        redirect('/app/perfil');
    }

    public function password(): void
    {
        $u = Auth::user();
        $back = Auth::canTrainer() ? '/painel/configuracoes?aba=conta' : '/app/perfil';
        $new = (string) ($_POST['password'] ?? '');
        if ($u['password_hash'] && !Auth::verifyPassword($u, (string) ($_POST['current_password'] ?? ''))) {
            flash('error', 'Senha atual incorreta.');
            redirect($back);
        }
        if (mb_strlen($new) < 8 || $new !== (string) ($_POST['password_confirm'] ?? '')) {
            flash('error', 'A nova senha deve ter 8+ caracteres e as duas devem ser iguais.');
            redirect($back);
        }
        DB::update('users', ['password_hash' => Auth::hash($new), 'updated_at' => now()], 'id = ?', [$u['id']]);
        Audit::log('auth.password_change', 'user', (int) $u['id']);
        Notify::send((int) $u['id'], 'password_changed');
        // Mantém esta sessão válida e invalida as demais.
        $fresh = DB::one('SELECT * FROM users WHERE id = ?', [$u['id']]);
        $mode = $_SESSION['mode'] ?? null;
        Auth::login($fresh, 'password_change');
        if ($mode) $_SESSION['mode'] = $mode;
        flash('success', 'Senha alterada. Outras sessões foram encerradas.');
        redirect($back);
    }

    public function avatar(): void
    {
        $u = Auth::user();
        $back = Auth::canTrainer() ? '/painel/configuracoes?aba=conta' : '/app/perfil';
        try {
            $f = Files::store($_FILES['avatar'] ?? [], [
                'context' => 'avatar', 'student_id' => $u['role'] === 'trainer' ? null : (int) $u['id'], 'kinds' => ['image'], 'sensitive' => 0,
            ]);
        } catch (UserError $e) {
            flash('error', $e->getMessage());
            redirect($back);
        }
        Files::delete($u['avatar_file_id'] ? (int) $u['avatar_file_id'] : null);
        DB::update('users', ['avatar_file_id' => $f['id'], 'updated_at' => now()], 'id = ?', [$u['id']]);
        flash('success', 'Foto de perfil atualizada.');
        redirect($back);
    }

    public function prefs(): void
    {
        $uid = (int) Auth::id();
        foreach (Notify::PREFS as $k => $_) {
            $enabled = !empty($_POST['prefs'][$k]) ? 1 : 0;
            if (DB::val('SELECT id FROM notification_prefs WHERE user_id = ? AND pref_key = ?', [$uid, $k])) {
                DB::update('notification_prefs', ['enabled' => $enabled], 'user_id = ? AND pref_key = ?', [$uid, $k]);
            } else {
                DB::insert('notification_prefs', ['user_id' => $uid, 'pref_key' => $k, 'enabled' => $enabled]);
            }
        }
        flash('success', 'Preferências de e-mail salvas. Avisos essenciais (conta, segurança e pagamentos) continuam sendo enviados.');
        redirect('/app/perfil');
    }

    public function dataRequest(): void
    {
        $type = (string) input('type');
        $labels = ['access' => 'acesso aos dados', 'correction' => 'correção de dados', 'deletion' => 'exclusão de dados'];
        if (!isset($labels[$type])) {
            flash('error', 'Tipo de solicitação inválido.');
            redirect('/app/perfil');
        }
        $id = DB::insert('data_requests', [
            'user_id' => Auth::id(), 'request_type' => $type,
            'details' => mb_substr(trim((string) input('details', '')), 0, 3000) ?: null, 'status' => 'open', 'created_at' => now(),
        ]);
        Audit::log('lgpd.request', 'data_request', $id, ['type' => $type]);
        Notify::toTrainer('data_request', ['student' => Auth::user()['name'], 'type' => $labels[$type]], ['dedupe' => "lgpd:{$id}"]);
        flash('success', 'Solicitação registrada. Você receberá a resposta por e-mail em até 15 dias.');
        redirect('/app/perfil');
    }

    /** Portabilidade/acesso (LGPD art. 18): exporta os dados do titular em JSON. */
    public function export(): void
    {
        $uid = (int) Auth::id();
        $u = DB::one('SELECT id, email, name, status, created_at, consent_at, consent_version FROM users WHERE id = ?', [$uid]);
        $data = [
            'gerado_em' => now() . ' UTC',
            'conta' => $u,
            'perfil' => DB::one('SELECT phone, birth_date, cycle, onboarding_completed_at, next_assessment_on FROM student_profiles WHERE user_id = ?', [$uid]),
            'anamneses' => array_map(fn($a) => ['ciclo' => $a['cycle'], 'enviada_em' => $a['submitted_at'], 'respostas' => json_decode_array($a['data']), 'observacoes' => $a['personal_notes']], DB::all('SELECT * FROM anamneses WHERE student_id = ?', [$uid])),
            'autoavaliacoes' => DB::all('SELECT ref_date, scores, notes FROM self_assessments WHERE student_id = ? ORDER BY ref_date', [$uid]),
            'alimentacao' => DB::all('SELECT ref_date, meal, description, water_ml, notes FROM nutrition_logs WHERE student_id = ? ORDER BY ref_date', [$uid]),
            'avaliacoes_fisicas' => DB::all('SELECT due_on, submitted_at, measurements, student_notes, trainer_comments, trainer_guidance, status FROM physical_assessments WHERE student_id = ?', [$uid]),
            'treinos_realizados' => DB::all('SELECT s.scheduled_date, s.status, s.notes, w.name FROM workout_sessions s JOIN workouts w ON w.id = s.workout_id WHERE s.student_id = ? ORDER BY s.scheduled_date', [$uid]),
            'mensagens' => DB::all('SELECT created_at, CASE WHEN sender_id = student_id THEN \'aluno\' ELSE \'treinador\' END AS remetente, body FROM messages WHERE student_id = ? AND deleted_at IS NULL ORDER BY id', [$uid]),
            'arquivos' => DB::all('SELECT id, context, kind, original_name, created_at FROM files WHERE student_id = ? AND deleted_at IS NULL', [$uid]),
            'pagamentos' => DB::all('SELECT amount_cents, status, method, paid_at FROM payments WHERE student_id = ?', [$uid]),
        ];
        Audit::log('lgpd.export', 'user', $uid);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="meus-dados-wruch-personal.json"');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
