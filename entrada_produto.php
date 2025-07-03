<?php
// entrada_produto.php
// Esta página exibe o formulário para registrar entradas e lista as entradas existentes.
// Não deve conter lógica de redirecionamento que se ative ao carregar a página via GET.

// Inclui o arquivo de configuração do banco de dados
require_once 'config.php';

// Verificação da conexão (opcional, para depuração)
if (!$link) {
    die("Erro: Conexão com o banco de dados não estabelecida em entrada_produto.php. Verifique config.php");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Entrada de Produto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #333;
            /* Adicionado para rodapé fixo */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 1.5rem;
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            /* Adicionado para rodapé fixo */
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
        <h1 class="text-3xl font-bold text-center mb-6 text-gray-800">Registrar Entrada de Produto</h1>

        <?php
        // Exibe mensagens de sucesso ou erro passadas via URL (bloco original no topo)
        // Você pode remover este bloco se quiser que as mensagens apareçam APENAS no rodapé.
        // Se ambos estiverem presentes e não houver controle, a mensagem pode aparecer duas vezes.
        if (isset($_GET['message']) && (!isset($_GET['display_in_footer']) || $_GET['display_in_footer'] != 'true')) {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>

        <form action="processa_entrada.php" method="POST" class="grid grid-cols-1 gap-4 mb-8 p-6 bg-white rounded-lg shadow-md">
            <div>
                <label for="produto_id" class="block text-sm font-medium text-gray-700 mb-1">Produto:</label>
                <select id="produto_id" name="produto_id" class="form-select" required>
                    <option value="">Selecione um produto</option>
                    <?php
                    // PHP para carregar produtos do banco de dados
                    $sql_produtos = "SELECT id, nome, unidade_medida FROM produtos ORDER BY nome ASC";
                    $result_produtos = mysqli_query($link, $sql_produtos);

                    if ($result_produtos && mysqli_num_rows($result_produtos) > 0) {
                        while ($produto = mysqli_fetch_assoc($result_produtos)) {
                            echo "<option value='" . htmlspecialchars($produto['id']) . "'>"
                               . htmlspecialchars($produto['nome']) . " (" . htmlspecialchars($produto['unidade_medida']) . ")"
                               . "</option>";
                        }
                        mysqli_free_result($result_produtos); // Libera o conjunto de resultados
                    } else {
                        echo "<option value=''>Nenhum produto cadastrado</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="quantidade" class="block text-sm font-medium text-gray-700 mb-1">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" class="form-input" min="1" required>
            </div>
            <div>
                <label for="data_entrada" class="block text-sm font-medium text-gray-700 mb-1">Data da Entrada:</label>
                <input type="date" id="data_entrada" name="data_entrada" class="form-input" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="flex justify-end space-x-4">
                <a href="index.php" class="btn-secondary">Voltar</a>
                <button type="submit" class="btn-primary">Registrar Entrada</button>
            </div>
        </form>

        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Entradas Registradas</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="table-header">
                    <tr>
                        <th>ID da Entrada</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Data da Entrada</th>
                        <th>Data de Registro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php
                    // PHP para listar entradas registradas do banco de dados
                    $sql_entradas = "SELECT e.id, e.quantidade, e.data_entrada, e.data_registro, p.nome, p.unidade_medida
                                     FROM entradas e
                                     JOIN produtos p ON e.produto_id = p.id
                                     ORDER BY e.data_registro DESC";

                    $result_entradas = mysqli_query($link, $sql_entradas);

                    if ($result_entradas && mysqli_num_rows($result_entradas) > 0) {
                        while ($entrada = mysqli_fetch_assoc($result_entradas)) {
                            echo "<tr class='table-row'>";
                            echo "<td>" . htmlspecialchars($entrada['id']) . "</td>";
                            echo "<td>" . htmlspecialchars($entrada['nome']) . " (" . htmlspecialchars($entrada['unidade_medida']) . ")</td>";
                            echo "<td>" . htmlspecialchars(date('d/m/Y', strtotime($entrada['data_entrada']))) . "</td>";
                            echo "<td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($entrada['data_registro']))) . "</td>";
                            echo "</tr>";
                        }
                        mysqli_free_result($result_entradas); // Libera o conjunto de resultados
                    } else {
                        echo "<tr><td colspan='5' class='text-center py-4'>Nenhuma entrada registrada ainda.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="app-footer">
        <p>&copy; <?php echo date('Y'); ?> Sistema de Gestão de Estoque (SGE). Todos os direitos reservados ,.</p>
        <p>Desenvolvido por [dani "emo"® roger]</p>

        <?php
        // Mensagem específica para o rodapé ou duplicada do cabeçalho
        // Este bloco exibirá a mensagem se ela foi passada via URL.
        // Você pode controlar se a mensagem aparece aqui, no topo, ou em ambos.
        // Remova o bloco PHP de mensagem do topo se quiser que as mensagens apareçam APENAS no rodapé.
        if (isset($_GET['message'])) {
            $message = htmlspecialchars($_GET['message']);
            // Se você passar 'type_message' do script de processamento, use-o. Senão, 'type' é o padrão.
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>
    </footer>

</body>
</html>
<?php
// É uma boa prática fechar a conexão com o banco de dados quando ela não for mais necessária no script.
if (isset($link) && is_object($link)) {
    mysqli_close($link);
}
?>