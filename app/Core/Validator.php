<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Moteur de validation des données d'entrée
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $this->applyRule($field, $value, $rule, $params);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule, array $params): void
    {
        $isEmpty = $value === null || $value === '' || (is_array($value) && empty($value));

        switch ($rule) {
            case 'required':
                if ($isEmpty) {
                    $this->addError($field, "Le champ {$field} est obligatoire.");
                }
                break;

            case 'email':
                if (!$isEmpty && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Le champ {$field} doit être une adresse email valide.");
                }
                break;

            case 'phone':
                // Format téléphone sénégalais / ouest-africain (+221, 77, 78, 76, 70, 75, 33...)
                if (!$isEmpty) {
                    $cleaned = preg_replace('/[\s\-\.\(\)]/', '', (string) $value);
                    if (!preg_match('/^(\+221|00221)?(7[05678]|33)\d{7}$/', $cleaned) && strlen($cleaned) < 8) {
                        $this->addError($field, "Le numéro de téléphone n'est pas au format valide.");
                    }
                }
                break;

            case 'numeric':
                if (!$isEmpty && !is_numeric($value)) {
                    $this->addError($field, "Le champ {$field} doit être un nombre.");
                }
                break;

            case 'positive':
                if (!$isEmpty && (!is_numeric($value) || (float) $value < 0)) {
                    $this->addError($field, "Le champ {$field} doit être un montant positif.");
                }
                break;

            case 'min':
                $min = (int) ($params[0] ?? 0);
                if (!$isEmpty) {
                    if (is_string($value) && mb_strlen($value) < $min) {
                        $this->addError($field, "Le champ {$field} doit comporter au moins {$min} caractères.");
                    } elseif (is_numeric($value) && (float) $value < $min) {
                        $this->addError($field, "Le champ {$field} doit être supérieur ou égal à {$min}.");
                    }
                }
                break;

            case 'max':
                $max = (int) ($params[0] ?? 255);
                if (!$isEmpty) {
                    if (is_string($value) && mb_strlen($value) > $max) {
                        $this->addError($field, "Le champ {$field} ne peut dépasser {$max} caractères.");
                    } elseif (is_numeric($value) && (float) $value > $max) {
                        $this->addError($field, "Le champ {$field} ne peut dépasser {$max}.");
                    }
                }
                break;

            case 'in':
                if (!$isEmpty && !in_array((string) $value, $params, true)) {
                    $this->addError($field, "La valeur choisie pour {$field} est invalide.");
                }
                break;

            case 'date':
                if (!$isEmpty) {
                    $d = \DateTime::createFromFormat('Y-m-d', (string) $value);
                    if (!$d || $d->format('Y-m-d') !== $value) {
                        $this->addError($field, "Le champ {$field} doit être une date valide au format AAAA-MM-JJ.");
                    }
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}
