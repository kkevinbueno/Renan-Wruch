<?php
declare(strict_types=1);

// ───────────── Público e autenticação ─────────────
Router::get('/', [PublicController::class, 'home']);
Router::get('/privacidade', [PublicController::class, 'privacy']);
Router::get('/termos', [PublicController::class, 'terms']);
Router::get('/offline', [PublicController::class, 'offline']);

Router::get('/entrar', [AuthController::class, 'loginForm'], ['guest']);
Router::post('/entrar', [AuthController::class, 'login'], ['guest']);
Router::get('/cadastro', [AuthController::class, 'registerForm'], ['guest']);
Router::post('/cadastro', [AuthController::class, 'register'], ['guest']);
Router::get('/confirmar-email', [AuthController::class, 'verifyNotice']);
Router::post('/confirmar-email/reenviar', [AuthController::class, 'resendVerification']);
Router::get('/verificar-email', [AuthController::class, 'verify']);
Router::get('/esqueci-senha', [AuthController::class, 'forgotForm']);
Router::post('/esqueci-senha', [AuthController::class, 'forgot']);
Router::get('/redefinir-senha', [AuthController::class, 'resetForm']);
Router::post('/redefinir-senha', [AuthController::class, 'reset']);
Router::post('/sair', [AuthController::class, 'logout']);
Router::get('/auth/google', [AuthController::class, 'google']);
Router::get('/auth/google/callback', [AuthController::class, 'googleCallback']);
Router::get('/auth/apple', [AuthController::class, 'apple']);
Router::post('/auth/apple/callback', [AuthController::class, 'appleCallback'], [], false);
Router::get('/consentimento', [AuthController::class, 'consentForm'], ['auth']);
Router::post('/consentimento', [AuthController::class, 'consentSave'], ['auth']);
Router::get('/aguardando', [AuthController::class, 'pending'], ['auth']);
Router::post('/alternar-interface', [AuthController::class, 'switchInterface'], ['developer']);

Router::get('/media/{id}', [MediaController::class, 'show']);
Router::post('/webhooks/mercadopago', [WebhookController::class, 'mercadopago'], [], false);
Router::get('/cron', [WebhookController::class, 'cron']);

// ───────────── Interface do aluno ─────────────
Router::get('/app', [StudentController::class, 'dashboard'], ['app']);
Router::get('/app/situacao', [StudentController::class, 'situation'], ['student']);
Router::get('/app/avaliacao-inicial', [OnboardingController::class, 'show'], ['onboarding']);
Router::post('/app/avaliacao-inicial', [OnboardingController::class, 'save'], ['onboarding']);
Router::post('/app/avaliacao-inicial/foto', [OnboardingController::class, 'uploadPhoto'], ['onboarding']);
Router::post('/app/avaliacao-inicial/foto/{id}/excluir', [OnboardingController::class, 'deletePhoto'], ['onboarding']);

Router::get('/app/treinos', [StudentWorkoutController::class, 'index'], ['app']);
Router::get('/app/treinos/{id}', [StudentWorkoutController::class, 'show'], ['app']);
Router::post('/app/treinos/{id}/iniciar', [StudentWorkoutController::class, 'start'], ['app']);
Router::post('/app/treinos/{id}/situacao', [StudentWorkoutController::class, 'markStatus'], ['app']);
Router::post('/app/treinos/{id}/mudar-dia', [StudentWorkoutController::class, 'requestChange'], ['app']);
Router::get('/app/sessao/{id}', [StudentWorkoutController::class, 'session'], ['app']);
Router::post('/app/sessao/{id}/registro', [StudentWorkoutController::class, 'saveEntry'], ['app']);
Router::post('/app/sessao/{id}/concluir', [StudentWorkoutController::class, 'complete'], ['app']);
Router::get('/app/sessao/{id}/concluido', [StudentWorkoutController::class, 'done'], ['app']);
Router::get('/app/historico', [StudentWorkoutController::class, 'history'], ['app']);

Router::get('/app/autoavaliacao', [SelfAssessmentController::class, 'index'], ['app']);
Router::post('/app/autoavaliacao', [SelfAssessmentController::class, 'save'], ['app']);
Router::post('/app/autoavaliacao/lembrete', [SelfAssessmentController::class, 'reminderTime'], ['app']);
Router::get('/app/alimentacao', [NutritionController::class, 'index'], ['app']);
Router::post('/app/alimentacao', [NutritionController::class, 'save'], ['app']);
Router::post('/app/alimentacao/{id}/excluir', [NutritionController::class, 'delete'], ['app']);

Router::get('/app/avaliacoes', [AssessmentController::class, 'studentIndex'], ['app']);
Router::get('/app/avaliacoes/nova', [AssessmentController::class, 'studentNew'], ['app']);
Router::get('/app/avaliacoes/{id}', [AssessmentController::class, 'studentShow'], ['app']);
Router::post('/app/avaliacoes/{id}/foto', [AssessmentController::class, 'uploadPhoto'], ['app']);
Router::post('/app/avaliacoes/{id}/foto/{fid}/excluir', [AssessmentController::class, 'deletePhoto'], ['app']);
Router::post('/app/avaliacoes/{id}/enviar', [AssessmentController::class, 'submit'], ['app']);

Router::get('/app/calendario', [CalendarController::class, 'student'], ['app']);
Router::get('/app/mensagens', [ChatController::class, 'student'], ['chat']);
Router::get('/app/eu', [StudentController::class, 'me'], ['student']);
Router::get('/app/progresso', [StudentController::class, 'progress'], ['app']);
Router::get('/app/perfil', [ProfileController::class, 'show'], ['student']);
Router::post('/app/perfil', [ProfileController::class, 'update'], ['student']);
Router::post('/app/perfil/senha', [ProfileController::class, 'password'], ['student']);
Router::post('/app/perfil/avatar', [ProfileController::class, 'avatar'], ['student']);
Router::post('/app/perfil/notificacoes', [ProfileController::class, 'prefs'], ['student']);
Router::post('/app/perfil/lgpd', [ProfileController::class, 'dataRequest'], ['student']);
Router::get('/app/perfil/exportar', [ProfileController::class, 'export'], ['student']);
Router::get('/app/assinatura', [SubscriptionController::class, 'show'], ['student']);
Router::post('/app/assinatura/checkout', [SubscriptionController::class, 'checkout'], ['student']);
Router::get('/app/assinatura/retorno', [SubscriptionController::class, 'returned'], ['student']);
Router::post('/app/assinatura/{id}/cancelar', [SubscriptionController::class, 'cancel'], ['student']);

// Chat (compartilhado entre interfaces; autorização por conversa no controlador)
Router::get('/api/mensagens/{id}', [ChatController::class, 'poll'], ['auth']);
Router::post('/api/mensagens/{id}', [ChatController::class, 'send'], ['auth']);
Router::post('/api/mensagens/{id}/{mid}/apagar', [ChatController::class, 'delete'], ['auth']);
Router::get('/api/contadores', [ChatController::class, 'counters'], ['auth']);

// ───────────── Interface do Renan ─────────────
Router::get('/painel', [TrainerController::class, 'dashboard'], ['trainer']);
Router::get('/painel/alunos', [TrainerStudentsController::class, 'index'], ['trainer']);
Router::get('/painel/alunos/{id}', [TrainerStudentsController::class, 'show'], ['trainer']);
Router::post('/painel/alunos/{id}/aprovar', [TrainerStudentsController::class, 'approve'], ['trainer']);
Router::post('/painel/alunos/{id}/recusar', [TrainerStudentsController::class, 'reject'], ['trainer']);
Router::post('/painel/alunos/{id}/situacao', [TrainerStudentsController::class, 'status'], ['trainer']);
Router::post('/painel/alunos/{id}/reativar', [TrainerStudentsController::class, 'reactivate'], ['trainer']);
Router::post('/painel/alunos/{id}/configuracoes', [TrainerStudentsController::class, 'settings'], ['trainer']);
Router::post('/painel/alunos/{id}/excluir-dados', [TrainerStudentsController::class, 'purge'], ['trainer']);
Router::get('/painel/alunos/{id}/sessoes/{sid}', [TrainerStudentsController::class, 'sessionDetail'], ['trainer']);

Router::post('/painel/alunos/{id}/treinos', [TrainerWorkoutController::class, 'create'], ['trainer']);
Router::get('/painel/treinos/{id}', [TrainerWorkoutController::class, 'edit'], ['trainer']);
Router::post('/painel/treinos/{id}/dados', [TrainerWorkoutController::class, 'updateMeta'], ['trainer']);
Router::post('/painel/treinos/{id}/salvar', [TrainerWorkoutController::class, 'saveDraft'], ['trainer']);
Router::post('/painel/treinos/{id}/publicar', [TrainerWorkoutController::class, 'publish'], ['trainer']);
Router::post('/painel/treinos/{id}/desagendar', [TrainerWorkoutController::class, 'unschedule'], ['trainer']);
Router::post('/painel/treinos/{id}/descartar-rascunho', [TrainerWorkoutController::class, 'discardDraft'], ['trainer']);
Router::post('/painel/treinos/{id}/duplicar', [TrainerWorkoutController::class, 'duplicate'], ['trainer']);
Router::post('/painel/treinos/{id}/modelo', [TrainerWorkoutController::class, 'saveAsTemplate'], ['trainer']);
Router::post('/painel/treinos/{id}/arquivar', [TrainerWorkoutController::class, 'archive'], ['trainer']);
Router::get('/painel/treinos/{id}/versoes/{vid}', [TrainerWorkoutController::class, 'version'], ['trainer']);
Router::post('/painel/treinos/video', [TrainerWorkoutController::class, 'uploadVideo'], ['trainer']);
Router::get('/painel/modelos', [TrainerWorkoutController::class, 'templates'], ['trainer']);
Router::post('/painel/modelos', [TrainerWorkoutController::class, 'createTemplate'], ['trainer']);

Router::get('/painel/biblioteca', [LibraryController::class, 'index'], ['trainer']);
Router::get('/api/biblioteca', [LibraryController::class, 'search'], ['trainer']);
Router::get('/painel/biblioteca/novo', [LibraryController::class, 'form'], ['trainer']);
Router::post('/painel/biblioteca', [LibraryController::class, 'store'], ['trainer']);
Router::get('/painel/biblioteca/{id}', [LibraryController::class, 'show'], ['trainer']);
Router::get('/painel/biblioteca/{id}/editar', [LibraryController::class, 'form'], ['trainer']);
Router::post('/painel/biblioteca/{id}/favorito', [LibraryController::class, 'favorite'], ['trainer']);
Router::post('/painel/biblioteca/{id}', [LibraryController::class, 'store'], ['trainer']);
Router::post('/painel/biblioteca/{id}/duplicar', [LibraryController::class, 'duplicate'], ['trainer']);
Router::post('/painel/biblioteca/{id}/arquivar', [LibraryController::class, 'archive'], ['trainer']);

Router::get('/painel/mais', [TrainerController::class, 'more'], ['trainer']);
Router::get('/painel/solicitacoes', [TrainerController::class, 'requests'], ['trainer']);
Router::post('/painel/solicitacoes/{id}', [TrainerController::class, 'decideRequest'], ['trainer']);
Router::get('/painel/avaliacoes', [AssessmentController::class, 'trainerIndex'], ['trainer']);
Router::get('/painel/avaliacoes/{id}', [AssessmentController::class, 'trainerShow'], ['trainer']);
Router::post('/painel/avaliacoes/{id}', [AssessmentController::class, 'review'], ['trainer']);
Router::get('/painel/calendario', [CalendarController::class, 'trainer'], ['trainer']);
Router::post('/painel/eventos', [CalendarController::class, 'saveEvent'], ['trainer']);
Router::post('/painel/eventos/{id}/excluir', [CalendarController::class, 'deleteEvent'], ['trainer']);
Router::get('/painel/mensagens', [ChatController::class, 'trainerIndex'], ['trainer']);
Router::get('/painel/mensagens/{id}', [ChatController::class, 'trainerThread'], ['trainer']);
Router::get('/painel/financeiro', [FinanceController::class, 'index'], ['trainer']);
Router::post('/painel/planos', [FinanceController::class, 'savePlan'], ['trainer']);
Router::post('/painel/financeiro/sincronizar', [FinanceController::class, 'sync'], ['trainer']);
Router::get('/painel/configuracoes', [TrainerSettingsController::class, 'index'], ['trainer']);
Router::post('/painel/configuracoes', [TrainerSettingsController::class, 'save'], ['trainer']);
Router::post('/painel/configuracoes/foto', [TrainerSettingsController::class, 'photo'], ['trainer']);
Router::post('/painel/configuracoes/modalidades', [TrainerSettingsController::class, 'modalities'], ['trainer']);
Router::post('/painel/configuracoes/lgpd/{id}', [TrainerSettingsController::class, 'resolveDataRequest'], ['trainer']);
Router::post('/painel/conta/senha', [ProfileController::class, 'password'], ['trainer']);
Router::post('/painel/conta/avatar', [ProfileController::class, 'avatar'], ['trainer']);
Router::post('/painel/configuracoes/email-teste', [TrainerSettingsController::class, 'testEmail'], ['trainer']);
