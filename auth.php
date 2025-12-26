<?php
session_start();

// Configurações do banco de dados (você precisará criar um banco MySQL)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cardapio_auth';

// Criar conexão com o banco
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Criar tabela se não existir
$sql = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    dispositivo_id VARCHAR(255) UNIQUE NOT NULL,
    modelo VARCHAR(100),
    ip VARCHAR(45),
    ultimo_acesso DATETIME,
    ativo BOOLEAN DEFAULT TRUE
)";

$conn->query($sql);

// Função para obter informações do dispositivo
function getDeviceInfo() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Gerar ID único do dispositivo baseado em informações disponíveis
    $device_id = md5($user_agent . $ip);
    
    // Extrair modelo do dispositivo (para dispositivos móveis)
    $modelo = "Desconhecido";
    if (preg_match('/(iPhone|iPad|Android|Windows Phone|BlackBerry)/i', $user_agent, $matches)) {
        $modelo = $matches[1];
    }
    
    return [
        'device_id' => $device_id,
        'modelo' => $modelo,
        'ip' => $ip
    ];
}

// Função para verificar autenticação
function verificarAuth() {
    global $conn;
    
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    $device_info = getDeviceInfo();
    $usuario_id = $_SESSION['usuario_id'];
    
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ? AND dispositivo_id = ? AND ativo = TRUE");
    $stmt->bind_param("is", $usuario_id, $device_info['device_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        session_destroy();
        return false;
    }
    
    // Atualizar último acesso
    $stmt = $conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    
    return true;
}

// Função para fazer login
function fazerLogin($usuario, $senha) {
    global $conn;
    
    $device_info = getDeviceInfo();
    
    $stmt = $conn->prepare("SELECT id, senha FROM usuarios WHERE usuario = ? AND ativo = TRUE");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($senha, $user['senha'])) {
            // Verificar se já existe um dispositivo registrado
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND dispositivo_id != ?");
            $stmt->bind_param("is", $user['id'], $device_info['device_id']);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Este usuário já está logado em outro dispositivo'];
            }
            
            // Atualizar informações do dispositivo
            $stmt = $conn->prepare("UPDATE usuarios SET dispositivo_id = ?, modelo = ?, ip = ?, ultimo_acesso = NOW() WHERE id = ?");
            $stmt->bind_param("sssi", $device_info['device_id'], $device_info['modelo'], $device_info['ip'], $user['id']);
            $stmt->execute();
            
            $_SESSION['usuario_id'] = $user['id'];
            return ['success' => true];
        }
    }
    
    return ['success' => false, 'message' => 'Usuário ou senha inválidos'];
}

// Função para criar novo usuário (apenas para administrador)
function criarUsuario($usuario, $senha) {
    global $conn;
    
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $device_info = getDeviceInfo();
    
    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, senha, dispositivo_id, modelo, ip, ultimo_acesso) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $usuario, $senha_hash, $device_info['device_id'], $device_info['modelo'], $device_info['ip']);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Usuário criado com sucesso'];
    }
    
    return ['success' => false, 'message' => 'Erro ao criar usuário'];
}

// Processar requisições
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Requisição inválida'];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                if (isset($_POST['usuario']) && isset($_POST['senha'])) {
                    $response = fazerLogin($_POST['usuario'], $_POST['senha']);
                }
                break;
                
            case 'criar_usuario':
                if (isset($_POST['usuario']) && isset($_POST['senha'])) {
                    $response = criarUsuario($_POST['usuario'], $_POST['senha']);
                }
                break;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Verificar autenticação para acesso ao cardápio
if (!verificarAuth() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: login.php');
    exit;
}
?> 