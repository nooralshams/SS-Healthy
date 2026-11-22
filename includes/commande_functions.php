<?php
/** Reconstruit les lignes du panier en reprenant les prix catalogue depuis la base. */
function reconstruire_articles_panier(PDO $pdo, array $panier) {
	$articles = [];
	$total = 0;

	foreach ($panier as $article) {
		$quantite = filter_var($article['quantite'] ?? null, FILTER_VALIDATE_INT);
		if (!$quantite || $quantite < 1 || $quantite > 20) {
			continue;
		}

		$estBowl = ($article['type'] ?? '') === 'bowl_personnalise';
		if ($estBowl) {
			$plat = [
				'id' => null,
				'nom' => $article['nom'] ?? 'Bowl personnalisé',
				'prix' => max(0, (int) ($article['prix'] ?? 0)),
				'disponible' => 1,
			];
		} else {
			$platId = filter_var($article['plat_id'] ?? null, FILTER_VALIDATE_INT);
			if (!$platId) {
				continue;
			}
			$stmt = $pdo->prepare('SELECT id, nom, prix, disponible FROM plats WHERE id = ?');
			$stmt->execute([$platId]);
			$plat = $stmt->fetch();
		}

		if (!$plat) {
			continue;
		}

		$articles[] = [
			'plat' => $plat,
			'quantite' => $quantite,
			'est_bowl' => $estBowl,
			'composition' => is_array($article['description'] ?? null) ? $article['description'] : [],
		];
		$total += (int) $plat['prix'] * $quantite;
	}

	return ['articles' => $articles, 'total' => $total];
}

/** Enregistre les lignes du panier mixte dans une transaction atomique. */
function enregistrer_commande(PDO $pdo, array $commande, array $articles) {
	$pdo->beginTransaction();
	try {
		$stmt = $pdo->prepare(
			'INSERT INTO commandes (utilisateur_id, creneau, mode, mode_paiement, adresse_livraison, total)
			 VALUES (?, ?, ?, ?, ?, ?)'
		);
		$stmt->execute([
			$commande['utilisateur_id'],
			$commande['creneau'],
			$commande['mode'],
			$commande['mode_paiement'],
			$commande['adresse_livraison'],
			$commande['total'],
		]);
		$commandeId = (int) $pdo->lastInsertId();

		$stmtPlats = $pdo->prepare(
			'INSERT INTO details_commande (commande_id, plat_id, quantite, prix_unitaire)
			 VALUES (?, ?, ?, ?)'
		);
		$stmtBowls = null;

		foreach ($articles as $article) {
			if ($article['est_bowl']) {
				if ($stmtBowls === null) {
					$stmtBowls = $pdo->prepare(
						'INSERT INTO details_commande_bowls (commande_id, nom, composition, quantite, prix_unitaire)
						 VALUES (?, ?, ?, ?, ?)'
					);
				}
				$composition = json_encode($article['composition'], JSON_UNESCAPED_UNICODE);
				if ($composition === false) {
					throw new RuntimeException('Composition du bowl non sérialisable.');
				}
				$stmtBowls->execute([
					$commandeId,
					$article['plat']['nom'],
					$composition,
					$article['quantite'],
					$article['plat']['prix'],
				]);
				continue;
			}

			$stmtPlats->execute([
				$commandeId,
				$article['plat']['id'],
				$article['quantite'],
				$article['plat']['prix'],
			]);
		}

		$pdo->commit();
		return $commandeId;
	} catch (Throwable $e) {
		if ($pdo->inTransaction()) {
			$pdo->rollBack();
		}
		throw $e;
	}
}
