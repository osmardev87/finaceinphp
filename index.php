<?php

include 'config.php';

session_start();

// Verificar se está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

// Processar exclusão
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    $stmt = $banco->prepare("DELETE FROM transacoes WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php");
    exit;
}

// Buscar todas as transações
$stmt = $banco->query("SELECT * FROM transacoes ORDER BY data_transacao DESC");
$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular saldo
$entradas = $banco->query("SELECT SUM(valor) FROM transacoes WHERE tipo='ENTRADA'")->fetchColumn() ?: 0;
$saidas   = $banco->query("SELECT SUM(valor) FROM transacoes WHERE tipo='SAIDA'")->fetchColumn() ?: 0;
$saldo    = $entradas - $saidas;

// Nomes dos meses em português
$nomesMeses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// Dados dos últimos 4 meses para os gráficos
$meses = [];
$dadosEntrada = [];
$dadosSaida = [];

for ($i = 3; $i >= 0; $i--) {
    $data = new DateTime();
    $data->modify("-$i month");
    $mesNum = $data->format('Y-m');
    $mesIndice = (int)$data->format('n'); // 1 a 12
    
    $me = $banco->prepare("SELECT SUM(valor) FROM transacoes WHERE tipo='ENTRADA' AND data_transacao LIKE ?");
    $me->execute([$mesNum . '%']);
    $se = $me->fetchColumn() ?: 0;
    
    $ms = $banco->prepare("SELECT SUM(valor) FROM transacoes WHERE tipo='SAIDA' AND data_transacao LIKE ?");
    $ms->execute([$mesNum . '%']);
    $ss = $ms->fetchColumn() ?: 0;
    
    $meses[] = $nomesMeses[$mesIndice];
    $dadosEntrada[] = (float)$se;
    $dadosSaida[] = (float)$ss;
}

// Gastos por categoria
$categorias = ['Alimentação' => 0, 'Moradia' => 0, 'Transporte' => 0, 'Lazer' => 0, 'Outros' => 0];
$stmtCat = $banco->query("SELECT categoria, SUM(valor) as total FROM transacoes WHERE tipo='SAIDA' GROUP BY categoria");
foreach ($stmtCat as $row) {
    if (isset($categorias[$row['categoria']])) {
        $categorias[$row['categoria']] = (float)$row['total'];
    } else {
        $categorias['Outros'] += (float)$row['total'];
    }
}
$valoresCategorias = array_values($categorias);
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Financeiro — FinControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef2ff', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 font-sans transition-colors duration-200">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="hidden md:flex flex-col w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700">
            <div class="flex items-center justify-center h-16 border-b border-slate-200 dark:border-slate-700 px-6">
                <div class="bg-brand-600 text-white p-2 rounded-xl shadow-md">
                    <i class="fa-solid fa-wallet text-lg"></i>
                </div>
                <span class="ml-3 text-xl font-bold bg-gradient-to-r from-brand-600 to-indigo-500 bg-clip-text text-transparent">FinControl</span>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="#" onclick="switchTab('dashboard')" id="nav-dashboard" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl bg-brand-50 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 transition">
                    <i class="fa-solid fa-chart-pie w-6"></i> Dashboard
                </a>
                <a href="#" onclick="switchTab('transactions')" id="nav-transactions" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                    <i class="fa-solid fa-receipt w-6"></i> Transações
                </a>
                <a href="sair.php" class="text-slate-500 hover:text-rose-600 transition ml-2" title="Sair">
                    <i class="fa-solid fa-right-from-bracket">Sair</i>
                </a>
            </nav>
            <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                <button onclick="toggleDarkMode()" class="w-full flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-sm font-medium transition">
                    <i class="fa-solid fa-moon dark:hidden"></i>
                    <i class="fa-solid fa-sun hidden dark:inline text-amber-400"></i>
                    <span class="dark:hidden">Modo Escuro</span>
                    <span class="hidden dark:inline">Modo Claro</span>
                </button>
            </div>
        </aside>

        <!-- Conteúdo Principal -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between px-6">
                <div class="flex items-center space-x-4">
                    <button class="md:hidden text-slate-600 dark:text-slate-400">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <h1 id="page-title" class="text-lg font-bold">Dashboard Geral</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-xs bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-3 py-1.5 rounded-full font-semibold border border-emerald-200 dark:border-emerald-800">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block mr-1.5 animate-pulse"></span>
                        Banco Conectado
                    </span>
                    <button onclick="openTransactionModal()" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl text-sm font-medium shadow-sm transition flex items-center space-x-2">
                        <i class="fa-solid fa-plus"></i>
                        <span class="hidden sm:inline">Nova Transação</span>
                    </button>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6">
                <!-- Dashboard -->
                <div id="tab-dashboard" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Saldo Total</span>
                                <div class="p-3 bg-brand-50 dark:bg-brand-900/40 text-brand-600 dark:text-brand-400 rounded-xl">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                            </div>
                            <h3 class="text-2xl font-bold <?= $saldo >= 0 ? '' : 'text-rose-600' ?>">
                                R$ <?= number_format($saldo, 2, ',', '.') ?>
                            </h3>
                            <span class="text-xs text-emerald-500 font-medium mt-2 inline-flex items-center">
                                <i class="fa-solid fa-database mr-1"></i> Direto do banco
                            </span>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Receitas</span>
                                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-xl">
                                    <i class="fa-solid fa-arrow-down-long"></i>
                                </div>
                            </div>
                            <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                R$ <?= number_format($entradas, 2, ',', '.') ?>
                            </h3>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Despesas</span>
                                <div class="p-3 bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 rounded-xl">
                                    <i class="fa-solid fa-arrow-up-long"></i>
                                </div>
                            </div>
                            <h3 class="text-2xl font-bold text-rose-600 dark:text-rose-400">
                                R$ <?= number_format($saidas, 2, ',', '.') ?>
                            </h3>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Economia</span>
                                <div class="p-3 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                                    <i class="fa-solid fa-piggy-bank"></i>
                                </div>
                            </div>
                            <h3 class="text-2xl font-bold">
                                R$ <?= number_format(max(0, $saldo), 2, ',', '.') ?>
                            </h3>
                            <span class="text-xs text-slate-400 mt-2 block">
                                <?= $entradas > 0 ? round(($saldo / $entradas) * 100, 1) : 0 ?>% da receita
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <h4 class="font-bold text-base mb-4">Fluxo de Caixa — Últimos 4 Meses</h4>
                            <div class="h-72 relative">
                                <canvas id="cashflowChart"></canvas>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm">
                            <h4 class="font-bold text-base mb-4">Gastos por Categoria</h4>
                            <div class="h-72 relative flex items-center justify-center">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transações -->
                <div id="tab-transactions" class="space-y-6 hidden">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h3 class="font-bold text-lg">Histórico de Transações</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Dados diretos do banco SQLite</p>
                            </div>
                            <button onclick="openTransactionModal()" class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-xl text-sm font-medium shadow-sm transition">
                                <i class="fa-solid fa-plus mr-1"></i> Adicionar
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-900/50 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                                        <th class="py-3 px-6">Descrição</th>
                                        <th class="py-3 px-6">Categoria</th>
                                        <th class="py-3 px-6">Data</th>
                                        <th class="py-3 px-6">Tipo</th>
                                        <th class="py-3 px-6 text-right">Valor</th>
                                        <th class="py-3 px-6 text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-sm">
                                    <?php if (empty($transacoes)): ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400">
                                            Nenhuma transação cadastrada ainda.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php foreach ($transacoes as $t): ?>
                                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                        <td class="py-3 px-6 font-medium">
                                            <?= htmlspecialchars($t['descricao']) ?>
                                        </td>
                                        <td class="py-3 px-6">
                                            <span class="px-2.5 py-1 text-xs bg-slate-100 dark:bg-slate-700 rounded-lg">
                                                <?= htmlspecialchars($t['categoria']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-6 text-slate-500 dark:text-slate-400">
                                            <?= date('d/m/Y', strtotime($t['data_transacao'])) ?>
                                        </td>
                                        <td class="py-3 px-6">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold 
                                                <?= $t['tipo'] === 'ENTRADA' 
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400' 
                                                    : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-400' ?>">
                                                <?= $t['tipo'] === 'ENTRADA' ? 'Receita' : 'Despesa' ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-6 text-right font-bold 
                                            <?= $t['tipo'] === 'ENTRADA' ? 'text-emerald-600' : 'text-rose-600' ?>">
                                            <?= $t['tipo'] === 'ENTRADA' ? '+' : '-' ?> 
                                            R$ <?= number_format($t['valor'], 2, ',', '.') ?>
                                        </td>
                                        <td class="py-3 px-6 text-center">
                                            <a href="?excluir=<?= $t['id'] ?>" 
                                               onclick="return confirm('Tem certeza que deseja excluir essa transação?')"
                                               class="text-slate-400 hover:text-rose-600 transition" title="Excluir">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="transaction-modal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md p-6 shadow-xl mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg">Nova Transação</h3>
                <button onclick="closeTransactionModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form action="salvar.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Descrição</label>
                    <input type="text" name="descricao" required placeholder="Ex: Salário, Supermercado..." 
                        class="w-full px-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Valor (R$)</label>
                        <input type="number" step="0.01" name="valor" required placeholder="0,00" 
                            class="w-full px-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Tipo</label>
                        <select name="tipo" 
                            class="w-full px-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="ENTRADA">Receita</option>
                            <option value="SAIDA">Despesa</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Categoria</label>
                    <select name="categoria" 
                        class="w-full px-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <option value="Alimentação">Alimentação</option>
                        <option value="Moradia">Moradia</option>
                        <option value="Transporte">Transporte</option>
                        <option value="Lazer">Lazer</option>
                        <option value="Outros">Outros</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Data</label>
                    <input type="date" name="data_transacao" required value="<?= date('Y-m-d') ?>" 
                        class="w-full px-4 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500">
                </div>
                <div class="flex justify-end space-x-3 pt-2">
                    <button type="button" onclick="closeTransactionModal()" 
                        class="px-4 py-2 text-sm font-medium rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700">
                        Cancelar
                    </button>
                    <button type="submit" 
                        class="bg-brand-600 hover:bg-brand-700 text-white px-5 py-2 text-sm font-medium rounded-xl shadow-sm transition">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.getElementById('tab-dashboard').classList.add('hidden');
            document.getElementById('tab-transactions').classList.add('hidden');
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            
            document.getElementById('nav-dashboard').classList.remove('bg-brand-50', 'dark:bg-brand-900/40', 'text-brand-600', 'dark:text-brand-400');
            document.getElementById('nav-dashboard').classList.add('text-slate-600', 'dark:text-slate-400');
            document.getElementById('nav-transactions').classList.remove('bg-brand-50', 'dark:bg-brand-900/40', 'text-brand-600', 'dark:text-brand-400');
            document.getElementById('nav-transactions').classList.add('text-slate-600', 'dark:text-slate-400');
            
            document.getElementById('nav-' + tabName).classList.add('bg-brand-50', 'dark:bg-brand-900/40', 'text-brand-600', 'dark:text-brand-400');
            document.getElementById('nav-' + tabName).classList.remove('text-slate-600', 'dark:text-slate-400');
            
            document.getElementById('page-title').textContent = tabName === 'dashboard' ? 'Dashboard Geral' : 'Transações';
        }

        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
        }

        function openTransactionModal() {
            document.getElementById('transaction-modal').classList.remove('hidden');
        }
        function closeTransactionModal() {
            document.getElementById('transaction-modal').classList.add('hidden');
        }

        // Fechar modal clicando fora
        document.getElementById('transaction-modal').addEventListener('click', function(e) {
            if (e.target === this) closeTransactionModal();
        });

        // Dados dos gráficos
        const dadosMeses = <?= json_encode($meses) ?>;
        const dadosEntrada = <?= json_encode($dadosEntrada) ?>;
        const dadosSaida = <?= json_encode($dadosSaida) ?>;
        const dadosCategorias = <?= json_encode($valoresCategorias) ?>;

        new Chart(document.getElementById('cashflowChart'), {
            type: 'bar',
            data: {
                labels: dadosMeses,
                datasets: [
                    { label: 'Receitas', data: dadosEntrada, backgroundColor: '#10b981', borderRadius: 8 },
                    { label: 'Despesas', data: dadosSaida, backgroundColor: '#f43f5e', borderRadius: 8 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: ['Alimentação', 'Moradia', 'Transporte', 'Lazer', 'Outros'],
                datasets: [{ 
                    data: dadosCategorias, 
                    backgroundColor: ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#94a3b8'] 
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</body>
</html>