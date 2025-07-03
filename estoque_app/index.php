<?php
// index.php
// Página principal do Sistema de Gestão de Estoque

require_once 'config.php'; // Inclui a conexão com o banco de dados

// Verificação de conexão (para depuração, pode ser removido em produção)
if (!$link) {
    die("Erro: Conexão com o banco de dados não estabelecida em index.php. Verifique config.php.");
}

// Lógica para buscar dados de gasto mensal para o gráfico
$monthly_spending_data = [];
$sql_monthly_spending = "SELECT
                            DATE_FORMAT(data_saida, '%Y-%m') AS month_year,
                            SUM(preco_custo_total) AS total_cost
                           FROM
                            saidas
                           GROUP BY
                            month_year
                           ORDER BY
                            month_year ASC";

$result_monthly_spending = mysqli_query($link, $sql_monthly_spending);

if ($result_monthly_spending) {
    while ($row = mysqli_fetch_assoc($result_monthly_spending)) {
        $monthly_spending_data[] = [
            'month_year' => date('M/Y', strtotime($row['month_year'] . '-01')), // Format as Jan/2023
            'total_cost' => (float)$row['total_cost']
        ];
    }
    mysqli_free_result($result_monthly_spending);
} else {
    // Handle error if query fails
    error_log("Erro ao buscar dados de gasto mensal: " . mysqli_error($link));
}

// Fecha a conexão com o banco de dados
if (isset($link) && is_object($link)) {
    mysqli_close($link);
}

// Codifica os dados para JSON para uso no JavaScript
$json_monthly_spending = json_encode($monthly_spending_data);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestão de Estoque</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .nav-button {
            display: block;
            width: 100%;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #4f46e5; /* Azul índigo */
            color: white;
            text-align: center;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: background-color 0.2s ease-in-out;
            text-decoration: none;
        }
        .nav-button:hover { background-color: #4338ca; }
        .message-box {
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .message-success { background-color: #d1fae5; color: #065f46; border: 1px solid #34d399; }
        .message-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .chart-container {
            width: 100%;
            max-height: 400px; /* Limita a altura do gráfico */
            margin-top: 2rem;
            padding: 1rem;
            background-color: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
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
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Sistema de Gestão de Estoque</h1>

        <?php
        // Exibe mensagens de sucesso ou erro, se houver (original location)
        if (isset($_GET['message']) && !isset($_GET['display_in_footer'])) {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="cadastro_produto.php" class="nav-button">Cadastrar Novo Produto</a>
            <a href="entrada_produto.php" class="nav-button">Registrar Entrada de Produto</a>
            <a href="saida_produto.php" class="nav-button">Registrar Saída de Produto</a>
            <a href="relatorio.php?type=estoque_atual" class="nav-button">Visualizar Relatórios</a>
            <a href="agenda_aplicadores.php" class="nav-button">Agenda Aplicadores</a>
            <a href="agenda_compras.php" class="nav-button">Agenda Compras</a>
            <a href="relatorio_comissoes.php" class="nav-button">Relatório Comissões Aplicadores</a>
        </div>

        <div class="chart-container">
            <h2 class="text-2xl font-semibold mb-4 text-gray-700 text-center">Gasto Total Mensal (Saídas)</h2>
            <canvas id="monthlySpendingChart"></canvas>
        </div>

    </div>

    <footer class="app-footer">
        <p>&copy; <?php echo date('Y'); ?> Sistema de Gestão de Estoque (SGE). Todos os direitos reservados.</p>
        <p>Desenvolvido por Dani "Emo" Roger</p>
        <?php
        // Mensagem específica para o rodapé ou duplicada do cabeçalho
        if (isset($_GET['message']) && isset($_GET['display_in_footer']) && $_GET['display_in_footer'] == 'true') {
            $message = htmlspecialchars($_GET['message']);
            $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
            echo "<div class='message-box message-$type'>$message</div>";
        } else if (isset($_GET['message']) && !isset($_GET['display_in_footer'])) {
             $message = htmlspecialchars($_GET['message']);
             $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'success';
             echo "<div class='message-box message-$type'>$message</div>";
        }
        ?>
    </footer>

    <script>
        // Dados do PHP para o JavaScript
        const monthlySpendingData = <?php echo $json_monthly_spending; ?>;

        // Preparar os rótulos (meses/anos) e os dados (custos) para o Chart.js
        const labels = monthlySpendingData.map(item => item.month_year);
        const data = monthlySpendingData.map(item => item.total_cost);

        const ctx = document.getElementById('monthlySpendingChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar', // Tipo de gráfico: barra
            data: {
                labels: labels,
                datasets: [{
                    label: 'Custo Total de Saídas (R$)',
                    data: data,
                    backgroundColor: 'rgba(79, 70, 229, 0.6)', // Cor azul índigo
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Permite controlar a altura com max-height no CSS
                scales: {
                    y: {
                    beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Custo (R$)'
                        },
                        ticks: {
                            callback: function(value, index, ticks) {
                                return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Mês/Ano'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += 'R$ ' + context.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>