<?php
declare(strict_types=1);

/** Estrutura da anamnese (avaliação inicial). Usada para renderizar, validar e exibir ao Renan. */
final class Anamnesis
{
    public static function sections(): array
    {
        $modalities = [];
        foreach (Modalities::all() as $m) {
            $modalities[$m['slug']] = $m['name'];
        }
        $days = array_combine(['1', '2', '3', '4', '5', '6', '0'], ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo']);
        return [
            'pessoal' => ['title' => 'Informações pessoais', 'step' => 1, 'fields' => [
                ['key' => 'full_name', 'label' => 'Nome completo', 'type' => 'text', 'required' => true],
                ['key' => 'birth_date', 'label' => 'Data de nascimento', 'type' => 'date', 'required' => true],
                ['key' => 'phone', 'label' => 'Telefone / WhatsApp', 'type' => 'tel', 'required' => true],
                ['key' => 'city', 'label' => 'Cidade', 'type' => 'text'],
                ['key' => 'occupation', 'label' => 'Profissão ou ocupação', 'type' => 'text'],
                ['key' => 'height_cm', 'label' => 'Altura (cm)', 'type' => 'number', 'min' => 100, 'max' => 250],
                ['key' => 'weight_kg', 'label' => 'Peso (kg)', 'type' => 'number', 'step' => '0.1', 'min' => 25, 'max' => 300],
            ]],
            'objetivos' => ['title' => 'Objetivos', 'step' => 2, 'fields' => [
                ['key' => 'goals', 'label' => 'Quais são seus objetivos com a consultoria?', 'type' => 'checkboxes', 'required' => true, 'options' => [
                    'performance' => 'Melhorar o desempenho no meu esporte', 'condicionamento' => 'Melhorar o condicionamento físico',
                    'forca' => 'Ganhar força', 'massa' => 'Ganhar massa muscular', 'composicao' => 'Reduzir gordura corporal',
                    'saude' => 'Saúde e qualidade de vida', 'retorno' => 'Voltar a treinar depois de uma pausa', 'competicao' => 'Preparação para competição',
                ]],
                ['key' => 'goal_details', 'label' => 'Conte com suas palavras o que você quer alcançar', 'type' => 'textarea', 'required' => true],
                ['key' => 'goal_deadline', 'label' => 'Existe alguma data, prova ou competição em vista?', 'type' => 'text'],
            ]],
            'modalidades' => ['title' => 'Modalidades', 'step' => 2, 'fields' => [
                ['key' => 'modalities', 'label' => 'Quais modalidades você pratica ou quer praticar com o acompanhamento?', 'type' => 'checkboxes', 'required' => true, 'options' => $modalities],
                ['key' => 'main_modality', 'label' => 'Modalidade principal', 'type' => 'select', 'required' => true, 'options' => $modalities],
                ['key' => 'sport_level', 'label' => 'Seu nível na modalidade principal', 'type' => 'select', 'options' => ['iniciante' => 'Iniciante', 'intermediario' => 'Intermediário', 'avancado' => 'Avançado', 'competitivo' => 'Competitivo']],
                ['key' => 'other_activities', 'label' => 'Outras atividades físicas que você faz', 'type' => 'text'],
            ]],
            'experiencia' => ['title' => 'Experiência com exercícios', 'step' => 2, 'fields' => [
                ['key' => 'training_experience', 'label' => 'Há quanto tempo você treina com regularidade?', 'type' => 'select', 'required' => true, 'options' => ['nunca' => 'Não treino atualmente', 'menos6m' => 'Menos de 6 meses', '6m2a' => 'De 6 meses a 2 anos', 'mais2a' => 'Mais de 2 anos']],
                ['key' => 'current_training', 'label' => 'Como são seus treinos hoje (frequência, tipo, duração)?', 'type' => 'textarea'],
                ['key' => 'had_coach', 'label' => 'Já treinou com acompanhamento profissional?', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
            ]],
            'rotina' => ['title' => 'Rotina semanal', 'step' => 3, 'fields' => [
                ['key' => 'week_routine', 'label' => 'Como é sua semana (trabalho, estudos, deslocamentos, compromissos fixos)?', 'type' => 'textarea', 'required' => true],
                ['key' => 'sleep_hours', 'label' => 'Quantas horas você costuma dormir por noite?', 'type' => 'select', 'options' => ['<5' => 'Menos de 5 h', '5-6' => '5 a 6 h', '6-7' => '6 a 7 h', '7-8' => '7 a 8 h', '>8' => 'Mais de 8 h']],
                ['key' => 'stress', 'label' => 'Como você avalia seu nível de estresse no dia a dia?', 'type' => 'select', 'options' => ['baixo' => 'Baixo', 'moderado' => 'Moderado', 'alto' => 'Alto']],
            ]],
            'disponibilidade' => ['title' => 'Disponibilidade para treinar', 'step' => 3, 'fields' => [
                ['key' => 'available_days', 'label' => 'Dias disponíveis', 'type' => 'checkboxes', 'required' => true, 'options' => $days],
                ['key' => 'session_time', 'label' => 'Tempo disponível por treino', 'type' => 'select', 'required' => true, 'options' => ['30' => 'Até 30 min', '45' => 'Cerca de 45 min', '60' => 'Cerca de 1 h', '90' => '1 h 30 ou mais']],
                ['key' => 'preferred_period', 'label' => 'Período preferido', 'type' => 'select', 'options' => ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite', 'variavel' => 'Varia']],
                ['key' => 'locations', 'label' => 'Onde você pode treinar?', 'type' => 'checkboxes', 'options' => ['academia' => 'Academia', 'quadra' => 'Quadra de tênis', 'areia' => 'Quadra de areia', 'piscina' => 'Piscina', 'casa' => 'Em casa', 'ar_livre' => 'Ao ar livre']],
                ['key' => 'equipment', 'label' => 'Equipamentos que você tem à disposição', 'type' => 'textarea'],
            ]],
            'saude' => ['title' => 'Limitações e lesões', 'step' => 4, 'fields' => [
                ['key' => 'limitations', 'label' => 'Possui alguma limitação física relevante para o treino?', 'type' => 'textarea', 'hint' => 'Escreva "nenhuma" se não houver.', 'required' => true],
                ['key' => 'injuries', 'label' => 'Histórico de lesões', 'type' => 'textarea', 'hint' => 'Quais, quando e se ainda incomodam. Escreva "nenhuma" se não houver.', 'required' => true],
                ['key' => 'surgeries', 'label' => 'Cirurgias que possam influenciar o treino', 'type' => 'textarea'],
                ['key' => 'pain_now', 'label' => 'Sente dor ao se exercitar atualmente?', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                ['key' => 'medical_restriction', 'label' => 'Algum profissional de saúde restringiu sua atividade física?', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'nao_sei' => 'Não sei']],
                ['key' => 'medical_restriction_details', 'label' => 'Se sim, qual restrição?', 'type' => 'textarea'],
            ]],
            'outros' => ['title' => 'Outras informações', 'step' => 4, 'fields' => [
                ['key' => 'other_info', 'label' => 'Algo mais que seja importante para o planejamento?', 'type' => 'textarea'],
            ]],
        ];
    }

    public static function fields(): array
    {
        $all = [];
        foreach (self::sections() as $s) {
            foreach ($s['fields'] as $f) {
                $all[$f['key']] = $f;
            }
        }
        return $all;
    }

    /** Filtra os valores enviados para as chaves e opções conhecidas. */
    public static function clean(array $input): array
    {
        $out = [];
        foreach (self::fields() as $key => $f) {
            $v = $input[$key] ?? null;
            if (in_array($f['type'], ['checkboxes'], true)) {
                $v = array_values(array_intersect(array_map('strval', (array) ($v ?? [])), array_map('strval', array_keys($f['options']))));
            } elseif (in_array($f['type'], ['select', 'radio'], true)) {
                $v = is_string($v) && array_key_exists($v, $f['options']) ? $v : '';
            } else {
                $v = mb_substr(trim((string) ($v ?? '')), 0, $f['type'] === 'textarea' ? 4000 : 200);
                if ($f['type'] === 'date' && $v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) $v = '';
                if ($f['type'] === 'number' && $v !== '' && !is_numeric(str_replace(',', '.', $v))) $v = '';
            }
            $out[$key] = $v;
        }
        return $out;
    }

    public static function missing(array $data): array
    {
        $missing = [];
        foreach (self::fields() as $key => $f) {
            if (empty($f['required'])) continue;
            $v = $data[$key] ?? '';
            if ($v === '' || $v === []) {
                $missing[] = $f['label'];
            }
        }
        return $missing;
    }

    /** Valor legível para exibição. */
    public static function display(array $f, mixed $v): string
    {
        if (is_array($v)) {
            return implode(', ', array_map(fn($k) => $f['options'][$k] ?? $k, $v)) ?: '—';
        }
        if (isset($f['options'])) {
            return $f['options'][$v] ?? ($v ?: '—');
        }
        if ($f['type'] === 'date' && $v) return fmt_date_short($v);
        return $v === '' || $v === null ? '—' : (string) $v;
    }

    public static function current(int $studentId): array
    {
        $p = Students::profile($studentId);
        $row = DB::one('SELECT * FROM anamneses WHERE student_id = ? AND cycle = ? ORDER BY id DESC LIMIT 1', [$studentId, $p['cycle']]);
        if (!$row) {
            // Novo ciclo: começa com as respostas anteriores para o aluno apenas revisar.
            $prev = DB::one('SELECT data FROM anamneses WHERE student_id = ? ORDER BY id DESC LIMIT 1', [$studentId]);
            $id = DB::insert('anamneses', ['student_id' => $studentId, 'cycle' => $p['cycle'], 'data' => $prev['data'] ?? null, 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);
            $row = DB::one('SELECT * FROM anamneses WHERE id = ?', [$id]);
        }
        $row['answers'] = json_decode_array($row['data']);
        return $row;
    }

    public static function latestSubmitted(int $studentId): ?array
    {
        $row = DB::one("SELECT * FROM anamneses WHERE student_id = ? AND status = 'submitted' ORDER BY id DESC LIMIT 1", [$studentId]);
        if ($row) $row['answers'] = json_decode_array($row['data']);
        return $row;
    }
}
