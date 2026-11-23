<?php
declare(strict_types=1);

/** Modelos de e-mail. Textos curtos, sem dados sensíveis. */
final class EmailTemplates
{
    public static function render(string $template, array $v): array
    {
        $brand = (string) Settings::get('brand_name');
        $trainer = (string) Settings::get('trainer_name');
        $hi = !empty($v['name']) ? 'Olá, ' . $v['name'] . '!' : 'Olá!';
        $t = match ($template) {
            'verify_email' => ["Confirme seu e-mail — {$brand}", $hi, ['Para continuar seu cadastro no ' . $brand . ', confirme seu endereço de e-mail.', 'O link vale por 48 horas.'], 'Confirmar e-mail', $v['link']],
            'password_reset' => ["Redefinição de senha — {$brand}", $hi, ['Recebemos um pedido para redefinir sua senha.', 'Se não foi você, ignore este e-mail — sua senha continua a mesma. O link vale por 1 hora.'], 'Criar nova senha', $v['link']],
            'password_changed' => ["Sua senha foi alterada — {$brand}", $hi, ['A senha da sua conta foi alterada agora há pouco.', 'Se não foi você, redefina a senha imediatamente e fale com o ' . $trainer . '.'], 'Acessar minha conta', url('/entrar')],
            'signup_pending' => ["Novo cadastro aguardando aprovação", 'Olá, ' . first_name($trainer) . '!', [($v['student'] ?? 'Um novo aluno') . ' confirmou o e-mail e aguarda sua aprovação.'], 'Revisar cadastro', $v['link']],
            'approved' => ["Seu cadastro foi aprovado — {$brand}", $hi, ['Seu cadastro na consultoria foi aprovado pelo ' . $trainer . '.', 'O próximo passo é preencher a avaliação inicial (anamnese e fotografias) para que ele monte o seu treino.'], 'Começar avaliação inicial', url('/app/avaliacao-inicial')],
            'rejected' => ["Sobre o seu cadastro — {$brand}", $hi, ['Seu cadastro na consultoria não foi aprovado neste momento.', !empty($v['note']) ? 'Observação: ' . $v['note'] : 'Se quiser mais informações, fale diretamente com o ' . $trainer . '.'], 'Falar no WhatsApp', 'https://wa.me/' . Settings::get('contact_whatsapp')],
            'onboarding_submitted' => ["Avaliação inicial recebida", 'Olá, ' . first_name($trainer) . '!', [($v['student'] ?? 'Um aluno') . ' concluiu a avaliação inicial (anamnese e fotografias).'], 'Ver avaliação', $v['link']],
            'workout_published' => ["Seu treino foi atualizado — {$brand}", $hi, ['O ' . $trainer . ' publicou ' . ($v['workout'] ?? 'um novo treino') . ' para você.'], 'Ver treino', url('/app/treinos')],
            'calendar_changed' => ["Atualização no seu calendário — {$brand}", $hi, ['Houve uma alteração na sua programação: ' . ($v['summary'] ?? 'confira o calendário') . '.'], 'Abrir calendário', url('/app/calendario')],
            'assessment_received' => ["Avaliação física recebida", 'Olá, ' . first_name($trainer) . '!', [($v['student'] ?? 'Um aluno') . ' enviou uma nova avaliação física.'], 'Revisar avaliação', $v['link']],
            'assessment_reviewed' => ["Sua avaliação física foi revisada — {$brand}", $hi, ['O ' . $trainer . ' revisou sua avaliação física e deixou orientações.'], 'Ver orientações', $v['link']],
            'assessment_reminder' => ["Lembrete: avaliação física " . ($v['when'] ?? 'em breve'), $hi, ['Sua próxima avaliação física está marcada para ' . ($v['date'] ?? '') . '.', 'Você vai precisar de fotos de frente, de lado e de costas.'], 'Enviar avaliação', url('/app/avaliacoes/nova')],
            'assessment_overdue' => ["Sua avaliação física está atrasada — {$brand}", $hi, ['A avaliação física prevista para ' . ($v['date'] ?? '') . ' ainda não foi enviada.'], 'Enviar agora', url('/app/avaliacoes/nova')],
            'assessment_overdue_trainer' => ["Avaliação física atrasada", 'Olá, ' . first_name($trainer) . '!', [($v['student'] ?? 'Um aluno') . ' ainda não enviou a avaliação prevista para ' . ($v['date'] ?? '') . '.'], 'Ver aluno', $v['link']],
            'self_eval_reminder' => ["Hora da sua autoavaliação — {$brand}", $hi, ['Leva menos de um minuto: conte como estão seu sono, alimentação, treino e disposição.'], 'Preencher autoavaliação', url('/app/autoavaliacao')],
            'activity_reminder' => ["Lembrete: " . ($v['title'] ?? 'atividade agendada'), $hi, ['Você tem uma atividade agendada para ' . ($v['when'] ?? 'amanhã') . '.'], 'Ver calendário', url('/app/calendario')],
            'reschedule_request' => ["Pedido de mudança de treino", 'Olá, ' . first_name($trainer) . '!', [($v['student'] ?? 'Um aluno') . ' pediu para mudar um treino de dia.'], 'Revisar pedido', url('/painel/solicitacoes')],
            'reschedule_decided' => ["Seu pedido de mudança foi " . ($v['decision'] ?? 'respondido'), $hi, ['O ' . $trainer . ' ' . ($v['decision'] ?? 'respondeu') . ' seu pedido para mudar ' . ($v['workout'] ?? 'o treino') . '.'], 'Ver calendário', url('/app/calendario')],
            'chat_unread' => ["Você tem mensagens não lidas — {$brand}", $hi, ['Você recebeu novas mensagens de ' . ($v['from'] ?? 'sua consultoria') . ' na plataforma.'], 'Abrir conversa', $v['link']],
            'payment_approved' => ["Pagamento confirmado — {$brand}", $hi, ['Recebemos a confirmação do seu pagamento de ' . ($v['amount'] ?? '') . '.'], 'Ver assinatura', url('/app/assinatura')],
            'payment_failed' => ["Não conseguimos processar seu pagamento — {$brand}", $hi, ['O pagamento da sua assinatura não foi aprovado.', 'Confira os dados do cartão ou escolha outra forma de pagamento.'], 'Regularizar pagamento', url('/app/assinatura')],
            'payment_due' => ["Sua assinatura vence em breve — {$brand}", $hi, ['Seu período atual termina em ' . ($v['date'] ?? '') . '.'], 'Renovar', url('/app/assinatura')],
            'subscription_cancelled' => ["Renovação cancelada — {$brand}", $hi, ['A renovação automática da sua assinatura foi cancelada. Seu histórico continua salvo.'], 'Ver assinatura', url('/app/assinatura')],
            'payment_failed_trainer' => ["Pagamento recusado", 'Olá, ' . first_name($trainer) . '!', ['O pagamento de ' . ($v['student'] ?? 'um aluno') . ' foi recusado.'], 'Ver financeiro', url('/painel/financeiro')],
            'data_request' => ["Solicitação de dados (LGPD)", 'Olá, ' . first_name($trainer) . '!', [($v['student'] ?? 'Um aluno') . ' registrou uma solicitação de ' . ($v['type'] ?? 'dados') . '. O prazo legal de resposta é de até 15 dias.'], 'Ver solicitação', url('/painel/configuracoes?aba=lgpd')],
            'trainer_digest' => ["Resumo do dia — {$brand}", 'Bom dia, ' . first_name($trainer) . '!', $v['lines'] ?? [], 'Abrir painel', url('/painel')],
            default => throw new InvalidArgumentException("Modelo de e-mail desconhecido: {$template}"),
        };
        [$subject, $greeting, $paragraphs, $cta, $link] = $t;
        return [$subject, self::html($greeting, $paragraphs, $cta, $link, $brand), self::text($greeting, $paragraphs, $cta, $link, $brand)];
    }

    private static function html(string $greeting, array $paragraphs, string $cta, string $link, string $brand): string
    {
        $p = '';
        foreach ($paragraphs as $para) {
            $p .= '<p style="margin:0 0 14px;line-height:1.6;color:#3f3f46">' . e($para) . '</p>';
        }
        return '<!doctype html><html lang="pt-BR"><body style="margin:0;background:#f4f4f5;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:32px 12px"><tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border:1px solid #e4e4e7;border-radius:12px;padding:32px">'
            . '<tr><td style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#71717a;padding-bottom:20px">' . e($brand) . '</td></tr>'
            . '<tr><td><h1 style="font-size:20px;margin:0 0 16px;color:#18181b">' . e($greeting) . '</h1>' . $p
            . '<p style="margin:24px 0 8px"><a href="' . e($link) . '" style="display:inline-block;background:#0e0b0c;color:#ffffff;text-decoration:none;padding:13px 24px;border-radius:999px;font-weight:600;letter-spacing:.01em">' . e($cta) . '</a></p>'
            . '<p style="font-size:12px;color:#a1a1aa;margin-top:24px;line-height:1.5">Se o botão não funcionar, copie este endereço no navegador:<br>' . e($link) . '</p>'
            . '</td></tr></table>'
            . '<p style="font-size:12px;color:#a1a1aa;margin-top:16px">Você recebe este e-mail por ter uma conta no ' . e($brand) . '. Preferências: ' . e(url('/app/perfil')) . '</p>'
            . '</td></tr></table></body></html>';
    }

    private static function text(string $greeting, array $paragraphs, string $cta, string $link, string $brand): string
    {
        return $greeting . "\n\n" . implode("\n\n", $paragraphs) . "\n\n{$cta}: {$link}\n\n— {$brand}\n";
    }
}
