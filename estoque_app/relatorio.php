<?php
// relatorio.php
// Esta página exibe diversos relatórios de estoque e movimentação.

require_once 'config.php'; // Inclui a conexão com o banco de dados

// Verificação de conexão (para depuração, pode ser removido em produção)
if (!$link) {
    die("Erro: Conexão com o banco de dados não estabelecida em relatorio.php. Verifique config.php.");
}

// Determina o tipo de relatório a ser exibido
$report_type = $_GET['type'] ?? 'estoque_atual'; // Padrão: estoque_atual

// Captura o mês e ano para o relatório de saídas, ou define o padrão para o mês e ano atuais
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');

// Mapeamento de números de mês para nomes (para exibição)
$months = [
    '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
    '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
    '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios de Estoque</title>
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
        .nav-button-reports {
            display: inline-block; /* Para que fiquem lado a lado */
            padding: 0.75rem 1.5rem;
            margin: 0.5rem;
            background-color: #6b7280; /* Cinza secundário */
            color: white;
            text-align: center;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.2s ease-in-out;
            text-decoration: none;
        }
        .nav-button-reports:hover { background-color: #4b5563; }
        .nav-button-reports.active {
            background-color: #4f46e5; /* Azul índigo para o ativo */
        }
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
        /* Estilos adicionais para linhas de sumário */
        .summary-row {
            font-weight: bold;
            background-color: #e0e7ff; /* Cor levemente diferente para sumário */
            border-top: 2px solid #4f46e5;
        }
        /* Styles for form elements */
        .form-control {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        /* Estilo para o rodapé */
        .app-footer {
            margin-top: 2rem;
            padding: 1rem;
            background-color: #374151; /* Darker gray for footer */
            color: white;
            text-align: center;
            font-size: 0.9rem;
        }
        .app-footer .message-box {
            margin-left: auto;
            margin-right: auto;
            max-width: 600px; /* Alinha a mensagem no centro do rodapé */
            margin-bottom: 0.5rem; /* Ajusta a margem da mensagem dentro do rodapé */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-3xl font-bold text-center mb-6 text-gray-800">Relatórios de Estoque</h1>

        <div class="mb-4 text-center">
            <a href="index.php" class="btn-secondary">Voltar para o Início</a>
        </div>

        <div class="flex justify-center mb-8">
            <a href="relatorio.php?type=estoque_atual" class="nav-button-reports <?php echo ($report_type == 'estoque_atual') ? 'active' : ''; ?>">Estoque Atual</a>
            <a href="relatorio.php?type=saidas_concessionaria" class="nav-button-reports <?php echo ($report_type == 'saidas_concessionaria') ? 'active' : ''; ?>">Saídas por Concessionária (Mensal)</a>
            <a href="relatorio.php?type=entradas_geral" class="nav-button-reports <?php echo ($report_type == 'entradas_geral') ? 'active' : ''; ?>">Entradas Gerais</a>
        </div>

        <?php
        // Exibe mensagens de sucesso ou erro, se houver (para futuras implementações de filtros, etc.)
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type_message']) ? htmlspecialchars($_GET['type_message']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>

        <div class="report-content">
            <?php
            switch ($report_type) {
                case 'estoque_atual':
                    ?>
                    <h2 class="text-2xl font-semibold mb-4 text-gray-700">Relatório de Estoque Atual</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="table-header">
                                <tr>
                                    <th>ID</th>
                                    <th>Nome do Produto</th>
                                    <th>Unidade</th>
                                    <th>Preço Custo (R$)</th>
                                    <th>Estoque Atual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                $sql_estoque = "SELECT id, nome, unidade_medida, preco_custo, quantidade_estoque FROM produtos ORDER BY nome ASC";
                                $result_estoque = mysqli_query($link, $sql_estoque);

                                if ($result_estoque && mysqli_num_rows($result_estoque) > 0) {
                                    while ($produto = mysqli_fetch_assoc($result_estoque)) {
                                        echo "<tr class='table-row'>";
                                        echo "<td>" . htmlspecialchars($produto['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($produto['nome']) . "</td>";
                                        echo "<td>" . htmlspecialchars($produto['unidade_medida']) . "</td>";
                                        echo "<td>R$ " . number_format($produto['preco_custo'], 2, ',', '.') . "</td>";
                                        echo "<td>" . htmlspecialchars($produto['quantidade_estoque']) . "</td>";
                                        echo "</tr>";
                                    }
                                    mysqli_free_result($result_estoque);
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-4'>Nenhum produto cadastrado ou sem estoque.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    break;

                case 'saidas_concessionaria':
                    ?>
                    <h2 class="text-2xl font-semibold mb-4 text-gray-700">Relatório de Saídas por Concessionária (Mensal)</h2>

                    <form method="GET" action="relatorio.php" class="mb-6 bg-gray-50 p-4 rounded-lg shadow-sm">
                        <input type="hidden" name="type" value="saidas_concessionaria">
                        <div class="flex flex-wrap -mx-2 mb-4">
                            <div class="w-full md:w-1/2 px-2 mb-4 md:mb-0">
                                <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Mês:</label>
                                <select id="month" name="month" class="form-control" onchange="this.form.submit()">
                                    <?php
                                    $months = [
                                        '01' => 'Janeiro', '02' => 'Fevereiro', '03' => 'Março', '04' => 'Abril',
                                        '05' => 'Maio', '06' => 'Junho', '07' => 'Julho', '08' => 'Agosto',
                                        '09' => 'Setembro', '10' => 'Outubro', '11' => 'Novembro', '12' => 'Dezembro'
                                    ];
                                    foreach ($months as $num => $name) {
                                        $selected = ($selected_month == $num) ? 'selected' : '';
                                        echo "<option value='{$num}' {$selected}>{$name}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="w-full md:w-1/2 px-2">
                                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Ano:</label>
                                <select id="year" name="year" class="form-control" onchange="this.form.submit()">
                                    <?php
                                    $current_year_br = (int)date('Y', strtotime('now -3 hours')); // Current year in Fortaleza (GMT-3)
                                    for ($y = $current_year_br; $y >= $current_year_br - 5; $y--) { // Last 5 years
                                        $selected = ($selected_year == $y) ? 'selected' : '';
                                        echo "<option value='{$y}' {$selected}>{$y}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </form>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="table-header">
                                <tr>
                                    <th>Mês/Ano</th>
                                    <th>Concessionária</th>
                                    <th>Total Itens Saída</th>
                                    <th>Total Custo Mensal (R$)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                // SQL para somar o custo total de saídas por concessionária por mês/ano, filtrado pelo mês e ano selecionados
                                $sql_saidas_mensal = "SELECT
                                                          DATE_FORMAT(s.data_saida, '%Y-%m') AS ano_mes,
                                                          DATE_FORMAT(s.data_saida, '%m/%Y') AS mes_ano_formatado,
                                                          c.nome AS concessionaria_nome,
                                                          SUM(s.quantidade) AS total_itens_saida,
                                                          SUM(s.preco_custo_total) AS total_custo_mensal
                                                      FROM
                                                          saidas s
                                                      JOIN
                                                          concessionarias c ON s.concessionaria_id = c.id
                                                      WHERE
                                                          DATE_FORMAT(s.data_saida, '%Y-%m') = ?
                                                      GROUP BY
                                                          ano_mes, c.nome
                                                      ORDER BY
                                                          ano_mes DESC, c.nome ASC";

                                // Prepara a string do ano e mês para a consulta
                                $date_param = $selected_year . '-' . $selected_month;

                                $stmt = mysqli_prepare($link, $sql_saidas_mensal);
                                mysqli_stmt_bind_param($stmt, 's', $date_param);
                                mysqli_stmt_execute($stmt);
                                $result_saidas_mensal = mysqli_stmt_get_result($stmt);

                                if ($result_saidas_mensal && mysqli_num_rows($result_saidas_mensal) > 0) {
                                    $grand_total_custo = 0; // Total geral para o mês/ano selecionado

                                    while ($saida = mysqli_fetch_assoc($result_saidas_mensal)) {
                                        $grand_total_custo += $saida['total_custo_mensal'];

                                        echo "<tr class='table-row'>";
                                        echo "<td>" . htmlspecialchars($saida['mes_ano_formatado']) . "</td>";
                                        echo "<td>" . htmlspecialchars($saida['concessionaria_nome']) . "</td>";
                                        echo "<td>" . htmlspecialchars($saida['total_itens_saida']) . "</td>";
                                        echo "<td>R$ " . number_format($saida['total_custo_mensal'], 2, ',', '.') . "</td>";
                                        echo "</tr>";
                                    }
                                    mysqli_free_result($result_saidas_mensal);
                                    mysqli_stmt_close($stmt);

                                    // Exibe o total geral para o mês selecionado
                                    echo "<tr class='table-row summary-row bg-indigo-100'>";
                                    echo "<td colspan='3' class='text-right pr-4 text-indigo-800'>TOTAL DE CUSTO PARA " . htmlspecialchars($months[$selected_month]) . "/" . htmlspecialchars($selected_year) . ":</td>";
                                    echo "<td class='text-indigo-800'>R$ " . number_format($grand_total_custo, 2, ',', '.') . "</td>";
                                    echo "</tr>";

                                } else {
                                    echo "<tr><td colspan='4' class='text-center py-4'>Nenhuma saída registrada para " . htmlspecialchars($months[$selected_month]) . "/" . htmlspecialchars($selected_year) . ".</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    break;

                case 'entradas_geral':
                    ?>
                    <h2 class="text-2xl font-semibold mb-4 text-gray-700">Relatório de Entradas Gerais</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="table-header">
                                <tr>
                                    <th>ID Entrada</th>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Data Entrada</th>
                                    <th>Data Registro</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                $sql_entradas_geral = "SELECT e.id, e.quantidade, e.data_entrada, e.data_registro, p.nome, p.unidade_medida
                                                       FROM entradas e
                                                       JOIN produtos p ON e.produto_id = p.id
                                                       ORDER BY e.data_entrada DESC, e.data_registro DESC";

                                $result_entradas_geral = mysqli_query($link, $sql_entradas_geral);

                                if ($result_entradas_geral && mysqli_num_rows($result_entradas_geral) > 0) {
                                    while ($entrada = mysqli_fetch_assoc($result_entradas_geral)) {
                                        echo "<tr class='table-row'>";
                                        echo "<td>" . htmlspecialchars($entrada['id']) . "</td>";
                                        echo "<td>" . htmlspecialchars($entrada['nome']) . " (" . htmlspecialchars($entrada['unidade_medida']) . ")</td>";
                                        echo "<td>" . htmlspecialchars(date('d/m/Y', strtotime($entrada['data_entrada']))) . "</td>";
                                        echo "<td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($entrada['data_registro']))) . "</td>";
                                        echo "</tr>";
                                    }
                                    mysqli_free_result($result_entradas_geral);
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-4'>Nenhuma entrada registrada.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    break;

                default:
                    echo "<p class='text-center py-4 text-gray-600'>Selecione um tipo de relatório para visualizar.</p>";
                    break;
            }
            ?>
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