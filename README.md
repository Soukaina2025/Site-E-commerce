# 🛍️ Plateforme E-commerce Multi-Boutiques

## 📌 Présentation du projet

**Boutique** est une plateforme web e-commerce **multi-boutiques** développée sur demande d'un client.

L'objectif principal de la plateforme est de permettre la **création et la gestion de plusieurs boutiques depuis une seule application**, tout en conservant une organisation indépendante des produits, des clients et des commandes de chaque boutique.

La solution permet au gestionnaire de créer plusieurs boutiques et de gérer leur activité à partir d'une interface centralisée.

La plateforme intègre également un espace client permettant de consulter les produits, gérer un panier, effectuer des commandes et consulter les commandes précédentes.

---

## 🎯 Objectifs

La plateforme a été conçue pour répondre à plusieurs besoins :

* Centraliser la gestion de plusieurs boutiques.
* Permettre la création de nouvelles boutiques.
* Gérer les produits de chaque boutique.
* Gérer les clients et leurs commandes.
* Permettre aux clients de consulter les produits disponibles.
* Permettre l'ajout de produits au panier.
* Permettre la création et le suivi des commandes.
* Générer des reçus de commande au format PDF.
* Fournir une interface d'administration pour gérer l'activité de la plateforme.

---

# 🏪 Concept Multi-Boutiques

L'une des principales fonctionnalités du projet est la possibilité de **créer plusieurs boutiques sur une même plateforme**.

Par exemple :

```text
                    Plateforme E-commerce
                            │
             ┌──────────────┼──────────────┐
             │              │              │
             ▼              ▼              ▼
        Boutique 1     Boutique 2     Boutique 3
             │              │              │
        ┌────┼────┐    ┌────┼────┐    ┌────┼────┐
        │    │    │    │    │    │    │    │    │
      Produits Clients Commandes ...
```

Chaque boutique peut disposer de ses propres :

* Produits
* Clients
* Commandes
* Informations
* Paramètres

Cela permet de gérer plusieurs activités commerciales à partir d'un même système.

---

# ✨ Fonctionnalités principales

## 🏪 1. Création des boutiques

La plateforme permet de créer plusieurs boutiques.

La création d'une boutique permet notamment de définir les informations nécessaires à son fonctionnement.

Cette fonctionnalité constitue la base du système multi-boutiques.

### Fonctionnalités :

* Création d'une nouvelle boutique
* Gestion des informations de la boutique
* Configuration de la boutique
* Gestion indépendante des données de chaque boutique

---

# 📦 2. Gestion des produits

Chaque boutique peut gérer son catalogue de produits.

### Fonctionnalités :

* Ajouter un produit
* Modifier un produit
* Supprimer un produit
* Consulter les produits
* Afficher les détails d'un produit
* Ajouter des images aux produits
* Organiser les produits par boutique

Les images des produits sont stockées dans le dossier :

```text
uploads/
```

---

# 🛒 3. Panier

Les clients peuvent ajouter des produits à leur panier avant de passer une commande.

### Fonctionnalités :

* Ajouter un produit au panier
* Consulter le panier
* Modifier le contenu du panier
* Supprimer un produit du panier
* Préparer une commande à partir du panier

---

# 📋 4. Gestion des commandes

La plateforme permet de gérer les commandes effectuées par les clients.

### Côté client :

* Passer une commande
* Consulter ses commandes
* Consulter les informations d'une commande
* Télécharger un reçu

### Côté administration :

* Consulter les commandes
* Suivre les commandes
* Gérer les commandes des boutiques

---

# 👤 5. Gestion des clients

La plateforme permet également de gérer les utilisateurs et les clients.

Les informations relatives aux clients peuvent être utilisées pour assurer le suivi des commandes et de l'activité commerciale.

---

# 🔐 6. Authentification

La plateforme dispose d'un système d'accès permettant de différencier les fonctionnalités accessibles à l'administrateur et aux clients.

### Administrateur

L'administrateur dispose d'un espace dédié permettant de gérer la plateforme.

### Client

Le client peut accéder aux fonctionnalités liées à la consultation des produits, au panier et aux commandes.

---

# 👨‍💼 7. Espace Administrateur

L'espace administrateur constitue l'interface principale de gestion de la plateforme.

Il permet notamment de gérer :

* Les boutiques
* Les produits
* Les clients
* Les commandes
* Le profil administrateur
* Les paramètres de la boutique

### Pages principales

```text
admin.php
admin_login.php
admin_profile.php
admin_parameters.php
admin_produits.php
clients.php
commandes.php
```

---

# 🧾 8. Génération des reçus PDF

La plateforme permet de générer des documents PDF liés aux commandes.

Le projet utilise la bibliothèque **TCPDF** pour la génération des documents.

Les fonctionnalités liées aux PDF comprennent notamment :

```text
generate_pdf.php
download_receipt.php
```

La bibliothèque utilisée est présente dans :

```text
tcpdf/
```

---

# 🖥️ Technologies utilisées

## Backend

* **PHP**

PHP est utilisé pour développer la logique serveur et gérer les différentes fonctionnalités de l'application.

## Frontend

* **HTML5**
* **CSS3**
* **JavaScript**

Ces technologies sont utilisées pour construire l'interface utilisateur et gérer les interactions côté client.

## Base de données

* **MySQL**

MySQL est utilisé pour stocker les données liées aux boutiques, produits, clients et commandes.

## Serveur local

* **XAMPP**

XAMPP permet d'exécuter localement :

* Apache
* PHP
* MySQL

## Génération PDF

* **TCPDF**

TCPDF est utilisée pour générer les reçus et documents PDF.

## Versioning

* **Git**
* **GitHub**

Git est utilisé pour gérer les versions du projet et GitHub pour héberger le code source.

---

# 📂 Structure du projet

```text
boutique/
│
├── acceuil.php
├── index.php
├── config.php
├── create_boutique.php
│
├── admin.php
├── admin_login.php
├── admin_profile.php
├── admin_parameters.php
├── admin_produits.php
│
├── ajouter_produit.php
├── modifier_produit.php
├── supprimer_produit.php
│
├── clients.php
├── commandes.php
├── mes_commandes.php
│
├── detail.php
├── panier.php
├── logout.php
│
├── generate_pdf.php
├── download_receipt.php
│
├── boutique (1).sql
│
├── tcpdf/
│   └── ...
│
└── uploads/
    └── ...
```

---

# 📄 Description des principaux fichiers

| Fichier                 | Description                                        |
| ----------------------- | -------------------------------------------------- |
| `index.php`             | Point d'entrée de l'application                    |
| `acceuil.php`           | Page d'accueil                                     |
| `config.php`            | Configuration de la connexion à la base de données |
| `create_boutique.php`   | Création d'une boutique                            |
| `admin.php`             | Interface principale de l'administration           |
| `admin_login.php`       | Connexion administrateur                           |
| `admin_profile.php`     | Gestion du profil administrateur                   |
| `admin_parameters.php`  | Paramètres de la boutique                          |
| `admin_produits.php`    | Gestion des produits                               |
| `ajouter_produit.php`   | Ajout d'un produit                                 |
| `modifier_produit.php`  | Modification d'un produit                          |
| `supprimer_produit.php` | Suppression d'un produit                           |
| `clients.php`           | Gestion des clients                                |
| `commandes.php`         | Gestion des commandes                              |
| `mes_commandes.php`     | Consultation des commandes du client               |
| `detail.php`            | Détails d'un produit                               |
| `panier.php`            | Gestion du panier                                  |
| `logout.php`            | Déconnexion                                        |
| `generate_pdf.php`      | Génération de documents PDF                        |
| `download_receipt.php`  | Téléchargement du reçu                             |
| `boutique (1).sql`      | Structure et données de la base de données         |
| `tcpdf/`                | Bibliothèque de génération PDF                     |
| `uploads/`              | Stockage des images                                |

---

# 🗄️ Base de données

Le projet utilise une base de données **MySQL**.

Le fichier SQL fourni avec le projet est :

```text
boutique (1).sql
```

Ce fichier peut être importé dans **phpMyAdmin** afin de créer la structure nécessaire au fonctionnement de l'application.

Les données de la plateforme concernent notamment :

* Les boutiques
* Les produits
* Les clients
* Les commandes
* Les informations nécessaires au fonctionnement de la plateforme

---

# ⚙️ Installation

## 1. Prérequis

Avant d'installer le projet, il est nécessaire d'avoir :

* XAMPP
* PHP
* MySQL
* Apache
* Un navigateur web
* Git (optionnel pour cloner le projet)

---

## 2. Télécharger le projet

Cloner le repository GitHub :

```bash
git clone URL_DU_REPOSITORY
```

Puis accéder au dossier :

```bash
cd boutique
```

---

## 3. Placer le projet dans XAMPP

Copier le dossier du projet dans :

```text
C:\xampp\htdocs\
```

Le chemin final doit être similaire à :

```text
C:\xampp\htdocs\boutique
```

---

## 4. Démarrer XAMPP

Ouvrir **XAMPP Control Panel**.

Démarrer :

```text
Apache
MySQL
```

Les deux services doivent être actifs.

---

# 🗄️ Configuration de la base de données

## 1. Ouvrir phpMyAdmin

Dans le navigateur :

```text
http://localhost/phpmyadmin
```

## 2. Créer une base de données

Créer une base de données correspondant à la configuration utilisée dans `config.php`.

Exemple :

```text
boutique
```

## 3. Importer la base de données

Dans phpMyAdmin :

```text
Importer
    ↓
Choisir un fichier
    ↓
boutique (1).sql
    ↓
Exécuter
```

---

# 🔧 Configuration de la connexion

Ouvrir :

```text
config.php
```

Puis vérifier les informations de connexion à MySQL.

Exemple :

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "boutique";
```

Les valeurs doivent correspondre à la configuration de votre environnement XAMPP.

---

# 🚀 Lancement de l'application

Une fois Apache et MySQL démarrés, ouvrir :

```text
http://localhost/boutique/
```

L'application devrait alors être accessible depuis le navigateur.

---

# 🔄 Flux général de l'application

Le fonctionnement général peut être représenté comme suit :

```text
                    Utilisateur
                        │
                        ▼
                  Plateforme Web
                        │
             ┌──────────┴──────────┐
             │                     │
             ▼                     ▼
          Client              Administrateur
             │                     │
             ▼                     ▼
        Produits              Boutiques
             │                 Produits
             ▼                 Clients
           Panier              Commandes
             │                 Paramètres
             ▼
         Commande
             │
             ▼
       Reçu / PDF
```

---

# 🔐 Gestion des accès

La plateforme distingue les fonctionnalités selon le type d'utilisateur.

| Fonctionnalité            | Client | Administrateur |
| ------------------------- | :----: | :------------: |
| Consulter les produits    |    ✅   |        ✅       |
| Consulter les détails     |    ✅   |        ✅       |
| Ajouter au panier         |    ✅   |        ❌       |
| Passer une commande       |    ✅   |        ❌       |
| Consulter ses commandes   |    ✅   |        ❌       |
| Gérer les produits        |    ❌   |        ✅       |
| Ajouter un produit        |    ❌   |        ✅       |
| Modifier un produit       |    ❌   |        ✅       |
| Supprimer un produit      |    ❌   |        ✅       |
| Gérer les clients         |    ❌   |        ✅       |
| Gérer les commandes       |    ❌   |        ✅       |
| Gérer les paramètres      |    ❌   |        ✅       |
| Gérer les boutiques       |    ❌   |        ✅       |
| Générer des documents PDF |    ❌   |        ✅       |

---

# 🏗️ Architecture générale

L'application suit une organisation basée sur plusieurs pages PHP spécialisées.

```text
Interface utilisateur
        │
        ▼
     PHP Pages
        │
        ▼
 Business Logic
        │
        ▼
     MySQL
        │
        ▼
 Application Data
```

Les différentes pages PHP communiquent avec la base de données afin de récupérer, créer, modifier ou supprimer les informations nécessaires.

---

# 📸 Gestion des images

Les images utilisées pour les produits sont stockées dans le dossier :

```text
uploads/
```

Lorsqu'un produit est ajouté avec une image, celle-ci peut être enregistrée dans ce dossier afin d'être utilisée dans l'interface de la boutique.

---

# 📊 Gestion indépendante des boutiques

Le système multi-boutiques permet d'éviter de mélanger les informations entre les différentes boutiques.

Chaque boutique peut être considérée comme un espace commercial indépendant au sein de la même plateforme.

Exemple :

```text
Boutique A
├── Produits
├── Clients
└── Commandes

Boutique B
├── Produits
├── Clients
└── Commandes

Boutique C
├── Produits
├── Clients
└── Commandes
```

Cette organisation permet au gestionnaire de développer plusieurs activités commerciales sans avoir besoin de créer une application différente pour chaque boutique.

---



# 📜 Licence

Ce projet a été développé pour répondre aux besoins spécifiques du client.

L'utilisation, la modification ou la redistribution du code doit respecter les conditions convenues avec le propriétaire du projet.
