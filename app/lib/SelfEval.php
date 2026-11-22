<?php
declare(strict_types=1);

/** Escala de autoavaliação (1–5). As notas são percepções do aluno, não diagnósticos. */
final class SelfEval
{
    public const SCALE = [
        1 => ['emoji' => '😞', 'label' => 'Muito ruim'],
        2 => ['emoji' => '🙁', 'label' => 'Ruim'],
        3 => ['emoji' => '😐', 'label' => 'Regular'],
        4 => ['emoji' => '🙂', 'label' => 'Bom'],
        5 => ['emoji' => '😁', 'label' => 'Excelente'],
    ];

    public const DESCRIPTIONS = [
        'sono' => [1 => 'Dormi muito pouco ou acordei várias vezes.', 2 => 'Dormi menos que o normal e acordei cansado.', 3 => 'Sono razoável, nem bom nem ruim.', 4 => 'Dormi bem na maior parte dos dias.', 5 => 'Sono profundo e acordei descansado.'],
        'alimentacao' => [1 => 'Comi de forma muito desorganizada.', 2 => 'Pulei refeições ou exagerei com frequência.', 3 => 'Alimentação razoável, com alguns deslizes.', 4 => 'Comi bem e com regularidade.', 5 => 'Alimentação organizada e do jeito planejado.'],
        'treinamento' => [1 => 'Não consegui treinar ou os treinos foram muito ruins.', 2 => 'Treinei pouco ou com muita dificuldade.', 3 => 'Treinei, mas abaixo do esperado.', 4 => 'Cumpri os treinos com bom rendimento.', 5 => 'Treinos completos e com ótimo rendimento.'],
        'cardio' => [1 => 'Não fiz o cardio previsto.', 2 => 'Fiz bem menos que o previsto.', 3 => 'Fiz parte do previsto.', 4 => 'Fiz quase tudo o que estava previsto.', 5 => 'Fiz todo o cardio previsto, me sentindo bem.'],
        'disposicao' => [1 => 'Muito cansado na maior parte do tempo.', 2 => 'Pouca energia em vários momentos.', 3 => 'Energia oscilando ao longo dos dias.', 4 => 'Boa disposição na maior parte do tempo.', 5 => 'Muita energia e disposição.'],
        'recuperacao' => [1 => 'Muito dolorido, sem conseguir me recuperar.', 2 => 'Recuperação lenta entre os treinos.', 3 => 'Recuperação razoável.', 4 => 'Recupero bem entre os treinos.', 5 => 'Totalmente recuperado para treinar.'],
        'motivacao' => [1 => 'Sem nenhuma vontade de treinar.', 2 => 'Pouca motivação.', 3 => 'Motivação oscilando.', 4 => 'Motivado na maior parte do tempo.', 5 => 'Muito motivado e engajado.'],
        'hidratacao' => [1 => 'Bebi muito pouca água.', 2 => 'Bebi menos água do que deveria.', 3 => 'Hidratação razoável.', 4 => 'Bebi água regularmente.', 5 => 'Me hidratei muito bem todos os dias.'],
    ];

    /** Indicadores simples de atenção: notas baixas (≤ 2) ou queda de 2+ pontos em relação à anterior. */
    public static function attention(array $scores, ?array $previous): array
    {
        $flags = [];
        foreach ($scores as $cat => $v) {
            $v = (int) $v;
            if ($v <= 2) {
                $flags[$cat] = 'nota baixa';
            } elseif ($previous && isset($previous[$cat]) && (int) $previous[$cat] - $v >= 2) {
                $flags[$cat] = 'queda';
            }
        }
        return $flags;
    }
}
