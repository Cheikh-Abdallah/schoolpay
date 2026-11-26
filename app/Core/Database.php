<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Singleton de gestion de la connexion PDO MySQL
 * Fournit une gestion transactionnelle atomique et sécurisée
 */
class Database
{
    private static ?PDO $instance = null;
    private static ?self $serviceInstance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance(): self
    {
        if (self::$serviceInstance === null) {
            self::$serviceInstance = new self();
        }

        return self::$serviceInstance;
    }

    /**
     * Retourne l'instance unique de connexion PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__, 2) . '/config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                Logger::error('Erreur critique de connexion MySQL: ' . $e->getMessage());
                throw new RuntimeException('Impossible de se connecter à la base de données SchoolPay.');
            }
        }

        return self::$instance;
    }

    /**
     * Exécute une fonction dans une transaction PDO protégée
     *
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     * @throws Throwable
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::getConnection();

        if ($pdo->inTransaction()) {
            return $callback($pdo);
        }

        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Logger::error('Transaction MySQL annulée (Rollback) : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exécute une requête préparée avec paramètres et retourne le statement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function execute(string $sql, array $params = []): bool
    {
        return self::query($sql, $params)->rowCount() > 0;
    }

    /**
     * Récupère une seule ligne
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupère toutes les lignes
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Récupère une valeur scalaire unique
     */
    public static function fetchValue(string $sql, array $params = []): mixed
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Retourne le dernier ID inséré
     */
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }
}
