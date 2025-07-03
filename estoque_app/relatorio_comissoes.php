<?php
// relatorio_comissoes.php
require_once 'config.php';

// Verifica a conexão com o banco de dados
if (!$link) {
    die("Erro: Conexão com o banco de dados não estabelecida em relatorio_comissoes.php. Verifique config.php.");
}

// --- Lógica para filtros de data ---
$filter_data_inicio = $_GET['filter_data_inicio'] ?? date('Y-m-01'); // Padrão: primeiro dia do mês atual
$filter_data_fim = $_GET['filter_data_fim'] ?? date('Y-m-t');   // Padrão: último dia do mês atual

$where_clause_comissoes = " WHERE aa.status = 'Concluído' "; // Sempre filtra por concluído

if (!empty($filter_data_inicio)) {
    $where_clause_comissoes .= " AND aa.data_agendamento >= '" . mysqli_real_escape_string($link, $filter_data_inicio) . "'";
}
if (!empty($filter_data_fim)) {
    $where_clause_comissoes .= " AND aa.data_agendamento <= '" . mysqli_real_escape_string($link, $filter_data_fim) . "'";
}

// Lógica para buscar os dados de comissões
$comissoes_por_aplicador = [];
$sql_comissoes = "SELECT
                      aa.aplicador,
                      COUNT(aa.id) AS total_agendamentos_concluidos
                  FROM
                      agendamentos_aplicadores aa
                  " . $where_clause_comissoes . "
                  GROUP BY
                      aa.aplicador
                  ORDER BY
                      total_agendamentos_concluidos DESC, aa.aplicador ASC";

$result_comissoes = mysqli_query($link, $sql_comissoes);

if ($result_comissoes) {
    while ($row = mysqli_fetch_assoc($result_comissoes)) {
        $comissoes_por_aplicador[] = $row;
    }
    mysqli_free_result($result_comissoes);
} else {
    error_log("Erro ao buscar dados de comissões: " . mysqli_error($link));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Comissões por Aplicador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f3f4f6; /* Cor de fallback se a imagem não carregar */
            background-image: url('imagens/background_art.jpg'); /* Substitua pelo caminho da sua imagem */
            background-size: cover; /* Faz a imagem cobrir todo o fundo */
            background-repeat: no-repeat; /* Evita que a imagem se repita */
            background-position: center center; /* Centraliza a imagem */
            background-attachment: fixed; /* Mantém a imagem fixa durante a rolagem (opcional) */
        }
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: rgba(255, 255, 255, 0.9); /* Fundo branco semi-transparente para o container */
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex-grow: 1;
        }
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
        .app-footer { margin-top: 2rem; padding: 1rem; background-color: #374151; color: white; text-align: center; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-3xl font-bold text-center mb-6 text-gray-800">Relatório de Comissões por Aplicador</h1>

        <div class="mb-4 text-center">
            <a href="index.php" class="btn-secondary">Voltar ao Início</a>
        </div>

        <?php
        // Exibe mensagens de sucesso ou erro, se houver
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>

        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Filtrar por Período</h2>
        <form action="relatorio_comissoes.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 p-6 bg-white rounded-lg shadow-md">
            <input type="hidden" name="type" value="comissoes_aplicadores">
            <div>
                <label for="filter_data_inicio" class="block text-sm font-medium text-gray-700 mb-1">Data Início:</label>
                <input type="date" id="filter_data_inicio" name="filter_data_inicio" class="form-input" value="<?php echo htmlspecialchars($filter_data_inicio); ?>">
            </div>
            <div>
                <label for="filter_data_fim" class="block text-sm font-medium text-gray-700 mb-1">Data Fim:</label>
                <input type="date" id="filter_data_fim" name="filter_data_fim" class="form-input" value="<?php echo htmlspecialchars($filter_data_fim); ?>">
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full">Aplicar Filtro</button>
            </div>
        </form>

        <h2 class="text-2xl font-semibold mb-4 text-gray-800">Agendamentos Concluídos por Aplicador</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="table-header">
                    <tr>
                        <th>Aplicador</th>
                        <th>Agendamentos Concluídos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    if (!empty($comissoes_por_aplicador)) {
                        foreach ($comissoes_por_aplicador as $comissao) {
                            echo "<tr class='table-row'>";
                            echo "<td>" . htmlspecialchars($comissao['aplicador']) . "</td>";
                            echo "<td>" . htmlspecialchars($comissao['total_agendamentos_concluidos']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2' class='text-center py-4'>Nenhum agendamento concluído encontrado para o período.</td></tr>";
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
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>
    </footer>

</body>
</html>
<?php
// Fecha a conexão com o banco de dados
if (isset($link) && is_object($link)) {
    mysqli_close($link);
}
?>