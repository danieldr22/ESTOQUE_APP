<?php
// saida_produto.php
require_once 'config.php';

// Verificação de conexão (para depuração, pode ser removido em produção)
if (!$link) {
    die("Erro: Conexão com o banco de dados não estabelecida em saida_produto.php. Verifique config.php.");
}

// --- Lógica para filtros ---
$selected_concessionaria_id = $_GET['filter_concessionaria_id'] ?? '';
$selected_month_year = $_GET['filter_month_year'] ?? ''; // Format: YYYY-MM

// Prepare filter conditions for SQL
$filter_conditions = [];
if (!empty($selected_concessionaria_id)) {
    $filter_conditions[] = "s.concessionaria_id = " . intval($selected_concessionaria_id);
}
if (!empty($selected_month_year)) {
    $filter_conditions[] = "DATE_FORMAT(s.data_saida, '%Y-%m') = '" . mysqli_real_escape_string($link, $selected_month_year) . "'";
}

$where_clause = '';
if (!empty($filter_conditions)) {
    $where_clause = " WHERE " . implode(' AND ', $filter_conditions);
}

// --- Preencher dropdown de Meses/Anos dinamicamente ---
$months_years = [];
$sql_months_years = "SELECT DISTINCT DATE_FORMAT(data_saida, '%Y-%m') AS month_year_raw,
                                     DATE_FORMAT(data_saida, '%m/%Y') AS month_year_formatted
                      FROM saidas
                      ORDER BY month_year_raw DESC";
$result_months_years = mysqli_query($link, $sql_months_years);
if ($result_months_years) {
    while ($row = mysqli_fetch_assoc($result_months_years)) {
        $months_years[$row['month_year_raw']] = $row['month_year_formatted'];
    }
    mysqli_free_result($result_months_years);
}

// --- Preencher dropdown de Concessionárias dinamicamente ---
$concessionarias_for_filter = [];
$sql_concessionarias_filter = "SELECT id, nome FROM concessionarias ORDER BY nome ASC";
$result_concessionarias_filter = mysqli_query($link, $sql_concessionarias_filter);
if ($result_concessionarias_filter) {
    while ($row = mysqli_fetch_assoc($result_concessionarias_filter)) {
        $concessionarias_for_filter[$row['id']] = $row['nome'];
    }
    mysqli_free_result($result_concessionarias_filter);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saída de Produto</title>
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
            max-width: 700px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: rgba(255, 255, 255, 0.9); /* Fundo branco semi-transparente para o container */
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            flex-grow: 1;
        }
        .form-input, .form-select { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 1rem; background-color: #f9fafb; }
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
        <h1 class="text-3xl font-bold text-center mb-6 text-gray-800">Registrar Saída de Produto</h1>

        <?php
        // Exibe mensagens de sucesso ou erro
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type_message']) ? htmlspecialchars($_GET['type_message']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>

        <form action="processa_saida.php" method="POST" class="grid grid-cols-1 gap-4 mb-8 p-6 bg-white rounded-lg shadow-md">
            <div>
                <label for="produto_id" class="block text-sm font-medium text-gray-700 mb-1">Produto:</label>
                <select id="produto_id" name="produto_id" class="form-select" required>
                    <option value="">Selecione um produto</option>
                    <?php
                    // PHP para carregar produtos do banco de dados
                    $sql_products = "SELECT id, nome, unidade_medida, quantidade_estoque FROM produtos ORDER BY nome ASC";
                    $result_products = mysqli_query($link, $sql_products);

                    if ($result_products && mysqli_num_rows($result_products) > 0) {
                        while ($product = mysqli_fetch_assoc($result_products)) {
                            // Only show products with stock
                            if ($product['quantidade_estoque'] > 0) {
                                echo "<option value='" . htmlspecialchars($product['id']) . "'>"
                                   . htmlspecialchars($product['nome']) . " (" . htmlspecialchars($product['unidade_medida']) . ") - Estoque: " . htmlspecialchars($product['quantidade_estoque'])
                                   . "</option>";
                            }
                        }
                        mysqli_free_result($result_products);
                    } else {
                        echo "<option value=''>Nenhum produto com estoque disponível</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="quantidade" class="block text-sm font-medium text-gray-700 mb-1">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" class="form-input" min="1" required>
            </div>
            <div>
                <label for="concessionaria_id" class="block text-sm font-medium text-gray-700 mb-1">Concessionária:</label>
                <select id="concessionaria_id" name="concessionaria_id" class="form-select" required>
                    <option value="">Selecione uma concessionária</option>
                    <?php
                    // PHP para carregar concessionárias do banco de dados
                    $sql_concessionarias = "SELECT id, nome FROM concessionarias ORDER BY nome ASC";
                    $result_concessionarias = mysqli_query($link, $sql_concessionarias);

                    if ($result_concessionarias && mysqli_num_rows($result_concessionarias) > 0) {
                        while ($concessionaria = mysqli_fetch_assoc($result_concessionarias)) {
                            echo "<option value='" . htmlspecialchars($concessionaria['id']) . "'>" . htmlspecialchars($concessionaria['nome']) . "</option>";
                        }
                        mysqli_free_result($result_concessionarias);
                    } else {
                        echo "<option value=''>Nenhuma concessionária cadastrada</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="data_saida" class="block text-sm font-medium text-gray-700 mb-1">Data da Saída:</label>
                <input type="date" id="data_saida" name="data_saida" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="flex justify-end space-x-4">
                <a href="index.php" class="btn-secondary">Voltar</a>
                <button type="submit" class="btn-primary">Registrar Saída</button>
            </div>
        </form>

        <hr>
        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Filtrar Saídas Registradas</h2>
        <form action="saida_produto.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 p-6 bg-white rounded-lg shadow-md">
            <div>
                <label for="filter_month_year" class="block text-sm font-medium text-gray-700 mb-1">Mês/Ano:</label>
                <select id="filter_month_year" name="filter_month_year" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($months_years as $raw => $formatted): ?>
                        <option value="<?php echo htmlspecialchars($raw); ?>" <?php echo ($selected_month_year == $raw) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($formatted); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="filter_concessionaria_id" class="block text-sm font-medium text-gray-700 mb-1">Concessionária:</label>
                <select id="filter_concessionaria_id" name="filter_concessionaria_id" class="form-select">
                    <option value="">Todas</option>
                    <?php foreach ($concessionarias_for_filter as $id => $nome): ?>
                        <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($selected_concessionaria_id == $id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nome); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn-primary w-full">Filtrar</button>
            </div>
        </form>

        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Saídas Registradas</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="table-header">
                    <tr>
                        <th>ID Saída</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Custo Total (R$)</th>
                        <th>Concessionária</th>
                        <th>Data Saída</th>
                        <th>Data Registro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    // PHP para listar saídas registradas do banco de dados com filtros
                    $sql_saidas = "SELECT s.id, s.quantidade, s.preco_custo_total, s.data_saida, s.data_registro,
                                          p.nome AS produto_nome, p.unidade_medida, c.nome AS concessionaria_nome
                                   FROM saidas s
                                   JOIN produtos p ON s.produto_id = p.id
                                   JOIN concessionarias c ON s.concessionaria_id = c.id
                                   " . $where_clause . "
                                   ORDER BY s.data_registro DESC";

                    $result_saidas = mysqli_query($link, $sql_saidas);

                    if ($result_saidas && mysqli_num_rows($result_saidas) > 0) {
                        while ($saida = mysqli_fetch_assoc($result_saidas)) {
                            echo "<tr class='table-row'>";
                            echo "<td>" . htmlspecialchars($saida['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($saida['produto_nome']) . " (" . htmlspecialchars($saida['unidade_medida']) . ")</td>";
                            echo "<td>" . htmlspecialchars($saida['quantidade']) . "</td>";
                            echo "<td>R$ " . number_format($saida['preco_custo_total'], 2, ',', '.') . "</td>";
                            echo "<td>" . htmlspecialchars($saida['concessionaria_nome']) . "</td>";
                            echo "<td>" . htmlspecialchars(date('d/m/Y', strtotime($saida['data_saida']))) . "</td>";
                            echo "<td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($saida['data_registro']))) . "</td>";
                            echo "</tr>";
                        }
                        mysqli_free_result($result_saidas);
                    } else {
                        echo "<tr><td colspan='7' class='text-center py-4'>Nenhuma saída registrada para os filtros selecionados.</td></tr>";
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