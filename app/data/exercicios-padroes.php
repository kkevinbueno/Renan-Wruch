<?php
/**
 * Padrões de movimento da biblioteca: descrição do tipo de exercício, orientações de execução
 * e erros comuns. Cada exercício do catálogo aponta para um padrão e pode acrescentar uma nota
 * específica (pegada, ajuste, ênfase). Textos próprios do Wruch Personal.
 *
 * tipo   => frase que abre a descrição ("Exercício … para <músculo>")
 * passos => orientações de execução, em ordem
 * erros  => cuidados e erros comuns
 */
return [

'supino' => [
    'tipo' => 'Exercício multiarticular de empurrar na horizontal',
    'passos' => [
        'Deite no banco com os pés firmes no chão e os olhos alinhados logo abaixo da barra (ou com os halteres apoiados nas coxas).',
        'Aproxime e abaixe as escápulas, mantendo o peito elevado e a curvatura natural da lombar.',
        'Desça a carga de forma controlada até a altura do meio do peito, com os cotovelos entre 45° e 70° em relação ao tronco.',
        'Empurre de volta até estender os braços, sem perder a posição das escápulas.',
    ],
    'erros' => [
        'Tirar o quadril do banco durante a subida.',
        'Abrir os cotovelos a 90°, sobrecarregando os ombros.',
        'Quicar a carga no peito ou encurtar a amplitude.',
        'Deixar os punhos dobrados para trás.',
    ],
],

'supino_inclinado' => [
    'tipo' => 'Exercício multiarticular de empurrar em plano inclinado',
    'passos' => [
        'Ajuste o encosto do banco entre 30° e 45° e apoie bem os pés no chão.',
        'Aproxime e abaixe as escápulas, mantendo o peito elevado.',
        'Desça a carga de forma controlada até a parte superior do peito, com os cotovelos levemente fechados em relação ao tronco.',
        'Empurre até estender os braços, mantendo a carga alinhada sobre os ombros.',
    ],
    'erros' => [
        'Usar o banco inclinado demais, transferindo o trabalho para os ombros.',
        'Tirar o quadril do banco.',
        'Descer a carga até o meio do peito, saindo do trajeto da variação.',
    ],
],

'supino_declinado' => [
    'tipo' => 'Exercício multiarticular de empurrar em plano declinado',
    'passos' => [
        'Prenda as pernas no apoio do banco declinado e deite com a cabeça na parte mais baixa.',
        'Aproxime e abaixe as escápulas.',
        'Desça a carga de forma controlada até a parte inferior do peito.',
        'Empurre até estender os braços.',
    ],
    'erros' => [
        'Declinação excessiva, que dificulta a respiração e o controle da carga.',
        'Descer a carga rápido demais.',
        'Pedir ajuda para retirar e devolver a barra é recomendado: evite fazer sozinho com cargas altas.',
    ],
],

'supino_maquina' => [
    'tipo' => 'Exercício multiarticular de empurrar com trajetória guiada',
    'passos' => [
        'Ajuste o assento para que as manoplas fiquem na altura do meio do peito.',
        'Mantenha as costas apoiadas e as escápulas aproximadas.',
        'Empurre até estender os braços sem travar os cotovelos.',
        'Volte controlando até sentir o alongamento do peitoral.',
    ],
    'erros' => [
        'Assento muito alto ou muito baixo.',
        'Desencostar as costas do apoio para ganhar força.',
        'Deixar a carga voltar sem controle.',
    ],
],

'press_pe' => [
    'tipo' => 'Exercício multiarticular de empurrar em pé',
    'passos' => [
        'Fique de costas para a polia (ou para o ponto de fixação do elástico), com um pé à frente para dar base.',
        'Segure as pegadas na altura do peito, cotovelos levemente abaixo da linha dos ombros.',
        'Empurre as mãos à frente até estender os braços, mantendo o abdômen contraído.',
        'Volte controlando até as mãos chegarem ao lado do peito.',
    ],
    'erros' => [
        'Inclinar o tronco à frente para compensar a carga.',
        'Arquear a lombar.',
        'Deixar o cabo ou elástico puxar os braços para trás sem controle.',
    ],
],

'crucifixo' => [
    'tipo' => 'Exercício de isolamento de adução horizontal do ombro',
    'passos' => [
        'Deite no banco com a carga acima do peito e os cotovelos levemente flexionados.',
        'Abra os braços em arco, mantendo o mesmo ângulo dos cotovelos, até sentir o alongamento do peitoral.',
        'Retorne pelo mesmo arco, como se abraçasse um tronco, contraindo o peitoral.',
    ],
    'erros' => [
        'Flexionar e estender os cotovelos, transformando o movimento em supino.',
        'Descer além da amplitude confortável para o ombro.',
        'Usar carga alta e perder o controle na descida.',
    ],
],

'crossover' => [
    'tipo' => 'Exercício de isolamento de adução horizontal do ombro no cabo',
    'passos' => [
        'Ajuste as polias na altura indicada pela variação, segure as manoplas e dê um passo à frente.',
        'Incline levemente o tronco e mantenha os cotovelos semiflexionados.',
        'Una as mãos à frente do corpo descrevendo um arco e contraia o peitoral.',
        'Volte controlando até sentir o alongamento.',
    ],
    'erros' => [
        'Girar ou balançar o tronco para ajudar no movimento.',
        'Estender demais os cotovelos, sobrecarregando a articulação.',
        'Soltar a carga de uma vez na volta.',
    ],
],

'peck_deck' => [
    'tipo' => 'Exercício de isolamento de adução horizontal do ombro na máquina',
    'passos' => [
        'Ajuste o banco para que os braços fiquem na altura do peito.',
        'Com as costas apoiadas, una as manoplas à frente do corpo.',
        'Faça uma pausa curta e volte controlando.',
    ],
    'erros' => [
        'Assento mal ajustado, com os braços acima da linha dos ombros.',
        'Afastar as costas do encosto.',
        'Levar os braços para trás além do confortável.',
    ],
],

'flexao' => [
    'tipo' => 'Exercício multiarticular de empurrar com o peso do corpo',
    'passos' => [
        'Apoie as mãos no chão na largura indicada pela variação e estenda as pernas, formando uma linha reta da cabeça aos calcanhares.',
        'Contraia o abdômen e os glúteos.',
        'Desça o peito em direção ao chão com os cotovelos a cerca de 45° do tronco.',
        'Empurre o chão até estender os braços.',
    ],
    'erros' => [
        'Deixar o quadril cair ou subir demais.',
        'Projetar a cabeça à frente.',
        'Fazer só metade do movimento.',
    ],
],

'mergulho' => [
    'tipo' => 'Exercício multiarticular de empurrar na vertical',
    'passos' => [
        'Segure nas barras paralelas com os braços estendidos e os ombros afastados das orelhas.',
        'Desça flexionando os cotovelos até os ombros ficarem um pouco abaixo da linha dos cotovelos, ou até onde for confortável.',
        'Suba estendendo os braços. Inclinar o tronco à frente enfatiza o peitoral; tronco vertical enfatiza o tríceps.',
    ],
    'erros' => [
        'Descer demais, forçando a frente do ombro.',
        'Encolher os ombros.',
        'Balançar as pernas para ganhar impulso.',
    ],
],

'mergulho_banco' => [
    'tipo' => 'Exercício de empurrar com o peso do corpo',
    'passos' => [
        'Apoie as mãos na borda do banco, logo atrás do quadril, com as pernas estendidas ou flexionadas.',
        'Desça flexionando os cotovelos para trás até cerca de 90°.',
        'Suba estendendo os braços.',
    ],
    'erros' => [
        'Afastar o quadril do banco.',
        'Descer além de 90° com desconforto no ombro.',
        'Abrir os cotovelos para os lados.',
    ],
],

'pullover' => [
    'tipo' => 'Exercício de extensão do ombro',
    'passos' => [
        'Deite no banco segurando a carga acima do peito, com os cotovelos levemente flexionados.',
        'Leve a carga para trás da cabeça em arco, até sentir o alongamento.',
        'Retorne à posição inicial pelo mesmo arco.',
    ],
    'erros' => [
        'Flexionar demais os cotovelos durante o movimento.',
        'Arquear excessivamente a lombar.',
        'Usar carga excessiva para a amplitude.',
    ],
],

'puxada' => [
    'tipo' => 'Exercício multiarticular de puxar na vertical',
    'passos' => [
        'Ajuste o apoio das coxas e segure a pegada indicada pela variação.',
        'Incline levemente o tronco para trás e mantenha o peito elevado.',
        'Puxe levando os cotovelos para baixo, em direção ao tronco, até a pegada chegar perto da parte superior do peito.',
        'Volte controlando até estender os braços.',
    ],
    'erros' => [
        'Puxar atrás da nuca.',
        'Balançar o tronco para trás a cada repetição.',
        'Encolher os ombros durante a puxada.',
        'Não estender os braços na volta.',
    ],
],

'barra_fixa' => [
    'tipo' => 'Exercício multiarticular de puxar com o peso do corpo',
    'passos' => [
        'Segure a barra com a pegada da variação e fique pendurado com os braços estendidos.',
        'Ative as escápulas, aproximando-as e levando-as para baixo.',
        'Puxe o corpo até o queixo passar a barra, levando os cotovelos em direção ao tronco.',
        'Desça controlando até estender os braços.',
    ],
    'erros' => [
        'Balançar o corpo para ganhar impulso.',
        'Fazer meia amplitude, sem estender os braços.',
        'Projetar o queixo para alcançar a barra.',
    ],
],

'remada' => [
    'tipo' => 'Exercício multiarticular de puxar na horizontal',
    'passos' => [
        'Sente-se (ou apoie o peito no banco) com a coluna neutra e o peito elevado.',
        'Puxe a pegada em direção ao abdômen, levando os cotovelos para trás e aproximando as escápulas.',
        'Faça uma pausa curta e volte controlando até alongar as costas.',
    ],
    'erros' => [
        'Balançar o tronco para frente e para trás.',
        'Encolher os ombros.',
        'Puxar só com os braços, sem mover as escápulas.',
    ],
],

'remada_curvada' => [
    'tipo' => 'Exercício multiarticular de puxar com o tronco inclinado',
    'passos' => [
        'Com a carga nas mãos, flexione levemente os joelhos e incline o tronco à frente pelo quadril, mantendo a coluna neutra.',
        'Puxe a carga em direção à parte baixa do abdômen.',
        'Aproxime as escápulas no fim do movimento e desça controlando.',
    ],
    'erros' => [
        'Arredondar a lombar.',
        'Levantar o tronco a cada repetição.',
        'Usar impulso das pernas.',
    ],
],

'remada_unilateral' => [
    'tipo' => 'Exercício unilateral de puxar na horizontal',
    'passos' => [
        'Apoie a mão e o joelho do mesmo lado no banco (ou incline-se com apoio), mantendo a coluna neutra.',
        'Puxe a carga em direção ao quadril, com o cotovelo próximo ao corpo.',
        'Desça controlando até alongar as costas.',
    ],
    'erros' => [
        'Girar o tronco para subir a carga.',
        'Puxar em direção ao ombro em vez do quadril.',
        'Arredondar a coluna.',
    ],
],

'pulldown_reto' => [
    'tipo' => 'Exercício de extensão do ombro com braços estendidos',
    'passos' => [
        'De frente para a polia alta (ou na máquina), segure a pegada com os braços estendidos e o tronco levemente inclinado.',
        'Leve as mãos até a altura das coxas, mantendo os cotovelos quase estendidos.',
        'Volte controlando até sentir o alongamento da dorsal.',
    ],
    'erros' => [
        'Flexionar os cotovelos, transformando em tríceps na polia.',
        'Usar impulso do tronco.',
        'Arredondar a coluna.',
    ],
],

'terra' => [
    'tipo' => 'Exercício multiarticular de levantar a carga do chão',
    'passos' => [
        'Posicione os pés na largura indicada pela variação, com a carga próxima às canelas.',
        'Segure a carga, abaixe o quadril, eleve o peito e mantenha a coluna neutra.',
        'Empurre o chão e estenda quadril e joelhos ao mesmo tempo, com a carga junto ao corpo.',
        'No topo, fique ereto sem jogar a lombar para trás; desça controlando pelo mesmo caminho.',
    ],
    'erros' => [
        'Arredondar a lombar.',
        'Afastar a carga do corpo.',
        'Subir o quadril antes do tronco.',
        'Hiperestender a lombar no topo.',
    ],
],

'good_morning' => [
    'tipo' => 'Exercício de dobradiça de quadril com a carga nas costas',
    'passos' => [
        'Apoie a barra sobre o trapézio, pés na largura do quadril e joelhos levemente flexionados.',
        'Incline o tronco à frente levando o quadril para trás, com a coluna neutra.',
        'Desça até sentir o alongamento dos posteriores de coxa.',
        'Volte estendendo o quadril.',
    ],
    'erros' => [
        'Arredondar a coluna.',
        'Flexionar demais os joelhos.',
        'Usar carga alta antes de dominar a técnica.',
    ],
],

'hiperextensao' => [
    'tipo' => 'Exercício de extensão do quadril e do tronco',
    'passos' => [
        'Ajuste o apoio na altura do quadril e cruze os braços no peito (ou segure a carga).',
        'Desça o tronco flexionando o quadril, com a coluna neutra.',
        'Suba até alinhar tronco e pernas, contraindo glúteos e lombar.',
    ],
    'erros' => [
        'Jogar a lombar para trás no topo.',
        'Fazer o movimento rápido demais.',
        'Apoio na altura errada, travando o quadril.',
    ],
],

'superman' => [
    'tipo' => 'Exercício de extensão do tronco no solo',
    'passos' => [
        'Deite de bruços com os braços estendidos à frente.',
        'Eleve braços, peito e pernas do chão ao mesmo tempo, olhando para o chão.',
        'Mantenha um a dois segundos e desça devagar.',
    ],
    'erros' => [
        'Jogar a cabeça para trás.',
        'Subir com impulso.',
        'Forçar a amplitude com dor na lombar.',
    ],
],

'desenvolvimento' => [
    'tipo' => 'Exercício multiarticular de empurrar acima da cabeça',
    'passos' => [
        'Posicione a carga na altura dos ombros, com os punhos alinhados sobre os cotovelos.',
        'Contraia abdômen e glúteos para estabilizar o tronco.',
        'Empurre a carga acima da cabeça até estender os braços.',
        'Desça controlando até a altura dos ombros.',
    ],
    'erros' => [
        'Arquear demais a lombar.',
        'Empurrar a carga à frente da cabeça.',
        'Fazer meia amplitude.',
    ],
],

'elevacao_lateral' => [
    'tipo' => 'Exercício de isolamento de abdução do ombro',
    'passos' => [
        'Em pé (ou na posição do equipamento), carga ao lado do corpo e cotovelos levemente flexionados.',
        'Eleve os braços lateralmente até a altura dos ombros, conduzindo pelos cotovelos.',
        'Desça controlando.',
    ],
    'erros' => [
        'Usar impulso do tronco.',
        'Encolher os ombros durante a subida.',
        'Subir muito acima dos ombros com carga alta.',
    ],
],

'elevacao_frontal' => [
    'tipo' => 'Exercício de isolamento de flexão do ombro',
    'passos' => [
        'Em pé, com a carga à frente das coxas.',
        'Eleve à frente até a altura dos ombros, com os cotovelos levemente flexionados.',
        'Desça controlando.',
    ],
    'erros' => [
        'Balançar o tronco.',
        'Subir além da linha dos ombros sem necessidade.',
        'Arquear a lombar.',
    ],
],

'crucifixo_inverso' => [
    'tipo' => 'Exercício de isolamento de abdução horizontal do ombro',
    'passos' => [
        'Incline o tronco à frente com a coluna neutra (ou sente-se na máquina com o peito apoiado).',
        'Com os cotovelos levemente flexionados, abra os braços lateralmente até a linha do tronco.',
        'Volte controlando.',
    ],
    'erros' => [
        'Usar impulso do tronco.',
        'Encolher os ombros.',
        'Usar carga alta e perder a amplitude.',
    ],
],

'face_pull' => [
    'tipo' => 'Exercício de puxar com rotação externa do ombro',
    'passos' => [
        'Ajuste a polia (ou o elástico) na altura do rosto e segure a corda com as palmas para baixo.',
        'Puxe em direção ao rosto, separando as mãos e levando os cotovelos para fora e para trás.',
        'Termine com as mãos ao lado das orelhas e volte controlando.',
    ],
    'erros' => [
        'Puxar em direção ao peito.',
        'Inclinar o tronco para trás.',
        'Deixar os cotovelos abaixo da linha das mãos.',
    ],
],

'remada_alta' => [
    'tipo' => 'Exercício multiarticular de puxar na vertical, junto ao corpo',
    'passos' => [
        'Em pé, segure a carga com pegada um pouco mais aberta que os ombros.',
        'Puxe a carga junto ao corpo até a altura do peito, com os cotovelos acima dos punhos.',
        'Desça controlando.',
    ],
    'erros' => [
        'Pegada muito fechada.',
        'Subir acima da linha dos ombros se houver desconforto.',
        'Usar impulso do corpo.',
    ],
],

'rotacao_ombro' => [
    'tipo' => 'Exercício de fortalecimento do manguito rotador',
    'passos' => [
        'Mantenha o cotovelo junto ao tronco, flexionado a 90° (uma toalha entre o braço e o corpo ajuda).',
        'Gire o antebraço para fora (rotação externa) ou para dentro (rotação interna), sem afastar o cotovelo do corpo.',
        'Volte devagar.',
    ],
    'erros' => [
        'Afastar o cotovelo do corpo.',
        'Usar carga alta.',
        'Girar o tronco em vez do ombro.',
    ],
],

'encolhimento' => [
    'tipo' => 'Exercício de isolamento de elevação das escápulas',
    'passos' => [
        'Em pé, com a carga ao lado ou à frente do corpo e os braços estendidos.',
        'Eleve os ombros em direção às orelhas.',
        'Faça uma pausa curta e desça controlando.',
    ],
    'erros' => [
        'Girar os ombros.',
        'Flexionar os cotovelos.',
        'Fazer amplitude curta com carga excessiva.',
    ],
],

'rosca' => [
    'tipo' => 'Exercício de isolamento de flexão do cotovelo',
    'passos' => [
        'Em pé (ou sentado), cotovelos junto ao tronco e braços estendidos segurando a carga.',
        'Flexione os cotovelos levando a carga em direção aos ombros, sem mover os cotovelos para frente.',
        'Desça controlando até estender os braços.',
    ],
    'erros' => [
        'Balançar o tronco.',
        'Levar os cotovelos à frente.',
        'Descer sem controle ou fazer meia amplitude.',
    ],
],

'rosca_scott' => [
    'tipo' => 'Exercício de isolamento de flexão do cotovelo com apoio',
    'passos' => [
        'Apoie a parte de trás dos braços no banco Scott, com as axilas encostadas no apoio.',
        'Flexione os cotovelos até a carga se aproximar dos ombros.',
        'Desça controlando até quase estender os braços.',
    ],
    'erros' => [
        'Estender bruscamente no fim da descida.',
        'Tirar os braços do apoio.',
        'Fazer amplitude curta.',
    ],
],

'rosca_concentrada' => [
    'tipo' => 'Exercício de isolamento de flexão do cotovelo',
    'passos' => [
        'Sentado, apoie a parte de trás do braço na parte interna da coxa.',
        'Flexione o cotovelo levando o halter em direção ao ombro.',
        'Desça controlando.',
    ],
    'erros' => [
        'Mover o ombro.',
        'Usar o tronco para subir a carga.',
    ],
],

'triceps_polia' => [
    'tipo' => 'Exercício de isolamento de extensão do cotovelo',
    'passos' => [
        'De frente para a polia alta (ou na máquina), mantenha os cotovelos junto ao tronco.',
        'Estenda os cotovelos até os braços ficarem retos, sem mover os ombros.',
        'Volte controlando até cerca de 90°.',
    ],
    'erros' => [
        'Abrir os cotovelos.',
        'Inclinar o tronco para usar o peso do corpo.',
        'Subir as mãos além da altura do peito.',
    ],
],

'triceps_testa' => [
    'tipo' => 'Exercício de isolamento de extensão do cotovelo deitado',
    'passos' => [
        'Deitado no banco, braços estendidos acima do peito.',
        'Flexione apenas os cotovelos, levando a carga em direção à testa (ou logo atrás da cabeça).',
        'Estenda os cotovelos de volta.',
    ],
    'erros' => [
        'Abrir os cotovelos.',
        'Mover os ombros.',
        'Descer rápido demais.',
    ],
],

'triceps_frances' => [
    'tipo' => 'Exercício de isolamento de extensão do cotovelo acima da cabeça',
    'passos' => [
        'Sentado ou em pé, carga acima da cabeça com os braços estendidos.',
        'Flexione os cotovelos levando a carga atrás da cabeça, com os cotovelos apontando para cima.',
        'Estenda os cotovelos de volta.',
    ],
    'erros' => [
        'Abrir os cotovelos.',
        'Arquear a lombar.',
        'Fazer amplitude curta.',
    ],
],

'triceps_coice' => [
    'tipo' => 'Exercício de isolamento de extensão do cotovelo com o tronco inclinado',
    'passos' => [
        'Incline o tronco à frente com a coluna neutra, braço junto ao corpo e cotovelo a 90°.',
        'Estenda o cotovelo levando a carga para trás.',
        'Volte controlando.',
    ],
    'erros' => [
        'Mover o ombro.',
        'Balançar a carga.',
        'Deixar o cotovelo cair abaixo da linha do tronco.',
    ],
],

'rosca_punho' => [
    'tipo' => 'Exercício de isolamento do punho',
    'passos' => [
        'Sentado, apoie os antebraços nas coxas ou no banco, com os punhos para fora do apoio.',
        'Flexione os punhos (palmas para cima) ou estenda-os (palmas para baixo, na versão inversa), mantendo os antebraços apoiados.',
        'Volte devagar.',
    ],
    'erros' => [
        'Mover os antebraços.',
        'Usar carga alta e perder a amplitude.',
    ],
],

'isometria_pegada' => [
    'tipo' => 'Exercício isométrico de força de pegada',
    'passos' => [
        'Assuma a posição da variação com a pegada firme.',
        'Mantenha os ombros ativos e o tronco estável pelo tempo prescrito.',
        'Respire normalmente e solte com controle.',
    ],
    'erros' => [
        'Relaxar totalmente os ombros.',
        'Prender a respiração.',
    ],
],

'carregamento' => [
    'tipo' => 'Exercício de carregamento',
    'passos' => [
        'Levante a carga do chão com a técnica do levantamento terra.',
        'Caminhe com passos curtos e controlados, tronco ereto e ombros para trás.',
        'Mantenha o abdômen contraído e não incline o tronco para os lados.',
    ],
    'erros' => [
        'Inclinar o tronco.',
        'Dar passos longos ou rápidos demais.',
        'Perder a postura com o cansaço.',
    ],
],

'agachamento' => [
    'tipo' => 'Exercício multiarticular de agachar',
    'passos' => [
        'Pés entre a largura do quadril e a dos ombros, com as pontas levemente para fora.',
        'Inspire, contraia o abdômen e desça levando o quadril para trás e para baixo, com os joelhos acompanhando a direção dos pés.',
        'Desça até onde conseguir manter a coluna neutra e os calcanhares no chão.',
        'Suba empurrando o chão com o pé inteiro.',
    ],
    'erros' => [
        'Joelhos caindo para dentro.',
        'Tirar os calcanhares do chão.',
        'Arredondar a lombar no fundo.',
        'Deixar o tronco desabar à frente.',
    ],
],

'hack' => [
    'tipo' => 'Exercício multiarticular de agachar com trajetória guiada',
    'passos' => [
        'Encoste as costas no apoio e posicione os pés na plataforma na largura do quadril.',
        'Destrave e desça flexionando joelhos e quadril de forma controlada.',
        'Suba empurrando a plataforma, sem travar os joelhos no topo.',
    ],
    'erros' => [
        'Tirar o quadril do apoio.',
        'Joelhos caindo para dentro.',
        'Fazer amplitude curta.',
    ],
],

'leg_press' => [
    'tipo' => 'Exercício multiarticular de empurrar com as pernas',
    'passos' => [
        'Sente-se com costas e quadril apoiados e os pés na plataforma na largura do quadril.',
        'Destrave e desça a plataforma flexionando os joelhos até onde o quadril não descolar do banco.',
        'Empurre até quase estender os joelhos, sem travar.',
    ],
    'erros' => [
        'Descolar o quadril do banco no fundo.',
        'Travar os joelhos no topo.',
        'Deixar os joelhos caírem para dentro.',
    ],
],

'extensora' => [
    'tipo' => 'Exercício de isolamento de extensão do joelho',
    'passos' => [
        'Ajuste o encosto para o joelho ficar alinhado ao eixo da máquina e o rolo logo acima do tornozelo.',
        'Estenda os joelhos até as pernas ficarem retas.',
        'Volte controlando.',
    ],
    'erros' => [
        'Tirar o quadril do banco.',
        'Usar impulso.',
        'Deixar a carga cair na descida.',
    ],
],

'flexora' => [
    'tipo' => 'Exercício de isolamento de flexão do joelho',
    'passos' => [
        'Ajuste o equipamento para o joelho ficar alinhado ao eixo e o apoio logo acima dos calcanhares.',
        'Flexione os joelhos trazendo os calcanhares em direção aos glúteos.',
        'Volte controlando até quase estender.',
    ],
    'erros' => [
        'Tirar o quadril do apoio.',
        'Fazer o movimento rápido.',
        'Fazer amplitude curta.',
    ],
],

'nordica' => [
    'tipo' => 'Exercício excêntrico de flexão do joelho com o peso do corpo',
    'passos' => [
        'Ajoelhe-se com os tornozelos presos (por um parceiro ou apoio).',
        'Com o quadril estendido e o tronco alinhado, incline o corpo à frente o mais devagar possível.',
        'Amorteça com as mãos e use-as para ajudar na volta, se necessário.',
    ],
    'erros' => [
        'Flexionar o quadril durante a descida.',
        'Cair rápido, sem frear.',
        'Aumentar o volume rápido demais (causa muita dor tardia).',
    ],
],

'stiff' => [
    'tipo' => 'Exercício de dobradiça de quadril',
    'passos' => [
        'Em pé, com a carga à frente das coxas e os joelhos levemente flexionados.',
        'Leve o quadril para trás deslizando a carga junto às pernas, com a coluna neutra.',
        'Desça até sentir o alongamento dos posteriores de coxa.',
        'Volte estendendo o quadril e contraindo os glúteos.',
    ],
    'erros' => [
        'Arredondar a lombar.',
        'Afastar a carga das pernas.',
        'Flexionar demais os joelhos, transformando em agachamento.',
    ],
],

'afundo' => [
    'tipo' => 'Exercício multiarticular unilateral de membros inferiores',
    'passos' => [
        'Posicione um pé à frente e outro atrás (ou dê o passo da variação), com o tronco ereto.',
        'Desça flexionando os dois joelhos até o joelho de trás se aproximar do chão.',
        'Empurre com o pé da frente para subir.',
    ],
    'erros' => [
        'Deixar o joelho da frente cair para dentro.',
        'Usar base estreita demais e perder o equilíbrio.',
        'Inclinar demais o tronco.',
    ],
],

'step_up' => [
    'tipo' => 'Exercício unilateral de subir',
    'passos' => [
        'Coloque o pé inteiro sobre o banco ou caixa.',
        'Suba empurrando com a perna de cima até estender o quadril, sem impulso da perna de baixo.',
        'Desça controlando.',
    ],
    'erros' => [
        'Impulsionar com a perna de baixo.',
        'Deixar o joelho cair para dentro.',
        'Usar caixa alta demais para a mobilidade.',
    ],
],

'elevacao_pelvica' => [
    'tipo' => 'Exercício de extensão do quadril',
    'passos' => [
        'Apoie a parte superior das costas no banco (ou deite no chão, na ponte) com os pés firmes na largura do quadril.',
        'Posicione a carga sobre o quadril, quando houver.',
        'Eleve o quadril até alinhar tronco e coxas, contraindo os glúteos e mantendo o queixo levemente recolhido.',
        'Desça controlando.',
    ],
    'erros' => [
        'Jogar a lombar para trás no topo.',
        'Pés muito longe ou muito perto do quadril.',
        'Empurrar com a ponta dos pés.',
    ],
],

'coice_gluteo' => [
    'tipo' => 'Exercício de isolamento de extensão do quadril',
    'passos' => [
        'Em quatro apoios, ou de frente para a polia ou máquina, com o tronco estável.',
        'Estenda o quadril levando a perna para trás e contraia o glúteo.',
        'Volte controlando, sem arquear a lombar.',
    ],
    'erros' => [
        'Arquear a lombar.',
        'Girar o quadril.',
        'Usar impulso.',
    ],
],

'abducao' => [
    'tipo' => 'Exercício de isolamento de abdução do quadril',
    'passos' => [
        'Posicione-se no equipamento (ou deitado de lado) com o tronco estável.',
        'Afaste a perna (ou as pernas) lateralmente, contraindo a lateral do glúteo.',
        'Volte controlando.',
    ],
    'erros' => [
        'Usar impulso.',
        'Inclinar o tronco para compensar.',
        'Deixar a carga voltar sem controle.',
    ],
],

'aducao' => [
    'tipo' => 'Exercício de isolamento de adução do quadril',
    'passos' => [
        'Posicione-se no equipamento (ou deitado de lado) com o tronco estável.',
        'Aproxime a perna (ou as pernas) da linha do corpo, contraindo a parte interna da coxa.',
        'Volte controlando.',
    ],
    'erros' => [
        'Usar impulso.',
        'Abrir além do confortável na volta.',
        'Mover o tronco.',
    ],
],

'panturrilha' => [
    'tipo' => 'Exercício de isolamento de flexão plantar',
    'passos' => [
        'Apoie a ponta dos pés na plataforma ou degrau, com os calcanhares livres.',
        'Desça os calcanhares até alongar a panturrilha.',
        'Suba o máximo possível na ponta dos pés e faça uma pausa curta.',
    ],
    'erros' => [
        'Quicar no fundo do movimento.',
        'Fazer amplitude curta.',
        'Flexionar os joelhos para ajudar (nas versões em pé).',
    ],
],

'prancha' => [
    'tipo' => 'Exercício isométrico de estabilização do tronco',
    'passos' => [
        'Apoie os antebraços (ou as mãos) no chão, com os cotovelos abaixo dos ombros.',
        'Estenda as pernas e alinhe cabeça, tronco e quadril.',
        'Contraia abdômen e glúteos e mantenha a posição pelo tempo prescrito, respirando normalmente.',
    ],
    'erros' => [
        'Deixar o quadril cair.',
        'Elevar demais o quadril.',
        'Prender a respiração.',
    ],
],

'abdominal' => [
    'tipo' => 'Exercício de flexão do tronco',
    'passos' => [
        'Deitado (ou na posição do equipamento), com os pés apoiados e as mãos ao lado da cabeça ou cruzadas no peito.',
        'Flexione o tronco aproximando as costelas da pelve.',
        'Volte devagar.',
    ],
    'erros' => [
        'Puxar o pescoço com as mãos.',
        'Usar impulso.',
        'Subir usando a lombar em vez do abdômen.',
    ],
],

'elevacao_pernas' => [
    'tipo' => 'Exercício de flexão do quadril com controle da pelve',
    'passos' => [
        'Deitado ou pendurado, com a lombar estável.',
        'Eleve as pernas (ou os joelhos) levando a pelve levemente em direção às costelas.',
        'Desça devagar, sem arquear a lombar.',
    ],
    'erros' => [
        'Balançar o corpo.',
        'Arquear a lombar.',
        'Descer rápido.',
    ],
],

'roda_abdominal' => [
    'tipo' => 'Exercício de estabilização do tronco em extensão',
    'passos' => [
        'Ajoelhado, segure o apoio (roda ou bola) abaixo dos ombros.',
        'Role à frente estendendo o corpo e mantendo o abdômen contraído, até onde não arquear a lombar.',
        'Volte puxando com o abdômen.',
    ],
    'erros' => [
        'Arquear a lombar.',
        'Ir além da sua capacidade de controle.',
        'Voltar levando o quadril para trás primeiro.',
    ],
],

'rotacao_core' => [
    'tipo' => 'Exercício de rotação do tronco',
    'passos' => [
        'Assuma uma posição estável (sentado, ou em pé de lado para a polia).',
        'Gire o tronco de forma controlada, levando a carga de um lado para o outro ou na diagonal da variação.',
        'Mantenha o quadril estável e o abdômen contraído.',
    ],
    'erros' => [
        'Girar só os braços.',
        'Fazer rápido demais, perdendo o controle.',
        'Arredondar a coluna.',
    ],
],

'antirotacao' => [
    'tipo' => 'Exercício isométrico anti-rotação do tronco',
    'passos' => [
        'De lado para a polia (ou elástico), segure a pegada junto ao peito.',
        'Empurre as mãos à frente e mantenha alguns segundos sem deixar o tronco girar.',
        'Volte as mãos ao peito.',
    ],
    'erros' => [
        'Deixar o tronco girar.',
        'Usar base estreita.',
        'Prender a respiração.',
    ],
],

'estabilizacao' => [
    'tipo' => 'Exercício de estabilização do tronco',
    'passos' => [
        'Na posição inicial, mantenha a lombar estável e o abdômen contraído.',
        'Movimente braços e pernas conforme a variação, de forma lenta, sem perder a posição do tronco.',
        'Respire continuamente.',
    ],
    'erros' => [
        'Arquear a lombar.',
        'Ter pressa.',
        'Perder o alinhamento do corpo.',
    ],
],

'escalador' => [
    'tipo' => 'Exercício dinâmico de tronco e condicionamento',
    'passos' => [
        'Em prancha com os braços estendidos, mãos abaixo dos ombros.',
        'Leve um joelho em direção ao peito e volte, alternando as pernas no ritmo prescrito.',
        'Mantenha o quadril baixo e o abdômen contraído.',
    ],
    'erros' => [
        'Subir o quadril.',
        'Deixar os ombros passarem muito à frente das mãos.',
    ],
],

'swing' => [
    'tipo' => 'Exercício explosivo de extensão do quadril',
    'passos' => [
        'Pés um pouco além da largura do quadril, kettlebell no chão à frente.',
        'Leve o kettlebell entre as pernas levando o quadril para trás, com a coluna neutra.',
        'Estenda o quadril de forma explosiva e projete o kettlebell até a altura do peito, com os braços relaxados.',
        'Deixe o kettlebell descer e emende a próxima repetição.',
    ],
    'erros' => [
        'Agachar em vez de levar o quadril para trás.',
        'Levantar o kettlebell com os braços.',
        'Arredondar a lombar.',
        'Jogar o tronco para trás no topo.',
    ],
],

'olimpico' => [
    'tipo' => 'Exercício explosivo de levantamento (técnica complexa: aprenda com supervisão)',
    'passos' => [
        'Comece com a coluna neutra e a carga próxima ao corpo.',
        'Estenda quadril, joelhos e tornozelos de forma explosiva.',
        'Puxe o corpo para baixo da carga e receba-a na posição da variação (nos ombros ou acima da cabeça).',
        'Fique de pé e devolva a carga com controle.',
    ],
    'erros' => [
        'Puxar com os braços antes de estender o quadril.',
        'Afastar a carga do corpo.',
        'Receber a carga com a coluna arredondada.',
    ],
],

'thruster' => [
    'tipo' => 'Exercício multiarticular de agachar e empurrar',
    'passos' => [
        'Segure a carga na altura dos ombros (ou a bola junto ao peito).',
        'Agache mantendo o tronco ereto.',
        'Suba de forma explosiva e use o impulso para empurrar a carga acima da cabeça (ou arremessar a bola no alvo).',
        'Volte a carga aos ombros e repita.',
    ],
    'erros' => [
        'Deixar os cotovelos caírem no agachamento.',
        'Separar o movimento em duas partes, perdendo o impulso.',
        'Arquear a lombar no topo.',
    ],
],

'burpee' => [
    'tipo' => 'Exercício de condicionamento com o peso do corpo',
    'passos' => [
        'Em pé, agache e apoie as mãos no chão.',
        'Leve os pés para trás até a posição de prancha (com flexão, se prescrito).',
        'Volte os pés para perto das mãos e salte com os braços acima da cabeça.',
    ],
    'erros' => [
        'Deixar o quadril cair na prancha.',
        'Aterrissar com os joelhos para dentro.',
    ],
],

'tgu' => [
    'tipo' => 'Exercício de corpo inteiro com transições (técnica complexa)',
    'passos' => [
        'Deitado, segure o kettlebell com o braço estendido acima do ombro e a perna do mesmo lado flexionada.',
        'Apoie-se no antebraço e depois na mão do lado livre, elevando o tronco sem tirar os olhos do kettlebell.',
        'Eleve o quadril, passe a perna estendida por baixo do corpo até ajoelhar e fique de pé.',
        'Faça o caminho inverso até deitar, sempre com o braço da carga estendido.',
    ],
    'erros' => [
        'Flexionar o braço que segura a carga.',
        'Tirar os olhos do kettlebell.',
        'Apressar as transições.',
    ],
],

'slam' => [
    'tipo' => 'Exercício explosivo de arremesso',
    'passos' => [
        'Segure a bola acima da cabeça, com os braços estendidos.',
        'Arremesse com força no chão flexionando o quadril e contraindo o abdômen.',
        'Pegue a bola agachando com a coluna neutra e repita.',
    ],
    'erros' => [
        'Arredondar a lombar ao pegar a bola.',
        'Usar bola que quica alto (use bola própria para slam).',
    ],
],

'corda_naval' => [
    'tipo' => 'Exercício de condicionamento com cordas',
    'passos' => [
        'Segure as pontas da corda, com joelhos semiflexionados e tronco firme.',
        'Faça ondas alternadas ou simultâneas movendo os braços de forma vigorosa.',
        'Mantenha o ritmo pelo tempo prescrito.',
    ],
    'erros' => [
        'Relaxar o tronco.',
        'Fazer ondas pequenas, só com os punhos.',
    ],
],

'treno' => [
    'tipo' => 'Exercício de condicionamento e potência de membros inferiores',
    'passos' => [
        'Incline o corpo à frente com os braços estendidos nas barras do trenó.',
        'Empurre com passos curtos e potentes, apoiando a frente do pé.',
        'Mantenha o tronco firme durante todo o percurso.',
    ],
    'erros' => [
        'Quadril alto demais.',
        'Passos longos e lentos.',
    ],
],

'salto' => [
    'tipo' => 'Exercício pliométrico',
    'passos' => [
        'Agache parcialmente e salte o mais alto possível estendendo quadril, joelhos e tornozelos.',
        'Aterrisse suavemente, amortecendo com joelhos e quadril e com os joelhos alinhados aos pés.',
        'Estabilize antes do próximo salto.',
    ],
    'erros' => [
        'Aterrissar com as pernas rígidas.',
        'Deixar os joelhos caírem para dentro.',
    ],
],

'isometria' => [
    'tipo' => 'Exercício isométrico de membros inferiores',
    'passos' => [
        'Encoste as costas na parede e deslize até os joelhos ficarem perto de 90°.',
        'Mantenha os pés à frente dos joelhos e o peso distribuído no pé inteiro.',
        'Sustente pelo tempo prescrito, respirando normalmente.',
    ],
    'erros' => [
        'Apoiar as mãos nas coxas.',
        'Deixar os joelhos caírem para dentro.',
    ],
],

'sissy' => [
    'tipo' => 'Exercício de ênfase no quadríceps com o peso do corpo (avançado)',
    'passos' => [
        'Segure em um apoio, eleve os calcanhares e mantenha quadril e tronco alinhados.',
        'Leve os joelhos à frente enquanto inclina o corpo para trás, flexionando apenas os joelhos.',
        'Volte estendendo os joelhos.',
    ],
    'erros' => [
        'Flexionar o quadril.',
        'Descer sem controle.',
        'Fazer com dor no joelho.',
    ],
],

'caminhada_elastico' => [
    'tipo' => 'Exercício de ativação dos abdutores do quadril',
    'passos' => [
        'Coloque o elástico acima dos joelhos ou nos tornozelos e fique semiagachado.',
        'Dê passos laterais mantendo a tensão no elástico e o tronco estável.',
        'Faça o mesmo número de passos para os dois lados.',
    ],
    'erros' => [
        'Deixar os joelhos caírem para dentro.',
        'Balançar o tronco a cada passo.',
    ],
],

'mobilidade' => [
    'tipo' => 'Exercício de mobilidade articular',
    'passos' => [
        'Faça o movimento de forma lenta e controlada, dentro de uma amplitude sem dor.',
        'Aumente a amplitude aos poucos a cada repetição.',
        'Respire de forma contínua.',
    ],
    'erros' => [
        'Forçar a amplitude com dor.',
        'Fazer com pressa.',
        'Compensar com outras articulações.',
    ],
],

'alongamento' => [
    'tipo' => 'Alongamento estático',
    'passos' => [
        'Posicione-se até sentir uma tensão leve a moderada no músculo, sem dor.',
        'Mantenha a posição por 20 a 30 segundos (ou pelo tempo prescrito), respirando normalmente.',
        'Repita do outro lado, quando houver.',
    ],
    'erros' => [
        'Balançar ou insistir com dor.',
        'Prender a respiração.',
        'Compensar com a postura.',
    ],
],

'cardio_maquina' => [
    'tipo' => 'Exercício cardiovascular em equipamento',
    'passos' => [
        'Ajuste o equipamento (velocidade, inclinação, carga ou banco) conforme a prescrição.',
        'Comece com 3 a 5 minutos em ritmo leve para aquecer.',
        'Mantenha a intensidade prescrita (tempo, distância, frequência cardíaca ou percepção de esforço) com boa postura.',
        'Termine reduzindo o ritmo aos poucos.',
    ],
    'erros' => [
        'Apoiar o peso do corpo nos apoios das mãos.',
        'Postura curvada.',
        'Começar em alta intensidade sem aquecer.',
    ],
],

'cardio_livre' => [
    'tipo' => 'Exercício cardiovascular com o peso do corpo',
    'passos' => [
        'Comece em ritmo leve e aumente aos poucos.',
        'Mantenha o tronco firme e aterrissagens suaves, na frente dos pés.',
        'Siga o tempo ou as repetições prescritas.',
    ],
    'erros' => [
        'Aterrissar com as pernas rígidas.',
        'Perder a postura com o cansaço.',
    ],
],

'pular_corda' => [
    'tipo' => 'Exercício cardiovascular e de coordenação',
    'passos' => [
        'Ajuste o tamanho da corda: pisando no meio, as pontas devem chegar perto das axilas.',
        'Gire a corda com os punhos, cotovelos próximos ao corpo.',
        'Salte baixo, na ponta dos pés, com joelhos levemente flexionados.',
    ],
    'erros' => [
        'Girar a corda com os braços inteiros.',
        'Saltar alto demais.',
        'Aterrissar com os calcanhares.',
    ],
],

];
