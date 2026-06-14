# SmartRH Maroc — Guide de démonstration

## Accès

### Administration

```
URL      : http://localhost/admin
Email    : admin@smartrh.test
Mot de passe : password
```

Rôles disponibles :
- Super Admin — accès complet
- Company Owner — gestion de la société
- Payroll Manager — paie
- RH Manager — RH et contrats

### Portail salarié

```
URL      : http://localhost/employee/login
Email    : amina.employee@smartrh.test
Mot de passe : password
```

---

## 1. Générer un bulletin de paie

1. Se connecter en tant que Super Admin ou Payroll Manager
2. Aller dans **Paie → Périodes de paie**
3. Créer une nouvelle période (ex: Juillet 2026)
4. Aller dans **Paie → Bulletins**
5. Cliquer **Créer un bulletin**
6. Sélectionner un employé (ex: Amina Bennani)
7. Vérifier les éléments de paie (salaire de base, primes, CNSS, AMO, IR)
8. Cliquer **Générer le PDF**
9. Le bulletin est disponible en téléchargement

## 2. Générer un contrat RH

1. Se connecter en tant que Super Admin, Company Owner ou RH Manager
2. Aller dans **RH → Contrats**
3. Cliquer **Créer un contrat**
4. Sélectionner :
   - Type : CDI, CDD ou Stage
   - Employé
   - Modèle de contrat
   - Salaire
   - Date de début
5. Cliquer **Générer le PDF**
6. Le contrat est disponible dans la liste et téléchargeable

## 3. Créer un employé

1. Aller dans **RH → Employés**
2. Cliquer **Créer un employé**
3. Remplir les informations :
   - Prénom, nom, email, téléphone
   - CIN, numéro CNSS
   - Département, poste
   - Salaire de base, date d'embauche
4. Cliquer **Enregistrer**
5. L'employé apparaît dans la liste et peut accéder au portail salarié

## 4. Demander une démo (côté public)

1. Aller sur la page d'accueil : `http://localhost/`
2. Cliquer **Demander une démo** (navbar ou section CTA)
3. Remplir le formulaire :
   - Nom complet
   - Société
   - Email
   - Téléphone
   - Taille de l'entreprise
   - Pack souhaité
   - Message
4. Cliquer **Envoyer ma demande**
5. Vous êtes redirigé vers la page de confirmation
6. Un email de confirmation est envoyé au prospect
7. Un email de notification est envoyé à l'équipe SmartRH

## 5. Convertir un lead en société

1. Se connecter en tant que Super Admin
2. Aller dans **Support → Demandes de démo**
3. La nouvelle demande apparaît avec le statut **Nouveau**
4. Cliquer **Marquer comme contacté** après avoir appelé le prospect
5. Cliquer **Planifier une démo** pour fixer un rendez-vous
6. Après la démo, cliquer **Convertir en société** pour :
   - Créer une société
   - Créer un abonnement d'essai Business (14 jours)
   - Créer un utilisateur administrateur
   - Envoyer un email de bienvenue
   - Envoyer un mail avec les identifiants de connexion
7. Le statut passe à **Converti**

## 6. Vérifier un abonnement

1. Aller dans **Abonnements → Abonnements**
2. La liste affiche :
   - Société abonnée
   - Plan (Starter, Business, Cabinet, Enterprise)
   - Statut (trialing, active, past_due, cancelled)
   - Période de facturation
3. Cliquer sur un abonnement pour voir les détails

## 7. Vérifier une facture

1. Aller dans **Abonnements → Factures**
2. La liste affiche :
   - Numéro de facture
   - Montant
   - Statut (pending, paid, overdue, cancelled)
   - Date d'émission
3. Télécharger le PDF de la facture si disponible

## 8. Créer un tenant de démonstration

```bash
php artisan smartrh:create-demo-tenant client@example.com
```

Cette commande :
- Crée une société (Demo Client)
- Crée un utilisateur admin (client@example.com / password)
- Assigne un rôle (company_admin)
- Crée un abonnement d'essai Business (14 jours)
- Crée 2 employés
- Génère un contrat
- Génère un bulletin de paie
- Crée un audit log

## 9. Portail salarié

1. Aller sur `http://localhost/employee/login`
2. Se connecter avec un compte employé
3. Fonctionnalités :
   - **Tableau de bord** : vue d'ensemble
   - **Bulletins** : consulter et télécharger ses bulletins de paie
   - **Contrats** : consulter et télécharger ses contrats
   - **Documents** : consulter ses documents et faire des demandes
   - **Congés** : demander un congé

## 10. Vérifier l'audit

1. Aller dans **Support → Audit logs**
2. Toutes les actions importantes sont journalisées :
   - Création d'employé
   - Modification de salaire
   - Génération de bulletin
   - Génération de contrat
   - Connexion / déconnexion
   - Conversion de lead
   - Changement d'abonnement

## Rappel

Les paramètres de paie, modèles de contrats et documents générés doivent être vérifiés par un expert-comptable marocain, juriste ou professionnel compétent avant utilisation officielle.
