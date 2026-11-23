<?php
require __DIR__ . '/../app/bootstrap.php';
function conta($nome, $email, $senha) {
    $u = Auth::findByEmail($email) ?? Auth::createUser($nome, $email, $senha, true);
    DB::update('users', ['password_hash' => Auth::hash($senha), 'email_verified_at' => now(), 'consent_at' => now(), 'consent_version' => config('app.consent_version')], 'id = ?', [$u['id']]);
    return Auth::syncRole(Auth::findByEmail($email));
}
function aprovar($u, $avaliacaoFeita) {
    if (in_array($u['status'], ['pending', 'rejected'], true)) Students::approve((int) $u['id']);
    if ($avaliacaoFeita) DB::update('student_profiles', ['onboarding_completed_at' => now(), 'next_assessment_on' => date_add_days(today(), 30)], 'user_id = ?', [$u['id']]);
}
$renan = conta('Renan Wruch', 'renanrdw@gmail.com', 'teste12345');
aprovar(conta('Aluno Teste', 'aluno@teste.com', 'teste12345'), true);
aprovar(conta('Aluno Novo', 'novo@teste.com', 'teste12345'), false);
echo "Pronto! Senha de todas as contas: teste12345\n";
