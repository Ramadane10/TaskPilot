# TaskPilot - Application de Gestion de Projets

## 📋 Description

TaskPilot est une application web moderne de gestion de projets développée avec Symfony 7. Elle permet aux équipes de gérer leurs projets, tâches et collaborateurs de manière efficace et intuitive.

## ✨ Fonctionnalités

### 🔐 Système d'Authentification
- **Inscription** : Création de compte utilisateur avec validation
- **Connexion** : Authentification sécurisée avec sessions
- **Rôles** : Système de rôles (ROLE_USER, ROLE_ADMIN)
- **Déconnexion** : Gestion sécurisée des sessions

### 📊 Gestion des Projets
- **CRUD complet** : Création, lecture, modification, suppression
- **Restrictions admin** : Seuls les administrateurs peuvent créer/modifier/supprimer des projets
- **Statuts** : Actif, Terminé, Archivé
- **Membres** : Attribution d'utilisateurs aux projets
- **Affichage conditionnel** : Interface adaptée selon les permissions

### ✅ Gestion des Tâches
- **CRUD complet** : Gestion complète des tâches
- **Statuts** : À faire, En cours, En révision, Terminé
- **Priorités** : Faible, Moyenne, Élevée, Urgente
- **Assignation** : Attribution des tâches aux utilisateurs
- **Mise à jour métier** : Changement de statut en temps réel
- **Échéances** : Gestion des dates limites

### 👥 Gestion des Utilisateurs
- **Profils complets** : Prénom, nom, email, statut actif
- **Relations** : Liens avec projets et tâches
- **Affichage personnalisé** : Chaque utilisateur voit ses projets et tâches

### 🎨 Interface Utilisateur
- **Design moderne** : Interface Bootstrap 5 responsive
- **Navigation intuitive** : Menu de navigation clair
- **Dashboard** : Vue d'ensemble avec statistiques
- **Messages flash** : Notifications utilisateur
- **Icônes Font Awesome** : Interface visuelle attractive

## 🏗️ Architecture

### Entités (4 entités)
1. **User** : Utilisateurs avec authentification
2. **Project** : Projets avec membres et tâches
3. **Task** : Tâches avec assignation et statuts
4. **Comment** : Commentaires sur les tâches

### Relations
- **Project ↔ Task** : OneToMany (un projet peut avoir plusieurs tâches)
- **Project ↔ User** : ManyToMany (un projet peut avoir plusieurs membres)
- **Task ↔ User** : ManyToOne (une tâche peut être assignée à un utilisateur)
- **Task ↔ Comment** : OneToMany (une tâche peut avoir plusieurs commentaires)
- **Comment ↔ User** : ManyToOne (un commentaire a un auteur)

## 🛠️ Technologies Utilisées

- **Backend** : Symfony 7
- **Base de données** : MySQL 8.0
- **ORM** : Doctrine
- **Frontend** : Bootstrap 5, Font Awesome
- **Templates** : Twig
- **Sécurité** : Symfony Security Bundle

## 📦 Installation

### Prérequis
- PHP 8.1+
- Composer
- MySQL 8.0+
- Symfony CLI (optionnel)

### Étapes d'installation

1. **Cloner le projet**
```bash
git clone https://github.com/Ramadane10/TaskPilot
cd TaskPilot
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configurer la base de données**
   - Créer une base de données MySQL
   - Modifier le fichier `.env` avec vos paramètres de connexion :
   ```
   DATABASE_URL="mysql://root@127.0.0.1:3306/taskpilot_db?serverVersion=8.0.31&charset=utf8mb4"
   ```

4. **Créer les tables**
```bash
php bin/console doctrine:migrations:migrate
```

5. **Lancer le serveur**
```bash
php -S localhost:8000 -t public
```

6. **Accéder à l'application**
   - Ouvrir http://localhost:8000
   - Créer un compte administrateur


### Workflow typique
1. **Créer un projet** (admin seulement)
2. **Ajouter des membres** au projet
3. **Créer des tâches** et les assigner
4. **Suivre la progression** via le dashboard
5. **Mettre à jour les statuts** des tâches

## 🔒 Sécurité

- **Authentification** : Formulaire de connexion sécurisé
- **Autorisation** : Contrôle d'accès basé sur les rôles
- **CSRF Protection** : Protection contre les attaques CSRF
- **Validation** : Validation des données côté serveur
- **Hachage** : Mots de passe hachés avec bcrypt

## 📱 Responsive Design

L'application est entièrement responsive et s'adapte à tous les écrans :
- **Desktop** : Interface complète avec navigation latérale
- **Tablet** : Adaptation des grilles et menus
- **Mobile** : Navigation hamburger et cartes empilées



## 🚀 Améliorations Futures

- [ ] Système de notifications
- [ ] Export de données (PDF, Excel)
- [ ] API REST
- [ ] Système de tags pour les tâches
- [ ] Gantt chart pour les projets
- [ ] Intégration email
- [ ] Mode sombre
- [ ] Recherche avancée

## 📄 Licence

Ce projet est développé dans le cadre d'un exercice académique.

## 👨‍💻 Auteur

Développé avec Symfony 7 - Application de gestion de projets moderne et intuitive. 
