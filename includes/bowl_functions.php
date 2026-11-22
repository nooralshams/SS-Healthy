<?php
/** Valide une selection de bowl et retourne sa composition et son prix autoritaires. */
function preparer_selection_bowl($selection, array $config) {
    if (!is_array($selection)) {
        return ['ok' => false, 'message' => 'La sélection du bol est invalide.'];
    }

    $sizeId = $selection['size'] ?? '';
    if (!is_string($sizeId) || !isset($config['sizes'][$sizeId])) {
        return ['ok' => false, 'message' => 'Choisissez une taille valide.'];
    }

    $nettoyer = static function ($choix, array $autorises) {
        if (!is_array($choix)) {
            return null;
        }
        foreach ($choix as $id) {
            if (!is_string($id) || !array_key_exists($id, $autorises)) {
                return null;
            }
        }
        return array_values(array_unique($choix));
    };

    $bases = $nettoyer($selection['bases'] ?? [], $config['bases']);
    $proteines = $nettoyer($selection['proteins'] ?? [], $config['proteins']);
    $legumes = $nettoyer($selection['vegetables'] ?? [], $config['vegetables']);
    $sauce = $selection['sauce'] ?? '';
    $extras = $selection['extras'] ?? [];
    $unitesSupplementaires = filter_var($selection['extraUnits'] ?? 0, FILTER_VALIDATE_INT);

    if ($bases === null || $proteines === null || $legumes === null || !is_array($extras)
        || !is_string($sauce) || !isset($config['sauces'][$sauce])
        || $unitesSupplementaires === false || $unitesSupplementaires < 0
        || $unitesSupplementaires > $config['extras']['max_extra_units']) {
        return ['ok' => false, 'message' => 'Vérifiez les ingrédients de votre bowl.'];
    }
    if (!$bases) {
        return ['ok' => false, 'message' => 'Une base est requise.'];
    }
    if (!$proteines) {
        return ['ok' => false, 'message' => 'Une protéine est requise.'];
    }

    $unitesUtilisees = count($bases) + count($proteines) + count($legumes);
    $unitesDisponibles = $config['sizes'][$sizeId]['units'] + $unitesSupplementaires;
    if ($unitesUtilisees > $unitesDisponibles) {
        return ['ok' => false, 'message' => 'Votre bol dépasse le nombre d’unités disponibles.'];
    }

    $premiumCount = 0;
    foreach ($proteines as $id) {
        if ($config['proteins'][$id]['premium']) {
            $premiumCount++;
        }
    }

    $size = $config['sizes'][$sizeId];
    $price = $size['price']
        + $premiumCount * $config['extras']['premium_protein_price']
        + $unitesSupplementaires * $config['extras']['extra_unit_price'];
    $quinoaSelection = ($extras['quinoaSelection'] ?? false) === true;
    $composition = [
        'Taille' => $size['label'],
        'Bases' => implode(', ', array_map(static fn ($id) => $config['bases'][$id], $bases)),
        'Protéines' => implode(', ', array_map(static fn ($id) => $config['proteins'][$id]['label'], $proteines)),
        'Légumes' => $legumes ? implode(', ', array_map(static fn ($id) => $config['vegetables'][$id], $legumes)) : 'Aucun',
        'Sauce' => $config['sauces'][$sauce],
        'Extras' => array_values(array_filter([
            $quinoaSelection ? 'Quinoa Sélection' : null,
            $unitesSupplementaires ? $unitesSupplementaires . ' unité(s) supplémentaire(s)' : null,
        ])),
    ];

    return [
        'ok' => true,
        'size_id' => $sizeId,
        'size' => $size,
        'price' => $price,
        'composition' => $composition,
        'used_units' => $unitesUtilisees,
        'available_units' => $unitesDisponibles,
        'extra_units' => $unitesSupplementaires,
    ];
}
