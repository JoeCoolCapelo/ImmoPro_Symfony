# Guide d'Administration du Site - ImmoPro (Versions Symfony & Laravel)

Bienvenue dans le guide d'administration d'ImmoPro. Ce document est destiné aux administrateurs de la plateforme (`ROLE_ADMIN` dans Symfony, ou rôle `admin` dans Laravel) pour les aider à gérer le site et l'agence immobilière au quotidien.

Ce guide est valable pour les deux déclinaisons du projet :
* **Dépôt Symfony** : https://github.com/JoeCoolCapelo/ImmoPro_Symfony.git
* **Dépôt Laravel** : https://github.com/JoeCoolCapelo/ImmoPro_laravel.git

---

## 1. Identifiants de Connexion (Environnement de Test)
Si vous venez d'installer le projet et de charger les données fictives (fixtures/seeders), des comptes administrateurs par défaut ont été créés pour vous permettre d'accéder au panel de gestion.

* **Version Symfony** : `admin@immopro.gn` / Mot de passe : `password`
* **Version Laravel** : `admin@immopro.com` / Mot de passe : `password`
* **Lien de connexion** : `/login`

Une fois connecté avec ces identifiants, un menu déroulant **"Admin"** apparaîtra dans la barre de navigation.

## 2. Gestion des Utilisateurs
L'onglet **Utilisateurs** vous permet de gérer les différents acteurs de la plateforme :
* **Clients** : Chercheurs de biens ou locataires.
* **Propriétaires** : Propriétaires des biens mis en gestion.
* **Agents** : Les employés de votre agence qui gèrent le catalogue et le CRM.
* **Administrateurs** : Les gérants de l'agence.

**Actions possibles :**
- Créer un nouvel utilisateur (très utile pour inscrire vos agents manuellement).
- Modifier les rôles (promouvoir un client en propriétaire ou agent).
- Désactiver un compte en cas de litige.

## 3. Gestion du Catalogue (Biens)
L'onglet **Biens** permet de superviser l'ensemble du parc immobilier. Bien que les agents gèrent les biens au quotidien, l'administrateur peut :
- Valider la publication d'un bien (passer de "en attente" à "publié").
- Suspendre ou retirer un bien du catalogue public.
- Assigner ou réassigner un bien à un Agent spécifique ou changer le Propriétaire.

## 4. Pipeline CRM et Transactions
En tant qu'administrateur, vous avez une vue globale sur les performances de l'agence :
- **CRM (Leads)** : Vous pouvez voir toutes les visites en cours, les négociations, et les transactions gagnées de tous les agents.
- **Transactions** : Vous pouvez suivre l'état des signatures électroniques (Client, Propriétaire, Agence) pour les contrats de location et de vente.
- **Validation** : Dans certains cas, c'est l'administrateur qui appose la signature finale de l'agence (`agencySigned`).

## 5. Suivi Financier
* **Dépenses (Entretien)** : Enregistrez les frais de fonctionnement de l'agence ou les réparations effectuées sur les biens en gestion.
* **Rapports & Stats** : Analysez les revenus générés par les commissions, les loyers perçus, et les performances globales de l'agence.

## 6. Paramètres de l'Agence
L'onglet **Paramètres** est exclusif aux administrateurs. Il permet de configurer :
- **Identité de l'agence** : Nom, Logo, Adresse physique, Téléphone, Email de contact.
- **Finances** : Taux de commission par défaut pour les ventes, taux pour les locations, devise (GNF, EUR, etc.).
- **TVA** : Taux de taxe applicable.

> [!WARNING]
> Toute modification des taux de commission dans les paramètres s'appliquera par défaut aux **nouvelles transactions**, mais n'affectera pas les contrats déjà signés.
