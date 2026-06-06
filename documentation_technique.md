# 📘 Documentation Technique Complète - ImmoPro

> *"Même si vous débutez dans le code, ce guide vous expliquera comment fonctionne le moteur sous le capot d'ImmoPro."*

Cette documentation explique comment l'application ImmoPro est construite, comment l'installer, et comment fonctionnent ses mécanismes internes. L'application existe en **deux versions** (Symfony et Laravel), et nous couvrirons les deux ici.

---

## 🛠️ 1. Qu'y a-t-il sous le capot ? (La Stack Technique)

ImmoPro est comme une voiture, construite avec plusieurs pièces différentes :

* **Le Moteur (Langage)** : **PHP 8.x**. C'est le langage qui fait tourner toute la logique du site.
* **Le Châssis (Framework)** : **Symfony 7** (Option A) ou **Laravel 11** (Option B). Ce sont des "boîtes à outils" qui évitent de réinventer la roue pour coder en PHP.
* **Le Coffre-fort (Base de données)** : **MariaDB / MySQL**. C'est là que sont stockés tous les utilisateurs, les biens et les transactions.
* **La Carrosserie (Design)** : **Tailwind CSS**. C'est l'outil qui permet de peindre le site, d'arrondir les boutons et de placer les images pour que tout soit beau.
* **L'Imprimante (Générateur PDF)** : **DomPDF**. Une bibliothèque qui transforme du code HTML en véritables fichiers PDF (pour les contrats).

---

## 🚀 2. Comment installer le projet sur votre ordinateur ?

Pour travailler sur le projet, vous devez le faire tourner "en local" sur votre ordinateur. 

### Prérequis (Les outils à avoir sur votre PC)
1. **WAMP, XAMPP ou Laragon** : Ce sont des logiciels qui transforment votre PC en petit serveur.
2. **Composer** : Un logiciel qui télécharge automatiquement les bibliothèques PHP dont on a besoin.
3. **Node.js** : Un logiciel qui gère le design (Tailwind CSS).

### Option A : Installation de la version Symfony
1. **Télécharger le code** : `git clone https://github.com/JoeCoolCapelo/ImmoPro_Symfony.git`
2. **Télécharger les bibliothèques** : Tapez `composer install` dans votre terminal.
3. **Connecter la base de données** : 
   - Créez un fichier `.env.local` (en copiant le `.env`).
   - Modifiez la ligne `DATABASE_URL` pour y mettre vos identifiants WAMP.
4. **Créer les tables et les données de test** :
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```
5. **Allumer le serveur** : `symfony server:start`. Le site sera visible sur `http://localhost:8000`.

### Option B : Installation de la version Laravel
1. **Télécharger le code** : `git clone https://github.com/JoeCoolCapelo/ImmoPro_laravel.git`
2. **Télécharger les bibliothèques** : Tapez `composer install` et `npm install`.
3. **Connecter la base de données** : 
   - Copiez le fichier `.env.example` et renommez-le en `.env`.
   - Modifiez `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
   - Tapez `php artisan key:generate`.
4. **Créer les tables** : `php artisan migrate`.
5. **Allumer le serveur** : Tapez `php artisan serve` et dans un autre terminal `npm run dev`.

---

## 🏗️ 3. Comment est rangé le code ? (Architecture MVC)

Le code respecte une règle stricte appelée **MVC (Modèle - Vue - Contrôleur)**. C'est comme un restaurant :

1. **Le Serveur (Contrôleur)** : Il prend la commande du client (le clic sur un lien). 
   - *Dans Symfony* : Dossier `src/Controller`
   - *Dans Laravel* : Dossier `app/Http/Controllers`
2. **Le Cuisinier (Modèle)** : Il va chercher les ingrédients dans la base de données (les informations du Bien immobilier).
   - *Dans Symfony* : Dossier `src/Entity`
   - *Dans Laravel* : Dossier `app/Models`
3. **L'Assiette (Vue)** : C'est ce qui est présenté au client (la page HTML finale).
   - *Dans Symfony* : Dossier `templates/` (Fichiers `.twig`)
   - *Dans Laravel* : Dossier `resources/views/` (Fichiers `.blade.php`)

---

## ⚙️ 4. Fonctionnalités Avancées (Comment ça marche ?)

Voici l'explication des "pouvoirs magiques" de l'application :

### 📧 L'envoi d'Emails (SMTP)
Quand un utilisateur s'inscrit, il reçoit un email.
* **Techniquement** : L'application utilise `Symfony Mailer` (ou `Laravel Mail`). 
* **Où modifier ?** : Les identifiants de la boîte mail qui envoie les messages se trouvent dans le fichier `.env` à la ligne `MAILER_DSN` (Symfony) ou `MAIL_MAILER` (Laravel).

### 📄 La génération de Contrats PDF
Quand une transaction est validée, le système crée un contrat PDF.
* **Techniquement** : Nous utilisons un outil appelé `DomPDF`. L'application dessine d'abord une page HTML classique (avec le nom du client et le montant), puis demande à DomPDF de "prendre une photo" de cette page et de la transformer en vrai fichier PDF.

### ✍️ La Signature Électronique
L'application permet de signer un document sans papier.
* **Techniquement** : Ce n'est pas une "vraie" signature dessinée à la main qui a une valeur légale, c'est une empreinte numérique.
Quand l'utilisateur clique sur "Signer", le système enregistre 3 choses dans la base de données :
  1. Un `booléen` (vrai/faux) qui passe à VRAI (`is_signed = true`).
  2. L'heure exacte du clic (`signed_at`).
  3. L'Adresse IP de l'ordinateur qui a cliqué (pour prouver d'où vient le clic).

### ⏱️ Les Tâches Automatiques (Crons)
Parfois, le site doit travailler tout seul, la nuit (ex: envoyer un rappel de loyer impayé).
* **Techniquement** : On utilise le "Planificateur de tâches" de l'ordinateur serveur (le CRON). 
  - *Symfony* : On crée des `Commands` dans `src/Command`.
  - *Laravel* : On crée des tâches dans `app/Console/Kernel.php` et on dit au serveur d'exécuter `php artisan schedule:run` toutes les minutes.

### 🧪 Les Tests Automatiques (Assurance Qualité)
Avant de mettre à jour le site, on veut être sûr de ne rien avoir cassé.
* **Techniquement** : On utilise `PHPUnit`. Ce sont des petits robots (des scripts de code) qui vont faire semblant de cliquer sur le site pour vérifier que "2+2 fait toujours 4". Si on lance la commande de test et que tout est vert, on peut déployer sereinement.

---

## 🌍 5. Mettre le site en ligne (Déploiement)

Mettre le site sur Internet, c'est le "Déploiement".

1. On cache les erreurs de développement (`APP_ENV=prod`).
2. On compacte le design CSS pour qu'il soit plus léger à charger.
3. On vide le "Cache" (la mémoire à court terme du site) pour que les nouveautés apparaissent : 
   - *Symfony* : `php bin/console cache:clear`
   - *Laravel* : `php artisan optimize:clear`
4. On s'assure que le dossier qui stocke les images (`public/uploads/` ou `storage/`) autorise les internautes à écrire dedans.
