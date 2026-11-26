<?php

declare(strict_types=1);

/**
 * Seeder initial : Création du compte Super Administrateur HOME SCHOOL
 */

require_once dirname(__DIR__, 2) . '/config/app.php';
require_once dirname(__DIR__, 2) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Core\Database;

echo "--- Initialisation du Super Administrateur HOME SCHOOL ---\n";

$email = 'admin@homeschool.sn';
$password = 'AdminSchoolPay2026!';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$existing = Database::fetchOne("SELECT id FROM platform_users WHERE email = :email", [':email' => $email]);

if ($existing) {
    echo "Le compte Super Admin ({$email}) existe déjà (ID : {$existing['id']}).\n";
} else {
    Database::query(
        "INSERT INTO platform_users (full_name, email, phone, password_hash, role, is_active, created_at)
         VALUES ('Direction HOME SCHOOL', :email, '+221 77 000 00 00', :hash, 'super_admin', 1, CURRENT_TIMESTAMP)",
        [
            ':email' => $email,
            ':hash' => $passwordHash
        ]
    );
    $newId = Database::lastInsertId();
    echo "Super Administrateur créé avec succès !\n";
    echo "ID : {$newId}\n";
    echo "Email : {$email}\n";
    echo "Mot de passe initial : {$password}\n";
}
