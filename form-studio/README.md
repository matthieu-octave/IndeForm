# Form’Studio

Espace de préparation pédagogique de la solution **Indé’Form** : construire un cours, retrouver ses supports et l’adapter d’un groupe à l’autre.

## Fonctionnalités

- [x] Bibliothèque de modules (intitulé, référence facultative, objectifs, prérequis, mots-clés, recherche)
- [x] Séances d’un module
- [x] Déroulé par séance (étapes, durées, consignes, total)
- [x] Duplication d’un module avec ses séances et étapes (copie indépendante)
- [x] Notes après la séance
- [x] Vue pour animer (imprimable)
- [x] Comptes et isolation des données : un compte = un espace de travail ; inscription fermée par défaut (code d’invitation ou `php artisan studio:create-user`)
- [x] Modifier et réordonner séances et étapes, copier une séance seule (même module ou autre module)
- [x] Supports, exercices et corrigés en liens https (Google Drive…), au module ou à la séance
- [x] Modèles de séance : 4 fournis à tous les comptes, modèles personnels depuis une séance
- [x] Vue pour animer : heures prévues, chronomètre, impression A4 ; durées réelles et écart prévu / réel
- [x] Export / import JSON d’un module ; Mon compte : export de toutes mes données, suppression du compte
- [x] Lien par référence pour Form’Pilot : `/modules/ref/BC01-UA03`
- [ ] Mot de passe oublié (nécessite un service SMTP), pages légales, sauvegardes, abonnement

## Démarrer

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
php artisan serve
php artisan test
```

Déploiement sur hébergement mutualisé : voir `../docs/deploiement-planethoster.md`.

Laravel 12, SQLite, Blade. Charte : orange `#F19224`, vert `#0A9650`, anthracite `#121416`.
