# Form’Studio

Espace de préparation pédagogique de la solution **Indé’Form** : construire un cours, retrouver ses supports et l’adapter d’un groupe à l’autre.

## Version 1 (en cours)

- [x] Bibliothèque de modules (intitulé, référence facultative, objectifs, prérequis, mots-clés, recherche)
- [x] Séances d’un module
- [x] Déroulé par séance (étapes, durées, consignes, total)
- [x] Duplication d’un module avec ses séances et étapes (copie indépendante)
- [x] Notes après la séance
- [x] Vue pour animer (imprimable)
- [x] Comptes (inscription, connexion) et isolation des données : un compte = un espace de travail
- [ ] Supports et exercices (fichiers ou liens)
- [ ] Réordonner séances et étapes, modifier une étape
- [ ] Lien avec Form’Pilot (ouvrir la fiche depuis une intervention)

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
