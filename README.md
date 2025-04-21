# Application MISTRAL PENAL

La branche Mistral-v1 correspond à la version 1 de l'outil numérique. Il s'agit d'un MVP.

Plus d'information : https://beta.gouv.fr/startups/mistral-penal.html
https://mistralpenal.beta.gouv.fr/

## Présentation générale

L'application **MISTRAL** a pour objectif de fiabiliser la saisie des données pour en garantir la qualité et simplifier l'agrégation des tableaux (limiter les bugs et les risques d'une mauvaise manipulation).

### Contexte
* Les enseignements des Etats généraux de la justice Le numérique : un levier pour améliorer l’efficience du pilotage de l’activité juridictionnelle (via une harmonisation et homogénéisation des pratiques).
* Un enjeu de simplification des outils de travail : Une volonté du Garde des sceaux - Ministre de la justice.

### Problème
A l’origine de l’investigation, le constat se porte sur l’aspect peu collaboratif des outils utilisés, un partage d’information peu fluide, une redondance dans la saisie des données. Ces irritants quotidiens peuvent se traduire par la perte de plusieurs heures par semaine pour chaque agents en charge de manipuler les données) en particulier les greffiers.

La première période d’investigation a cherché à identifier comment rendre plus efficace les intervenants de la chaîne pénale pour réduire les délais d’exécution des jugements.

La seconde période d’investigation a permis de s’interroger de savoir comment faire pour rendre plus efficient le suivi et le pilotage des dossiers tout au long de la chaîne pénale.

### Apprentissages
* Pour les greffiers, il est plus important de simplifier drastiquement la saisie des données sur l’ensemble de l’audience (éviter les copier-coller, synchroniser les documents et logiciels) et pas uniquement le reporting. Simplifier la saisie des données est le pré-requis pour apporter des améliorations sur les étapes suivantes (agrégation, lecture)
* Pour les Directeurs des services de greffe judiciaire, les enjeux sont de fiabiliser la saisie des données pour en garantir la qualité et de simplifier l’agrégation des tableaux (limiter les bugs et les risques d’une mauvaise manipulation). Cela afin de gagner du temps et garantir une remontée d’informations fiables
* Pour les chefs de juridiction / chefs de cours, il y a des enjeux de partage des bonnes pratiques en matière d’indicateurs suivi de d’améliorer leur lisibilité afin de faciliter leur exploitation et d’améliorer l’efficience du pilotage.

### Nos partis pris
* Sortir de la logique “le document contient la donnée”, mais, au contraire penser “la donnée génère le document”
* Simplifier la production des tableaux de bord est une occasion pour simplifier le travail des greffiers
* Récupérer la donnée à la source (la note d’audience) pour simplifier et automatiser ses différents traitements

### Solution
MISTRAL Pénal est un service numérique qui simplifie le suivi des dossiers pour les greffiers et automatise les activités de pilotage (statistiques) pour les DSGJ afin de leur faire gagner du temps au quotidien et leur permettre de se concentrer sur leurs activités à forte valeur ajoutée.

MISTRAL Pénal améliore l’efficience du pilotage de l’activité juridictionnelle, de la saisie de la donnée jusqu’à son analyse.

* Pour les greffiers : gagnez du temps en supprimant les tâches de recopie de données et de bâtonnage, et suivez votre progression grâce à la barre d’avancement du travail
* Pour les DSGJ : améliorez la qualité des données, et gagnez en temps et confort lors de la production des indicateurs, tout en ayant des statistiques toujours à jour
* Pour les chefs de juridiction : bénéficiez d’indicateurs de meilleure qualité, plus lisibles et facilement actionnables, pour une organisation optimisée

Embarquez avec MISTRAL Pénal et naviguez vers une justice plus efficace et agile.

## Procédure d'installation
### Installation sur un poste de développement
```
composer install
yarn install
php bin/console assets:install --symlink public
php bin/console bazinga:js-translation:dump --merge-domains web
php bin/console f:j:dump --format=json
yarn encore dev --watch
# génération de la clé de chiffrement pour l'application
php bin/console app:chiffrement:generer-une-cle
```

### Importation des référentiels de l'application
Deux modes existent pour récupérer les référentiels : le mode dégradé et l'iso-cassiopée

#### Mode dégradé : import depuis des fichiers SQL
Cette solution doit être adopté si l'utilisation du RPA n'est pas possible.

*Etape 1: génération des référentiels pour la machine cible*
 ```
 php bin/console app:import-cassiopee:generer-les-backups-des-referentiels
 ```

 *Etape 2: consommation des référentiels depuis la machine cible*
```
php bin/console app:import-cassiopee:charger-les-referentiels-depuis-les-backups
```

#### Mode iso-cassiopée
Cette solution doit être adopté si l'utilisation du RPA est possible
```
php bin/console app:import-cassiopee:recuperer-les-referentiels
```

### Variables d'environnement

Un ensemble de variable d'environnement doivent être configurées pour que l'application puisse fonctionner :

| Variable d'environnement | Description | Exemple |
| -- | -- | -- |
| SELENIUM_CHROME_CASSIOPEE_SERVER_URL | Serveur SELENIUM dédié aux appels Cassiopée | http://xxx:4444/wd/hub |
| CASSIOPEE_APP_HOMEPAGE | page d'accueil de Cassiopée | https://ksp.intranet.justice.gouv.fr/ |
| CASSIOPEE_PROXY | Proxy pour atteindre le site de Cassiopée | proxy-cassiopee |
| CASSIOPEE_TIMEOUT | Durée de chargement maximale autorisée | 10 |

# Génération des DDL
Pour les besoins de l'équipe test, la fourniture des schémas de l'application est requise. Un script a été développé afin d'effectuer cette tâche.

## Mode opératoire

1. Se connecter sur l'instance postgres

̀```bash
./home/bash/generate_ddl.sh
̀```

Le script exécute la génération des 3 fichiers DDLs. Ils sont de la forme *ddl_schema_{bdd}.{yyyymmdd}.sql* et se trouvent dans le répertoire partagé */share*

2. Se connecter sur l'instance php8

Déplacer les fichiers de /share dans les différents répertoires dédiés ddl/ des applications
