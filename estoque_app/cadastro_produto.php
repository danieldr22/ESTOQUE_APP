<?php
// cadastro_produto.php
require_once 'config.php'; // Inclui o arquivo de configuração do banco de dados

// Verificação de conexão (para depuração, pode ser removido em produção)
if (!$link) {
    die("Erro: Conexão com o banco de dados não estabelecida em cadastro_produto.php. Verifique config.php.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produto</title>
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
        .table-header th { background-color: #e5e7eb; padding: 0.75rem; text-align: left; font-weight: 600; border-bottom: 2px solid #d1d5db; }
        .table-row td { padding: 0.75rem; border-bottom: 1px solid #e5e7eb; }
        .table-row:nth-child(even) { background-color: #f9fafb; }
        /* Adicionado para mensagens */
        .message-box {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .message-success { background-color: #d1fae5; color: #065f46; border: 1px solid #34d399; }
        .message-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
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
        <h1 class="text-3xl font-bold text-center mb-6 text-gray-800">Cadastro de Novo Produto</h1>

        <?php
        // Exibe mensagens de sucesso ou erro, se houver
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>

        <form action="processa_cadastro.php" method="POST" class="grid grid-cols-1 gap-4 mb-8 p-6 bg-white rounded-lg shadow-md">
            <div>
                <label for="nome_produto" class="block text-sm font-medium text-gray-700 mb-1">Nome do Produto:</label>
                <input type="text" id="nome_produto" name="nome_produto" class="form-input" required>
            </div>
            <div>
                <label for="unidade_medida" class="block text-sm font-medium text-gray-700 mb-1">Unidade de Medida (Ex: Litro, Unidade, Caixa):</label>
                <input type="text" id="unidade_medida" name="unidade_medida" class="form-input" required>
            </div>
            <div>
                <label for="preco_custo" class="block text-sm font-medium text-gray-700 mb-1">Preço de Custo (R$):</label>
                <input type="number" id="preco_custo" name="preco_custo" class="form-input" step="0.01" min="0" required>
            </div>
            <div class="flex justify-end space-x-4">
                <a href="index.php" class="btn-secondary">Voltar</a>
                <button type="submit" class="btn-primary">Cadastrar Produto</button>
            </div>
        </form>

        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Produtos Cadastrados</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="table-header">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Unidade</th>
                        <th>Preço Custo (R$)</th>
                        <th>Data Cadastro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    // Inclui o arquivo de configuração do banco de dados (já incluído no topo)
                    // require_once 'config.php'; // Não inclua novamente se já está no topo

                    // Verifica a conexão (para depuração, se necessário)
                    if (!$link) {
                        echo "<tr><td colspan='5' class='text-center py-4'>Erro: Conexão com o banco de dados não disponível.</td></tr>";
                    } else {
                        // Consulta para selecionar todos os produtos
                        $sql = "SELECT id, nome, unidade_medida, preco_custo, data_cadastro FROM produtos ORDER BY id DESC";

                        if ($result = mysqli_query($link, $sql)) {
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_array($result)) {
                                    echo "<tr class='table-row'>";
                                    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['unidade_medida']) . "</td>";
                                    echo "<td>R$ " . number_format($row['preco_custo'], 2, ',', '.') . "</td>";
                                    echo "<td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($row['data_cadastro']))) . "</td>"; // Formata a data
                                    echo "</tr>";
                                }
                                // Libera o conjunto de resultados
                                mysqli_free_result($result);
                            } else {
                                echo "<tr><td colspan='5' class='text-center py-4'>Nenhum produto cadastrado ainda.</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-4'>Erro ao carregar produtos: " . mysqli_error($link) . "</td></tr>";
                        }
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
        // Mensagem específica para o rodapé ou duplicada do cabeçalho
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