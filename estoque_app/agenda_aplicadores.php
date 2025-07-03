<?php
// agenda_aplicadores.php
require_once 'config.php';

// Verifica a conexão com o banco de dados
if (!$link) {
    die("Erro: Conexão com o banco de dados não estabelecida em agenda_aplicadores.php. Verifique config.php.");
}

// Lista fixa de aplicadores
$aplicadores_disponiveis = ['Toinho', 'Marcelino', 'Rafael', 'Samuel'];
$status_agendamento = ['Agendado', 'Concluído', 'Cancelado', 'Reagendado'];

// Opções de serviços agregados
$servicos_agregados_opcoes = [
    'vitrificacao' => 'Vitrificação',
    'vitrificacao_bancos' => 'Vitrificação de Bancos',
    'impermeabilizacao' => 'Impermeabilização',
    'ppf' => 'PPF'
];

// --- Lógica para filtros ---
$filter_aplicador = $_GET['filter_aplicador'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_data_inicio = $_GET['filter_data_inicio'] ?? '';
$filter_data_fim = $_GET['filter_data_fim'] ?? '';
$filter_concessionaria_id = $_GET['filter_concessionaria_id'] ?? '';


$filter_conditions = [];
if (!empty($filter_aplicador)) {
    $filter_conditions[] = "aplicador = '" . mysqli_real_escape_string($link, $filter_aplicador) . "'";
}
if (!empty($filter_status)) {
    $filter_conditions[] = "status = '" . mysqli_real_escape_string($link, $filter_status) . "'";
}
if (!empty($filter_data_inicio)) {
    $filter_conditions[] = "data_agendamento >= '" . mysqli_real_escape_string($link, $filter_data_inicio) . "'";
}
if (!empty($filter_data_fim)) {
    $filter_conditions[] = "data_agendamento <= '" . mysqli_real_escape_string($link, $filter_data_fim) . "'";
}
if (!empty($filter_concessionaria_id)) {
    $filter_conditions[] = "concessionaria_id = " . intval($filter_concessionaria_id);
}

$where_clause = '';
if (!empty($filter_conditions)) {
    $where_clause = " WHERE " . implode(' AND ', $filter_conditions);
}

// --- Lógica para buscar concessionárias para os dropdowns (tanto formulário de adição quanto filtro) ---
$concessionarias_dropdown = [];
$sql_concessionarias = "SELECT id, nome FROM concessionarias ORDER BY nome ASC";
$result_concessionarias = mysqli_query($link, $sql_concessionarias);
if ($result_concessionarias) {
    while ($row = mysqli_fetch_assoc($result_concessionarias)) {
        $concessionarias_dropdown[$row['id']] = $row['nome'];
    }
    mysqli_free_result($result_concessionarias);
}

// --- Lógica para processar mudança de status (AÇÃO) ---
if (isset($_GET['action']) && $_GET['action'] == 'change_status' && isset($_GET['id']) && isset($_GET['new_status'])) {
    $agendamento_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $new_status = mysqli_real_escape_string($link, $_GET['new_status']);

    if ($agendamento_id && in_array($new_status, $status_agendamento)) {
        $sql_update_status = "UPDATE agendamentos_aplicadores SET status = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql_update_status)) {
            mysqli_stmt_bind_param($stmt, "si", $new_status, $agendamento_id);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Status do agendamento ID " . $agendamento_id . " atualizado para '" . htmlspecialchars($new_status) . "' com sucesso!";
                $type = "success";
            } else {
                $message = "Erro ao atualizar status: " . mysqli_error($link);
                $type = "error";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Erro ao preparar a atualização de status: " . mysqli_error($link);
            $type = "error";
        }
    } else {
        $message = "Dados inválidos para atualizar o status.";
        $type = "error";
    }
    // Redireciona para a mesma página com a mensagem (removendo action/id/new_status da URL)
    $redirect_params = $_GET;
    unset($redirect_params['action'], $redirect_params['id'], $redirect_params['new_status']);
    $redirect_params['message'] = $message;
    $redirect_params['type'] = $type;
    header('Location: agenda_aplicadores.php?' . http_build_query($redirect_params));
    exit();
}

// Lógica para processar o formulário de adição (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_agendamento = trim($_POST['data_agendamento'] ?? '');
    $tipo_pelicula = trim($_POST['tipo_pelicula'] ?? ''); // Novo campo
    $concessionaria_id = filter_var($_POST['concessionaria_id'] ?? null, FILTER_VALIDATE_INT);
    $aplicador = trim($_POST['aplicador'] ?? '');
    $servico = trim($_POST['servico'] ?? '');
    $servicos_agregados_array = $_POST['servicos_agregados'] ?? []; // Novo campo (array)
    $observacoes = trim($_POST['observacoes'] ?? '');

    // Codifica os serviços agregados para JSON para armazenar no banco de dados
    $servicos_agregados_json = json_encode($servicos_agregados_array);
    if ($servicos_agregados_json === false) { // Verifica se houve erro na codificação JSON
        $servicos_agregados_json = '[]'; // Garante que seja um JSON vazio em caso de erro
    }

    if (empty($data_agendamento) || $concessionaria_id === false || $concessionaria_id <= 0 || empty($aplicador) || empty($servico) || !in_array($aplicador, $aplicadores_disponiveis)) {
        $message = "Erro: Por favor, preencha todos os campos obrigatórios corretamente e selecione um aplicador válido.";
        $type = "error";
    } else {
        // Ajuste o INSERT SQL para incluir os novos campos
        $sql_insert = "INSERT INTO agendamentos_aplicadores (data_agendamento, tipo_pelicula, concessionaria_id, aplicador, servico, servicos_agregados, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql_insert)) {
            // Ajuste os tipos para bind_param: sssisss -> ssissss (data, tipo, id_con, aplicador, servico, servicos_json, obs)
            mysqli_stmt_bind_param($stmt, "ssissss", $data_agendamento, $tipo_pelicula, $concessionaria_id, $aplicador, $servico, $servicos_agregados_json, $observacoes);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Agendamento registrado com sucesso!";
                $type = "success";
            } else {
                $message = "Erro ao registrar agendamento: " . mysqli_error($link);
                $type = "error";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Erro ao preparar a inserção do agendamento: " . mysqli_error($link);
            $type = "error";
        }
    }
    // Redireciona para a mesma página com a mensagem (mantendo filtros se existirem)
    $redirect_params = $_GET; // Mantém os filtros da URL atual
    unset($redirect_params['action'], $redirect_params['id'], $redirect_params['new_status']); // Limpa parâmetros de ação
    $redirect_params['message'] = $message;
    $redirect_params['type'] = $type;
    header('Location: agenda_aplicadores.php?' . http_build_query($redirect_params));
    exit();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda de Aplicadores</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            /* Nova coloração de fundo com imagem */
            background-color: #f3f4f6; /* Cor de fallback se a imagem não carregar */
            background-image: url('imagens/background_art.jpg'); /* Substitua 'background_art.jpg' pelo nome do seu arquivo */
            background-size: cover; /* Faz a imagem cobrir todo o fundo */
            background-repeat: no-repeat; /* Evita que a imagem se repita */
            background-position: center center; /* Centraliza a imagem */
            background-attachment: fixed; /* Mantém a imagem fixa durante a rolagem (opcional, mas geralmente bom para fundos) */
        }
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: rgba(255, 255, 255, 0.9); /* Fundo branco semi-transparente para o container para contrastar com o body e a arte */
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex-grow: 1;
        }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; background-color: #f9fafb; }
        .btn-primary { background-color: #4f46e5; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; transition: background-color 0.2s ease-in-out; }
        .btn-primary:hover { background-color: #4338ca; }
        .btn-secondary { background-color: #6b7280; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; transition: background-color 0.2s ease-in-out; text-decoration: none; }
        .btn-secondary:hover { background-color: #4b5563; }
        .message-box {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .message-success { background-color: #d1fae5; color: #065f46; border: 1px solid #34d399; }
        .message-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .table-header th { background-color: #e5e7eb; padding: 0.75rem; text-align: left; font-weight: 600; border-bottom: 2px solid #d1d5db; }
        .table-row td { padding: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .table-row:nth-child(even) { background-color: #f9fafb; }

        /* Estilo para destacar a agenda do dia */
        .highlight-today {
            background-color: #fffacd; /* Cor amarela clara para destaque */
            font-weight: 600;
            border-left: 5px solid #f59e0b; /* Borda lateral laranja */
        }
        .highlight-today:nth-child(even) { /* Mantém a alternância de cores para linhas pares */
            background-color: #fef08a; /* Amarelo um pouco mais escuro para linhas pares destacadas */
        }

        /* Novo estilo para agendamentos concluídos */
        .completed-appointment {
            background-color: #dcfce7; /* Verde claro pastel */
            font-weight: 600;
            border-left: 5px solid #22c55e; /* Borda lateral verde mais forte */
        }
        .completed-appointment:nth-child(even) {
            background-color: #bbf7d0; /* Verde um pouco mais escuro para linhas pares concluídas */
        }

        /* Novo estilo para agendamentos vencidos */
        .expired-appointment {
            background-color: #fce7e7; /* Vermelho claro */
            font-weight: 600;
            border-left: 5px solid #ef4444; /* Borda lateral vermelha */
        }
        .expired-appointment:nth-child(even) {
            background-color: #fecaca; /* Vermelho um pouco mais escuro para linhas pares vencidas */
        }


        .daily-panel {
            background-color: #ecfdf5; /* Verde claro */
            border: 1px solid #a7f3d0; /* Borda verde */
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        .daily-panel h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #047857; /* Verde escuro */
            margin-bottom: 0.75rem;
        }
        .daily-panel ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .daily-panel ul li {
            background-color: #d1fae5; /* Verde mais claro */
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
            color: #065f46;
        }
        .daily-panel ul li:last-child {
            margin-bottom: 0;
        }
        .daily-panel ul li .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-badge.Agendado { background-color: #bfdbfe; color: #1e40af; } /* Azul */
        .status-badge.Concluído { background-color: #a7f3d0; color: #065f46; } /* Verde */
        .status-badge.Cancelado { background-color: #fecaca; color: #991b1b; } /* Vermelho */
        .status-badge.Reagendado { background-color: #fde68a; color: #92400e; } /* Amarelo */


        .app-footer { margin-top: 2rem; padding: 1rem; background-color: #374151; color: white; text-align: center; font-size: 0.9rem; }
        .app-footer .message-box { margin-left: auto; margin-right: auto; max-width: 600px; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-3xl font-bold text-center mb-6 text-gray-800">Agenda de Aplicadores</h1>

        <div class="mb-4 text-center">
            <a href="index.php" class="btn-secondary">Voltar ao Início</a>
        </div>
        
        <?php
        // Exibe mensagens de sucesso ou erro (no topo da página)
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>

        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Agendamentos de Hoje (<?php echo date('d/m/Y'); ?>)</h2>
        <div class="daily-panel">
            <?php
            $today_date_sql = date('Y-m-d');
            $sql_today_agendamentos = "SELECT aa.id, aa.aplicador, aa.servico, aa.status, c.nome AS concessionaria_nome, aa.observacoes, aa.tipo_pelicula, aa.servicos_agregados
                                       FROM agendamentos_aplicadores aa
                                       JOIN concessionarias c ON aa.concessionaria_id = c.id
                                       WHERE aa.data_agendamento = ?
                                       ORDER BY aa.id ASC"; // Ordena por ID para consistência

            $stmt_today = mysqli_prepare($link, $sql_today_agendamentos);
            mysqli_stmt_bind_param($stmt_today, "s", $today_date_sql);
            mysqli_stmt_execute($stmt_today);
            $result_today = mysqli_stmt_get_result($stmt_today);

            if ($result_today && mysqli_num_rows($result_today) > 0) {
                echo "<ul>";
                while ($agendamento_today = mysqli_fetch_assoc($result_today)) {
                    // Decodifica os serviços agregados para exibição no painel
                    $servicos_agregados_exibicao_today = [];
                    $decoded_services_today = json_decode($agendamento_today['servicos_agregados'], true);
                    if (is_array($decoded_services_today)) {
                        foreach ($decoded_services_today as $service_key) {
                            $servicos_agregados_exibicao_today[] = $servicos_agregados_opcoes[$service_key] ?? $service_key;
                        }
                    }
                    $servicos_agregados_str_today = !empty($servicos_agregados_exibicao_today) ? " (" . implode(', ', $servicos_agregados_exibicao_today) . ")" : '';

                    echo "<li>";
                    echo "<span>";
                    echo "<strong>" . htmlspecialchars($agendamento_today['concessionaria_nome']) . "</strong> - ";
                    echo htmlspecialchars($agendamento_today['servico']);
                    if (!empty($agendamento_today['tipo_pelicula'])) {
                        echo " (Película: " . htmlspecialchars($agendamento_today['tipo_pelicula']) . ")";
                    }
                    echo htmlspecialchars($servicos_agregados_str_today); // Adiciona os serviços agregados aqui
                    echo " (Aplicador: " . htmlspecialchars($agendamento_today['aplicador']) . ")";
                    if (!empty($agendamento_today['observacoes'])) {
                        echo " - Obs: " . htmlspecialchars($agendamento_today['observacoes']); // Adiciona observações
                    }
                    echo "</span>";
                    echo "<span class='status-badge " . htmlspecialchars($agendamento_today['status']) . "'>" . htmlspecialchars($agendamento_today['status']) . "</span>";
                    echo "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='text-gray-600'>Nenhum agendamento para hoje.</p>";
            }
            mysqli_stmt_close($stmt_today);
            ?>
        </div>

        <hr class="my-8">

        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Adicionar Novo Agendamento</h2>
        <form action="agenda_aplicadores.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 p-6 bg-white rounded-lg shadow-md">
            <div>
                <label for="data_agendamento" class="block text-sm font-medium text-gray-700 mb-1">Data:</label>
                <input type="date" id="data_agendamento" name="data_agendamento" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label for="tipo_pelicula" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Película:</label>
                <input type="text" id="tipo_pelicula" name="tipo_pelicula" class="form-input" placeholder="Ex: G5, G20, Window Blue" required>
            </div>
            <div>
                <label for="concessionaria_id" class="block text-sm font-medium text-gray-700 mb-1">Concessionária:</label>
                <select id="concessionaria_id" name="concessionaria_id" class="form-select" required>
                    <option value="">Selecione uma concessionária</option>
                    <?php foreach ($concessionarias_dropdown as $id => $nome): ?>
                        <option value="<?php echo htmlspecialchars($id); ?>"><?php echo htmlspecialchars($nome); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="aplicador" class="block text-sm font-medium text-gray-700 mb-1">Aplicador:</label>
                <select id="aplicador" name="aplicador" class="form-select" required>
                    <option value="">Selecione um aplicador</option>
                    <?php foreach ($aplicadores_disponiveis as $aplicador_nome): ?>
                        <option value="<?php echo htmlspecialchars($aplicador_nome); ?>"><?php echo htmlspecialchars($aplicador_nome); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="servico" class="block text-sm font-medium text-gray-700 mb-1">Serviço Principal:</label>
                <input type="text" id="servico" name="servico" class="form-input" placeholder="Ex: Instalação de Insulfilm" required>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Serviços Agregados:</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($servicos_agregados_opcoes as $key => $label): ?>
                        <div class="flex items-center">
                            <input type="checkbox" id="servico_<?php echo $key; ?>" name="servicos_agregados[]" value="<?php echo htmlspecialchars($key); ?>" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="servico_<?php echo $key; ?>" class="ml-2 block text-sm text-gray-900"><?php echo htmlspecialchars($label); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="md:col-span-2">
                <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-1">Observações (Opcional):</label>
                <textarea id="observacoes" name="observacoes" class="form-textarea" rows="3" placeholder="Detalhes adicionais..."></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end space-x-4">
                <a href="index.php" class="btn-secondary">Voltar ao Início</a>
                <button type="submit" class="btn-primary">Agendar Serviço</button>
            </div>
        </form>

        <hr class="my-8">

        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Filtrar Agendamentos</h2>
        <form action="agenda_aplicadores.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 p-6 bg-white rounded-lg shadow-md">
            <input type="hidden" name="type" value="agenda_aplicadores">
            <div>
                <label for="filter_aplicador" class="block text-sm font-medium text-gray-700 mb-1">Aplicador:</label>
                <select id="filter_aplicador" name="filter_aplicador" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($aplicadores_disponiveis as $aplicador_nome): ?>
                        <option value="<?php echo htmlspecialchars($aplicador_nome); ?>" <?php echo ($filter_aplicador == $aplicador_nome) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($aplicador_nome); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_status" class="block text-sm font-medium text-gray-700 mb-1">Status:</label>
                <select id="filter_status" name="filter_status" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($status_agendamento as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>" <?php echo ($filter_status == $status) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_concessionaria_id" class="block text-sm font-medium text-gray-700 mb-1">Concessionária:</label>
                <select id="filter_concessionaria_id" name="filter_concessionaria_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($concessionarias_dropdown as $id => $nome): ?>
                        <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($filter_concessionaria_id == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nome); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-span-1 md:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="filter_data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data Início:</label>
                    <input type="date" id="filter_data_inicio" name="filter_data_inicio" class="form-input" value="<?php echo htmlspecialchars($filter_data_inicio); ?>">
                </div>
                <div>
                    <label for="filter_data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data Fim:</label>
                    <input type="date" id="filter_data_fim" name="filter_data_fim" class="form-input" value="<?php echo htmlspecialchars($filter_data_fim); ?>">
                </div>
            </div>
            <div class="md:col-span-3 flex justify-end">
                <button type="submit" class="btn-primary w-full md:w-auto">Aplicar Filtros</button>
            </div>
        </form>

        <h2 class="text-2xl font-semibold mb-4 text-gray-800">Todos os Agendamentos</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="table-header">
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Tipo Película</th>
                        <th>Concessionária</th>
                        <th>Aplicador</th>
                        <th>Serviço Principal</th>
                        <th>Serviços Agregados</th>
                        <th>Status</th>
                        <th>Observações</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    // Obtém a data atual no formato Y-m-d para comparação
                    $today_date = date('Y-m-d');

                    $sql_agendamentos = "SELECT aa.id, aa.data_agendamento, aa.tipo_pelicula, aa.aplicador, aa.servico, aa.servicos_agregados, aa.status, aa.observacoes, c.nome AS concessionaria_nome
                                         FROM agendamentos_aplicadores aa
                                         JOIN concessionarias c ON aa.concessionaria_id = c.id
                                         " . $where_clause . "
                                         ORDER BY aa.data_agendamento ASC, aa.id ASC"; // Ordena por data e ID para consistência

                    $result_agendamentos = mysqli_query($link, $sql_agendamentos);

                    if ($result_agendamentos && mysqli_num_rows($result_agendamentos) > 0) {
                        while ($agendamento = mysqli_fetch_assoc($result_agendamentos)) {
                            // Determina a classe da linha com base no status e data
                            $row_class = 'table-row';
                            if ($agendamento['status'] == 'Concluído') {
                                $row_class .= ' completed-appointment'; // Prioridade 1: Concluído (verde)
                            } elseif ($agendamento['data_agendamento'] < $today_date && $agendamento['status'] != 'Concluído' && $agendamento['status'] != 'Cancelado') {
                                $row_class .= ' expired-appointment'; // Prioridade 2: Vencido (vermelho)
                            } elseif ($agendamento['data_agendamento'] == $today_date) {
                                $row_class .= ' highlight-today'; // Prioridade 3: Hoje (amarelo)
                            }
                            // Se nenhuma das anteriores, permanece 'table-row' (branco/faded)

                            // Decodifica os serviços agregados para exibição
                            $servicos_agregados_exibicao = [];
                            $decoded_services = json_decode($agendamento['servicos_agregados'], true);
                            if (is_array($decoded_services)) {
                                foreach ($decoded_services as $service_key) {
                                    $servicos_agregados_exibicao[] = $servicos_agregados_opcoes[$service_key] ?? $service_key; // Usa o label formatado ou a key original
                                }
                            }
                            $servicos_agregados_str = !empty($servicos_agregados_exibicao) ? implode(', ', $servicos_agregados_exibicao) : '-';

                            echo "<tr class='" . $row_class . "'>";
                            echo "<td>" . htmlspecialchars($agendamento['id']) . "</td>";
                            echo "<td>" . htmlspecialchars(date('d/m/Y', strtotime($agendamento['data_agendamento']))) . "</td>";
                            echo "<td>" . htmlspecialchars($agendamento['tipo_pelicula']) . "</td>";
                            echo "<td>" . htmlspecialchars($agendamento['concessionaria_nome']) . "</td>";
                            echo "<td>" . htmlspecialchars($agendamento['aplicador']) . "</td>";
                            echo "<td>" . htmlspecialchars($agendamento['servico']) . "</td>";
                            echo "<td>" . htmlspecialchars($servicos_agregados_str) . "</td>";
                            echo "<td>" . htmlspecialchars($agendamento['status']) . "</td>";
                            echo "<td>" . htmlspecialchars($agendamento['observacoes']) . "</td>";
                            echo "<td>";
                            // Botões de Ação
                            echo "<a href='editar_agendamento.php?id=" . htmlspecialchars($agendamento['id']) . "' class='text-blue-600 hover:text-blue-900 mr-2'>Editar</a>"; // Botão de Edição
                            if ($agendamento['status'] == 'Agendado' || $agendamento['status'] == 'Reagendado') {
                                echo "<a href='agenda_aplicadores.php?" . http_build_query(array_merge($_GET, ['action' => 'change_status', 'id' => $agendamento['id'], 'new_status' => 'Concluído'])) . "' class='text-green-600 hover:text-green-900 mr-2'>Concluir</a>";
                                echo "<a href='agenda_aplicadores.php?" . http_build_query(array_merge($_GET, ['action' => 'change_status', 'id' => $agendamento['id'], 'new_status' => 'Cancelado'])) . "' class='text-red-600 hover:text-red-900'>Cancelar</a>";
                            } else if ($agendamento['status'] == 'Concluído' || $agendamento['status'] == 'Cancelado') {
                                echo "<span class='text-gray-500'>-</span>"; // Nenhuma ação de status se já estiver concluído/cancelado
                            }
                            echo "</td>";
                            echo "</tr>";
                        }
                        mysqli_free_result($result_agendamentos);
                    } else {
                        echo "<tr><td colspan='10' class='text-center py-4'>Nenhum agendamento encontrado para os filtros selecionados.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="app-footer">
        <p>&copy; <?php echo date('Y'); ?> Sistema de Gestão de Estoque (SGE). Todos os direitos reservados.</p>
        <p>Desenvolvido por Dani "Emo" Roger</p>
        <?php
        // Exibe mensagens no rodapé, se houver
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>
    </footer>

</body>
</html>