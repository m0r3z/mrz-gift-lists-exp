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
| **Type** | Badge coloré indiquant le type (Mariage, Naissance...) |
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
| Protection | 🔒 Protégée par mot de passe / 🔓 Accès libre |
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

## 4. Gestion des types de listes

### Accès
**Listes cadeaux > Types**

### Interface

La page est divisée en deux colonnes :

**Gauche** : Tableau des types existants
**Droite** : Formulaire de création/modification

### Tableau des types

| Colonne | Description |
|---------|-------------|
| Ordre | Numéro d'ordre d'affichage |
| Nom | Nom du type + description |
| Couleur | Aperçu de la couleur (carré coloré) |
| Listes | Nombre de listes utilisant ce type |
| Actif | ✓ (vert) si actif, ✗ (rouge) si inactif |
| Actions | Bouton "Modifier" |

### Créer un type

1. Remplissez le formulaire à droite :
   - **Nom** (obligatoire) : Ex: "Pendaison de crémaillère"
   - **Description** : Texte explicatif
   - **Icône** : Choisissez parmi les emojis disponibles
   - **Couleur** : Sélectionnez avec le color picker
   - **Ordre** : Position dans les listes (0 = premier)
   - **Actif** : Cochez pour rendre disponible

2. Cliquez sur **"Créer"**

### Modifier un type

1. Cliquez sur **"Modifier"** dans le tableau
2. Le formulaire se remplit avec les données existantes
3. Modifiez les champs souhaités
4. Cliquez sur **"Mettre à jour"**

### Supprimer un type

- Un type ne peut être supprimé que s'il n'est utilisé par aucune liste
- Cliquez sur **"Supprimer"** (bouton rouge en bas du formulaire)
- Si le type est utilisé, le bouton n'apparaît pas

### Types par défaut

Le plugin crée automatiquement 5 types à l'activation :

| Type | Icône | Couleur | Ordre |
|------|-------|---------|-------|
| Mariage | ❤️ Coeur | Rose (#e91e63) | 1 |
| Naissance | 👶 Bébé | Bleu (#03a9f4) | 2 |
| Anniversaire | 🎂 Gâteau | Orange (#ff9800) | 3 |
| Noël | 🎁 Cadeau | Rouge (#f44336) | 4 |
| Autre | 📋 Liste | Gris (#9e9e9e) | 99 |

---

## 5. Réglages du plugin

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

### Section "Affichage"

#### Mode d'affichage
- **Options** :
  - *Grille* : Produits affichés en grille responsive
  - *Liste* : Produits affichés en liste verticale
- **Valeur par défaut** : Grille

#### Produits par page
- **Description** : Nombre de produits affichés par page
- **Valeur** : Nombre entre 4 et 100
- **Valeur par défaut** : 12

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

### Section "Partage"

#### Réseaux de partage
Cochez les boutons de partage à afficher sur les listes :

- [ ] Facebook
- [ ] Twitter/X
- [ ] Pinterest
- [ ] WhatsApp
- [ ] Email
- [ ] Copier le lien

**Valeur par défaut** : Facebook, WhatsApp, Email, Copier le lien

---

## 6. Intégration avec WooCommerce

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

## 7. Gestion des rôles et permissions

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

## 8. Base de données

### Tables créées

| Table | Description |
|-------|-------------|
| `wp_mrz_gle_lists` | Listes de cadeaux |
| `wp_mrz_gle_items` | Produits dans les listes |
| `wp_mrz_gle_types` | Types de listes |

### Structure wp_mrz_gle_lists

| Colonne | Type | Description |
|---------|------|-------------|
| id | BIGINT | Identifiant unique |
| user_id | BIGINT | ID de l'utilisateur créateur |
| type_id | BIGINT | ID du type de liste |
| title | VARCHAR(255) | Titre de la liste |
| description | TEXT | Description |
| event_date | DATE | Date de l'événement |
| image_id | BIGINT | ID de l'image (attachment) |
| access_key | VARCHAR(64) | Clé d'accès unique |
| password_hash | VARCHAR(255) | Hash du mot de passe |
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
- Les 3 tables de base de données
- Toutes les options (mrz_gle_*)
- Les capacités des rôles
- Les transients

**Non supprimé** : Les métadonnées des commandes WooCommerce (_mrz_gle_*)
