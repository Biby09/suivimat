<?php
/*
    Champs personnalisés envoyés par le formulaire des catégories (scripts/category-setup.js),
    communs à api/category/create-category.php et api/category/update-category.php.
    Chaque champ arrive sous la forme fields[clé][name] et fields[clé][default] (valeur par défaut, optionnelle).
    Prérequis : la fonction sendError(int $status, string $message) de l'API appelante.
*/

const MAX_FIELDS = 20;
const MAX_FIELD_NAME = 45;      // additional_fields.ad_name
const MAX_FIELD_DEFAULT = 200;  // additional_fields.ad_default (= longueur max d'une valeur)

/**
 * Un champ reçu → ['name' => nom nettoyé, 'default' => valeur par défaut nettoyée, ou null si vide]
 */
function readCategoryField(mixed $raw): array
{
    // Formulaire modifié à la main (fields[0]=texte au lieu d'un tableau) : champ vide, refusé ou ignoré ensuite
    $raw = is_array($raw) ? $raw : [];
    $default = trim((string) ($raw['default'] ?? ''));

    return [
        'name' => trim((string) ($raw['name'] ?? '')),
        'default' => $default === '' ? null : $default,
    ];
}

/**
 * Vérifie l'ensemble des champs d'une catégorie (nombre, longueurs, noms en double) ; sendError() sinon.
 */
function checkCategoryFields(array $fields): void
{
    if (count($fields) > MAX_FIELDS) {
        sendError(400, 'Une catégorie ne peut pas avoir plus de ' . MAX_FIELDS . ' champs personnalisés');
    }
    foreach ($fields as $field) {
        if (mb_strlen($field['name']) > MAX_FIELD_NAME) {
            sendError(400, 'Le nom d\'un champ ne peut pas dépasser ' . MAX_FIELD_NAME . ' caractères');
        }
        if ($field['default'] !== null && mb_strlen($field['default']) > MAX_FIELD_DEFAULT) {
            sendError(400, 'La valeur par défaut d\'un champ ne peut pas dépasser ' . MAX_FIELD_DEFAULT . ' caractères');
        }
    }
    // array_column(…, 'name') : la liste des noms ; « Taille » et « taille » comptent comme un doublon
    $names = array_map('mb_strtolower', array_column($fields, 'name'));
    if (count(array_unique($names)) !== count($names)) {
        sendError(400, 'Deux champs personnalisés portent le même nom');
    }
}
