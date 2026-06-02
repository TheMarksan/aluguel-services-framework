<?php
require __DIR__ . '/vendor/autoload.php';

use MsAuth\Database;
use MsAuth\Config;

Config::load(__DIR__);
$pdo = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$hash = password_hash('admin123', PASSWORD_BCRYPT);
$email = 'admin@aluguel.dev';

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role) 
        VALUES ('Administrador', :email, :hash, 'admin')
    ");
    
    $stmt->execute(['email' => $email, 'hash' => $hash]);
    echo "Seed executado com sucesso: Admin criado na base de dados!\n";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "Aviso: O administrador já existe na base de dados.\n";
    } else {
        echo "Erro ao criar admin: " . $e->getMessage() . "\n";
    }
}