<?php
session_start();

// USUÁRIO E SENHA PRÉ-DEFINIDOS — ALTERE AQUI SE QUISER!
$usuario_permitido = 'admin';
$senha_permitida = '123456'; // ← pode mudar para a senha que você quiser!

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    
    if ($usuario === $usuario_permitido && $senha === $senha_permitida) {
        $_SESSION['logado'] = true;
        header("Location: index.php");
        exit;
    } else {
        $erro = "Usuário ou senha incorretos!";
    }
}

// Se já estiver logado, vai direto pro painel
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — FinControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#6366f1', 600: '#4f46e5', 700: '#4338ca'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-8">
            <div class="text-center mb-8">
                <div class="bg-brand-600 text-white w-14 h-14 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-md">
                    <i class="fa-solid fa-wallet text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-slate-800">FinControl</h1>
                <p class="text-slate-500 text-sm mt-1">Entre para acessar seu painel</p>
            </div>

            <?php if ($erro): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-600 px-4 py-3 rounded-xl mb-4 text-sm flex items-center">
                <i class="fa-solid fa-circle-exclamation mr-2"></i>
                <?= $erro ?>
            </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1.5">Usuário</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="usuario" required autofocus
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm"
                            placeholder="Digite o usuário">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1.5">Senha</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="senha" required
                            class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 text-sm"
                            placeholder="Digite a senha">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-brand-600 hover:bg-brand-700 text-white py-3 rounded-xl font-medium transition shadow-sm">
                    Entrar
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-slate-400">
                Usuário: <strong>admin</strong> | Senha: <strong>123456</strong>
            </p>
        </div>
    </div>
</body>
</html>