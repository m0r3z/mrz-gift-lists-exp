# Guide Administrateur - MRZ Gift Lists

Ce guide détaille toutes les fonctionnalités d'administration du plugin.

---

## 1. Accès à l'administration

Après activation du plugin, un nouveau menu **"Listes cadeaux"** apparaît dans la barre latérale de WordPress, avec une icône cadeau 🎁.

---

## 2. Tableau de bord des listes

### Accès
**Listes cadeaux > Toutes les listes**

### Vue d'ensemble

En haut de page, trois encadrés affichent les statistiques :

```
┌─────────────┐  ┌─────────────┐  ┌─────────────┐
│     12      │  │      8      │  │      4      │
│   Total     │  │   Actives   │  │  Archivées  │
└─────────────┘  └─────────────┘  └─────────────┘
```

### Tableau des listes

| Colonne | Description |
|---------|-------------|
| **Titre** | Nom de la liste + actions (voir détails, voir sur le site, supprimer) |
| **Créateur** | Nom et email du propriétaire de la liste |
| **Produits** | Ratio achetés/total + pourcentage de progression |
| **Statut** | Point coloré + texte (● Active, ● Archivée, ● Terminée) |
| **Date** | Date et heure de création |

### Filtres disponibles

**Par statut :**
- Tous les statuts
- Active (liste en cours)
- Archivée (liste désactivée)
- Terminée (tous les produits achetés)

**Recherche :**
Recherchez par titre de liste, nom ou email du créateur.

### Actions sur les listes

**Voir détails** : Ouvre la page de détail complète de la liste

**Voir sur le site** : Ouvre la liste côté public dans un nouvel onglet

**Supprimer** : Supprime définitivement la liste et tous ses produits (demande confirmation)

---

## 3. Détail d'une liste

### Accès
Cliquez sur le titre d'une liste ou sur "Voir détails"

### Informations affichées

#### Section principale

| Information | Description |
|-------------|-------------|
| Créateur | Nom + email (lien vers le profil utilisateur) |
| Date de création | Date et heure de création de la liste |
| Date de l'événement | Date prévue pour l'événement (si renseignée) |
| Statut | Active / Archivée / Terminée |
| Clé d'accès | Code unique pour accéder à la liste + bouton "Voir" |
| Description | Texte descriptif de la liste |

#### Tableau des produits

Pour chaque produit de la liste :

| Colonne | Description |
|---------|-------------|
| Image | Miniature du produit |
| Produit | Nom (avec ⭐ si prioritaire) |
| Prix | Prix WooCommerce |
| Qté | Quantité achetée / quantité demandée |
| Statut | Disponible / Réservé / Acheté |
| Acheté par | Nom de l'acheteur + lien vers la commande |

#### Statistiques (barre latérale)

- Nombre total de produits
- Nombre de produits disponibles
- Nombre de produits achetés
- Articles achetés / total (quantités)

#### Actions disponibles

- **Voir la liste** : Ouvre la liste côté public
- **Archiver / Réactiver** : Change le statut de la liste
- **Supprimer** : Supprime définitivement (demande confirmation)

---

## 4. Réglages du plugin

### Accès
**Listes cadeaux > Réglages**

### Section "Réglages généraux"

#### Page des listes
- **Description** : Page WordPress contenant le shortcode `[mrz_gift_lists]`
- **Valeur par défaut** : Page créée automatiquement à l'activation
- **Action** : Sélectionnez une autre page si nécessaire

#### Qui peut créer des listes
- **Options** :
  - *Utilisateurs connectés uniquement* : Tout utilisateur WordPress connecté
  - *Clients WooCommerce uniquement* : Uniquement les utilisateurs avec le rôle "Client"
- **Valeur par défaut** : Utilisateurs connectés

#### Listes max par utilisateur
- **Description** : Limite le nombre de listes actives simultanées
- **Valeur** : Nombre entre 1 et 50 (0 = illimité)
- **Valeur par défaut** : 5

### Section "Emails"

#### Email administrateur
- **Description** : Adresse email recevant les notifications
- **Valeur par défaut** : Email admin du site WordPress
- **Note** : Laissez vide pour désactiver les notifications admin

#### Notifier le créateur
- **Description** : Envoie un email au créateur quand un produit est acheté
- **Valeur par défaut** : Activé ✓
- **Contenu de l'email** :
  - Nom du produit offert
  - Nom de l'acheteur
  - Message cadeau (si renseigné)

---

## 5. Intégration avec WooCommerce

### Colonne dans les commandes

Une colonne **"Liste cadeaux"** est ajoutée au tableau des commandes.

**Affichage** :
- `—` si la commande ne contient pas de produits de liste
- Nom(s) de la/des liste(s) concernée(s) sinon

### Détail d'une commande

Si la commande contient un produit de liste :

1. Les métadonnées de l'article affichent "Liste de cadeaux : [Nom de la liste]"
2. Si un message cadeau a été laissé, il apparaît dans une section dédiée après l'adresse de facturation

### Notifications

**Email de nouvelle commande** (admin) :
- Inclut les informations de la liste associée

**Email de commande complétée** :
- Déclenche l'envoi au créateur de la liste (si activé)

---

## 6. Gestion des rôles et permissions

### Capacités ajoutées

| Capacité | Description |
|----------|-------------|
| `mrz_gle_manage_lists` | Accès complet à l'administration du plugin |
| `mrz_gle_edit_lists` | Modification des listes |
| `mrz_gle_delete_lists` | Suppression des listes |

### Rôles avec accès

| Rôle | Capacités |
|------|-----------|
| Administrateur | Toutes (manage, edit, delete) |
| Shop Manager | manage, edit |

### Ajouter l'accès à un autre rôle

```php
// Dans functions.php de votre thème
add_action( 'init', function() {
    $role = get_role( 'editor' ); // ou autre rôle
    if ( $role ) {
        $role->add_cap( 'mrz_gle_manage_lists' );
    }
});
```

---

## 7. Base de données

### Tables créées

| Table | Description |
|-------|-------------|
| `wp_mrz_gle_lists` | Listes de cadeaux |
| `wp_mrz_gle_items` | Produits dans les listes |

### Structure wp_mrz_gle_lists

| Colonne | Type | Description |
|---------|------|-------------|
| id | BIGINT | Identifiant unique |
| user_id | BIGINT | ID de l'utilisateur créateur |
| title | VARCHAR(255) | Titre de la liste |
| description | TEXT | Description |
| event_date | DATE | Date de l'événement |
| image_id | BIGINT | ID de l'image (attachment) |
| access_key | VARCHAR(64) | Clé d'accès unique |
| status | VARCHAR(20) | active, archived, completed |
| created_at | DATETIME | Date de création |
| updated_at | DATETIME | Date de modification |

### Structure wp_mrz_gle_items

| Colonne | Type | Description |
|---------|------|-------------|
| id | BIGINT | Identifiant unique |
| list_id | BIGINT | ID de la liste |
| product_id | BIGINT | ID du produit WooCommerce |
| variation_id | BIGINT | ID de la variation (si applicable) |
| quantity | INT | Quantité demandée |
| quantity_purchased | INT | Quantité achetée |
| priority | TINYINT | 0 ou 1 (favori) |
| note | TEXT | Note du créateur |
| status | VARCHAR(20) | available, reserved, purchased |
| purchased_by | VARCHAR(255) | Nom de l'acheteur |
| purchased_at | DATETIME | Date d'achat |
| order_id | BIGINT | ID de la commande WooCommerce |

### Désinstallation

La suppression du plugin via WordPress supprime :
- Les 2 tables de base de données
- Toutes les options (mrz_gle_*)
- Les capacités des rôles
- Les transients

**Non supprimé** : Les métadonnées des commandes WooCommerce (_mrz_gle_*)
