<?php
require_once 'auth.php';

// Verificar se já existe algum usuário
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios");
$row = $result->fetch_assoc();

if ($row['total'] > 0) {
    die("Já existe um usuário administrador cadastrado.");
}

// Criar usuário administrador
$usuario = "admin";
$senha = "admin123"; // Você deve alterar esta senha após o primeiro acesso
$device_info = getDeviceInfo();

$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO usuarios (usuario, senha, dispositivo_id, modelo, ip, ultimo_acesso) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sssss", $usuario, $senha_hash, $device_info['device_id'], $device_info['modelo'], $device_info['ip']);

if ($stmt->execute()) {
    echo "Usuário administrador criado com sucesso!<br>";
    echo "Usuário: admin<br>";
    echo "Senha: admin123<br>";
    echo "<br>IMPORTANTE: Por favor, altere a senha após o primeiro acesso!";
} else {
    echo "Erro ao criar usuário administrador: " . $conn->error;
}

// Remover este arquivo após o uso
echo "<br><br>Por favor, delete este arquivo após criar o usuário administrador!";
?> 