# MRZ Gift Lists

Plugin WordPress/WooCommerce permettant aux clients de créer des listes de cadeaux (mariage, naissance, anniversaire, Noël...) et de les partager avec leurs proches.

## Table des matières

1. [Installation](#installation)
2. [Configuration initiale](#configuration-initiale)
3. [Administration (Back-office)](#administration-back-office)
   - [Menu principal](#menu-principal)
   - [Gestion des listes](#gestion-des-listes)
   - [Types de listes](#types-de-listes)
   - [Réglages](#réglages)
4. [Utilisation (Front-end)](#utilisation-front-end)
   - [Page d'accueil des listes](#page-daccueil-des-listes)
   - [Créer une liste](#créer-une-liste)
   - [Gérer sa liste](#gérer-sa-liste)
   - [Rechercher une liste](#rechercher-une-liste)
   - [Offrir un cadeau](#offrir-un-cadeau)
5. [Intégration WooCommerce](#intégration-woocommerce)
6. [Shortcodes](#shortcodes)
7. [Hooks et filtres](#hooks-et-filtres)
8. [FAQ](#faq)

---

## Installation

1. Téléchargez le dossier `mrz-gift-lists-exp` dans `/wp-content/plugins/`
2. Activez le plugin depuis le menu **Extensions** de WordPress
3. Assurez-vous que **WooCommerce** est installé et activé

À l'activation, le plugin :
- Crée les tables nécessaires dans la base de données
- Insère les types de listes par défaut (Mariage, Naissance, Anniversaire, Noël, Autre)
- Crée une page "Listes de cadeaux" avec le shortcode `[mrz_gift_lists]`

---

## Configuration initiale

Après l'activation, rendez-vous dans **Listes cadeaux > Réglages** pour configurer le plugin.

### Réglages recommandés

1. **Vérifiez la page des listes** : Assurez-vous qu'une page est bien sélectionnée
2. **Configurez l'email admin** : Pour recevoir les notifications
3. **Activez les notifications** : Pour que les créateurs soient informés des achats
4. **Choisissez les réseaux de partage** : Facebook, WhatsApp, Email, etc.

---

## Administration (Back-office)

### Menu principal

Le plugin ajoute un menu **Listes cadeaux** dans l'administration WordPress avec les sous-menus suivants :

| Menu | Description |
|------|-------------|
| Toutes les listes | Vue d'ensemble de toutes les listes créées |
| Types | Gestion des types de listes (mariage, naissance...) |
| Réglages | Configuration générale du plugin |

### Gestion des listes

#### Vue d'ensemble

La page principale affiche :
- **Statistiques** : Nombre total de listes, actives, archivées
- **Tableau des listes** avec colonnes :
  - Titre de la liste
  - Créateur (nom + email)
  - Type de liste
  - Nombre de produits (achetés / total)
  - Statut (Active, Archivée, Terminée)
  - Date de création

#### Filtres et recherche

- **Filtre par statut** : Active, Archivée, Terminée
- **Recherche** : Par titre, nom ou email du créateur

#### Actions sur une liste

| Action | Description |
|--------|-------------|
| Voir détails | Affiche toutes les informations de la liste |
| Voir sur le site | Ouvre la liste côté front-end |
| Archiver | Désactive la liste (réversible) |
| Réactiver | Remet une liste archivée en active |
| Supprimer | Supprime définitivement la liste et ses produits |

#### Page de détails d'une liste

Affiche :
- **Informations générales** : Créateur, dates, statut, clé d'accès, protection
- **Description** de la liste
- **Statistiques** : Produits total, disponibles, achetés
- **Liste des produits** avec pour chacun :
  - Image et nom du produit
  - Prix
  - Quantité (achetée / demandée)
  - Statut (Disponible, Réservé, Acheté)
  - Nom de l'acheteur et numéro de commande

### Types de listes

Les types permettent de catégoriser les listes avec :
- **Nom** : Ex: "Mariage", "Naissance"
- **Description** : Texte explicatif
- **Icône** : Emoji représentatif (❤️, 👶, 🎂, 🎁, etc.)
- **Couleur** : Code couleur pour l'affichage
- **Ordre** : Position dans les listes déroulantes
- **Actif** : Permet de masquer un type sans le supprimer

#### Types par défaut

| Type | Icône | Couleur |
|------|-------|---------|
| Mariage | ❤️ | #e91e63 (rose) |
| Naissance | 👶 | #03a9f4 (bleu) |
| Anniversaire | 🎂 | #ff9800 (orange) |
| Noël | 🎁 | #f44336 (rouge) |
| Autre | 📋 | #9e9e9e (gris) |

> **Note** : Un type utilisé par des listes ne peut pas être supprimé.

### Réglages

#### Réglages généraux

| Option | Description | Valeur par défaut |
|--------|-------------|-------------------|
| Page des listes | Page contenant le shortcode principal | Page créée à l'activation |
| Qui peut créer | Utilisateurs connectés ou clients WC uniquement | Utilisateurs connectés |
| Listes max par utilisateur | Limite de listes actives simultanées (0 = illimité) | 5 |

#### Affichage

| Option | Description | Valeur par défaut |
|--------|-------------|-------------------|
| Mode d'affichage | Grille ou Liste | Grille |
| Produits par page | Nombre de produits affichés | 12 |

#### Emails

| Option | Description | Valeur par défaut |
|--------|-------------|-------------------|
| Email administrateur | Reçoit les notifications de nouvelles listes et achats | Email admin du site |
| Notifier le créateur | Envoie un email quand un produit est acheté | Activé |

#### Partage

Sélectionnez les boutons de partage à afficher :
- Facebook
- Twitter/X
- Pinterest
- WhatsApp
- Email
- Copier le lien

---

## Utilisation (Front-end)

### Page d'accueil des listes

Accessible via la page créée à l'activation ou en ajoutant le shortcode `[mrz_gift_lists]` sur n'importe quelle page.

**Affiche :**
- Titre et description d'introduction
- Boutons d'action :
  - "Mes listes" (si connecté)
  - "Créer une liste" (si autorisé)
  - "Rechercher une liste"
- Listes récentes (6 dernières)

### Créer une liste

**Prérequis** : Être connecté à son compte WordPress/WooCommerce

**Étapes :**

1. Cliquer sur **"Créer une liste"**
2. Remplir le formulaire :
   - **Titre** (obligatoire) : Ex: "Liste de mariage de Marie et Jean"
   - **Type** : Choisir parmi les types disponibles
   - **Date de l'événement** : Date optionnelle
   - **Description** : Texte libre pour décrire l'événement
   - **Image** : Photo d'illustration (JPG/PNG, max 2 Mo)
   - **Mot de passe** : Protection optionnelle de la liste
3. Cliquer sur **"Créer la liste"**

**Résultat** : Redirection vers la nouvelle liste avec une clé d'accès unique générée automatiquement.

### Gérer sa liste

Le créateur de la liste a accès à des fonctionnalités supplémentaires :

#### Ajouter des produits

1. Cliquer sur **"+ Ajouter un produit"**
2. Rechercher un produit par son nom dans le champ de recherche
3. Cliquer sur le bouton **"+"** à côté du produit souhaité
4. Le produit est ajouté instantanément à la liste

#### Actions sur les produits

| Action | Icône | Description |
|--------|-------|-------------|
| Prioritaire | ⭐/☆ | Marque le produit comme prioritaire (affiché en premier) |
| Supprimer | ✕ | Retire le produit de la liste |

#### Partager sa liste

Les boutons de partage permettent de :
- Partager sur Facebook
- Partager sur Twitter/X
- Envoyer par WhatsApp
- Envoyer par email
- Copier le lien dans le presse-papiers

**URL de la liste** : `https://votre-site.com/mrz-gift-lists/?mrz_gle_list_key=XXXXXX`

### Rechercher une liste

1. Cliquer sur **"Rechercher une liste"**
2. Saisir un terme de recherche :
   - Nom du créateur
   - Email du créateur
   - Titre de la liste
3. Les résultats s'affichent avec :
   - Titre de la liste
   - Type
   - Nombre de produits
   - Indicateur de protection 🔒

### Offrir un cadeau

**Pour un visiteur souhaitant offrir un cadeau :**

1. Accéder à une liste (via recherche ou lien partagé)
2. Si la liste est protégée, saisir le mot de passe
3. Parcourir les produits disponibles
4. Cliquer sur **"Offrir"** sur le produit choisi
5. Le produit est ajouté au panier WooCommerce
6. Procéder au paiement normalement
7. Optionnel : Laisser un message pour le créateur au moment du checkout

**Après l'achat :**
- Le produit est automatiquement marqué comme "Acheté"
- Le nom de l'acheteur apparaît sur la liste
- Le créateur reçoit une notification par email (si activé)

---

## Intégration WooCommerce

### Mon compte

Un nouvel onglet **"Mes listes de cadeaux"** est ajouté au menu "Mon compte" de WooCommerce, entre "Commandes" et "Déconnexion".

### Page produit

Sur chaque page produit, un bouton **"+ Ajouter à une liste"** apparaît pour les utilisateurs connectés ayant au moins une liste active.

### Panier

Les produits ajoutés depuis une liste affichent le nom de la liste associée.

### Commande

#### Champ personnalisé

Si le panier contient des produits de liste, un champ **"Message pour le créateur"** apparaît au checkout.

#### Colonne admin

Une colonne **"Liste cadeaux"** est ajoutée au tableau des commandes dans l'administration, indiquant si la commande contient des produits de liste.

#### Détail commande

Le message cadeau (si renseigné) est affiché dans les détails de la commande côté admin.

---

## Shortcodes

### [mrz_gift_lists]

Shortcode principal affichant l'interface complète des listes.

**Paramètres :**

| Paramètre | Description | Valeurs |
|-----------|-------------|---------|
| view | Force une vue spécifique | `my-lists`, `create`, `search` |

**Exemples :**

```
[mrz_gift_lists]                    // Interface complète
[mrz_gift_lists view="my-lists"]    // Affiche directement "Mes listes"
[mrz_gift_lists view="search"]      // Affiche directement la recherche
```

### [mrz_gift_lists_search]

Widget de recherche compact pour intégrer dans une sidebar ou un footer.

**Paramètres :**

| Paramètre | Description | Valeur par défaut |
|-----------|-------------|-------------------|
| placeholder | Texte du champ de recherche | "Rechercher par nom ou email..." |
| button_text | Texte du bouton | "Rechercher" |

**Exemple :**

```
[mrz_gift_lists_search placeholder="Trouver une liste..." button_text="Chercher"]
```

---

## Hooks et filtres

### Actions

| Action | Description | Paramètres |
|--------|-------------|------------|
| `mrz_gle_list_created` | Déclenchée après la création d'une liste | `$list_id` |
| `mrz_gle_item_purchased` | Déclenchée après l'achat d'un produit | `$item_id`, `$list_id`, `$order_id`, `$buyer_name` |

**Exemple d'utilisation :**

```php
add_action( 'mrz_gle_list_created', function( $list_id ) {
    // Votre code personnalisé
    error_log( 'Nouvelle liste créée : ' . $list_id );
} );
```

### Filtres

Les données sont accessibles via les repositories :

```php
// Récupérer une liste
$list_repo = new \MrzGiftsListsExp\Repositories\ListRepository();
$list = $list_repo->find( $list_id );
$list = $list_repo->find_by_access_key( 'abc123' );

// Récupérer les listes d'un utilisateur
$lists = $list_repo->find_by_user( $user_id );

// Récupérer les produits d'une liste
$item_repo = new \MrzGiftsListsExp\Repositories\ItemRepository();
$items = $item_repo->find_by_list( $list_id );

// Statistiques d'une liste
$stats = $item_repo->get_list_stats( $list_id );
// $stats->total_items, $stats->purchased_items, etc.
```

---

## FAQ

### Comment changer l'URL de la page des listes ?

1. Créez une nouvelle page avec le shortcode `[mrz_gift_lists]`
2. Allez dans **Listes cadeaux > Réglages**
3. Sélectionnez la nouvelle page dans "Page des listes"

### Comment personnaliser l'apparence ?

Le plugin utilise des classes CSS préfixées par `mrz-gle-`. Vous pouvez surcharger les styles dans votre thème :

```css
/* Exemple : changer la couleur des boutons */
.mrz-gle-btn-primary {
    background-color: #your-color;
}

/* Exemple : modifier la grille de produits */
.mrz-gle-products-grid {
    grid-template-columns: repeat(4, 1fr);
}
```

### Comment limiter les produits qu'on peut ajouter à une liste ?

Actuellement, tous les produits publiés de WooCommerce sont disponibles. Pour filtrer, vous pouvez modifier la recherche AJAX via le hook WordPress :

```php
add_filter( 'woocommerce_product_data_store_cpt_get_products_query', function( $query, $query_vars ) {
    // Votre logique de filtrage
    return $query;
}, 10, 2 );
```

### Les emails ne sont pas envoyés, que faire ?

1. Vérifiez que l'email admin est configuré dans les réglages
2. Vérifiez que "Notifier le créateur" est activé
3. Testez l'envoi d'email WordPress avec un plugin comme "Check & Log Email"
4. Si votre hébergeur bloque les emails, utilisez un plugin SMTP

### Comment traduire le plugin ?

Le plugin est prêt pour la traduction. Le domaine de texte est `mrz-gift-lists-exp`. Utilisez un plugin comme Loco Translate ou créez vos fichiers `.po/.mo` dans le dossier `/languages/`.

### Comment supprimer toutes les données du plugin ?

La suppression du plugin via l'admin WordPress supprime automatiquement :
- Les 3 tables de base de données
- Toutes les options du plugin
- Les capabilities ajoutées aux rôles

> **Note** : Les métadonnées ajoutées aux commandes WooCommerce sont conservées par défaut.

---

## Support

Pour signaler un bug ou demander une fonctionnalité, créez une issue sur le dépôt GitHub du projet.

---

## Changelog

### Version 1.0.0
- Version initiale
- Création et gestion des listes de cadeaux
- Types de listes personnalisables
- Intégration complète WooCommerce
- Système de partage social
- Protection par mot de passe
- Notifications par email
- Interface d'administration complète
