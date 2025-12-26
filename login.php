<?php
require_once 'auth.php';

// Se já estiver autenticado, redireciona para o cardápio
if (verificarAuth()) {
    header('Location: index.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cardápio</title>
    <style>
        :root {
            --bg-color: #f9f9f9;
            --text-color: #000;
            --card-bg: #fff;
            --button-bg: #27ae60;
            --input-bg: #fff;
        }

        [data-tema="escuro"] {
            --bg-color: #121212;
            --text-color: #f9f9f9;
            --card-bg: #1e1e1e;
            --button-bg: #2ecc71;
            --input-bg: #2c2c2c;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: var(--bg-color);
            color: var(--text-color);
        }

        .login-container {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
            text-align: center;
        }

        .login-container h1 {
            margin-bottom: 1.5rem;
            color: var(--text-color);
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            background: var(--input-bg);
            color: var(--text-color);
            box-sizing: border-box;
        }

        .btn-login {
            background: var(--button-bg);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .error-message {
            color: #e74c3c;
            margin-top: 1rem;
            display: none;
        }

        .theme-toggle {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: var(--button-bg);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="alternarTema()">🌓 Modo Escuro</button>

    <div class="login-container">
        <h1>Login - Cardápio</h1>
        <form id="loginForm" onsubmit="return fazerLogin(event)">
            <div class="form-group">
                <label for="usuario">Usuário</label>
                <input type="text" id="usuario" name="usuario" required>
            </div>
            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn-login">Entrar</button>
        </form>
        <div id="errorMessage" class="error-message"></div>
    </div>

    <script>
        function alternarTema() {
            const temaAtual = document.documentElement.getAttribute("data-tema");
            const novoTema = temaAtual === "escuro" ? "claro" : "escuro";
            document.documentElement.setAttribute("data-tema", novoTema);
            localStorage.setItem("tema", novoTema);
        }

        // Carregar tema salvo
        document.addEventListener("DOMContentLoaded", () => {
            const temaSalvo = localStorage.getItem("tema") || "claro";
            document.documentElement.setAttribute("data-tema", temaSalvo);
        });

        async function fazerLogin(event) {
            event.preventDefault();
            
            const usuario = document.getElementById('usuario').value;
            const senha = document.getElementById('senha').value;
            const errorMessage = document.getElementById('errorMessage');
            
            try {
                const response = await fetch('auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=login&usuario=${encodeURIComponent(usuario)}&senha=${encodeURIComponent(senha)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = 'index.html';
                } else {
                    errorMessage.textContent = data.message;
                    errorMessage.style.display = 'block';
                }
            } catch (error) {
                errorMessage.textContent = 'Erro ao fazer login. Tente novamente.';
                errorMessage.style.display = 'block';
            }
            
            return false;
        }
    </script>
</body>
</html> 