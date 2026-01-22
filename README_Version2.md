# Mini app (HTML + PHP) — envoyer et stocker messages

Ce petit projet contient :
- `index.html` : page front-end (HTML + JS) pour sélectionner les 4 utilisateurs, écrire un message et l'envoyer.
- `api.php` : backend PHP simple pour lister les destinataires, stocker et retourner l'historique.
- `data/messages.json` : fichier JSON qui contient l'historique (initialisé vide).

Installation / test local
1. Place tous les fichiers dans un dossier (ex. `sms-mini-php`).
2. Assure-toi que PHP est installé (PHP 7.0+ recommandé).
3. Donne la permission d'écriture au dossier `data` si nécessaire :
   - Linux/macOS : `chmod -R 775 data` ou `chown -R www-data:www-data data` selon ton environnement.
4. Lance le serveur PHP intégré (depuis le dossier) :
   ```
   php -S localhost:8000
   ```
5. Ouvre `http://localhost:8000/index.html` dans ton navigateur.

API
- GET `api.php?action=recipients` -> liste des 4 destinataires
- GET `api.php?action=messages` -> historique (array)
- POST `api.php?action=send` -> envoyer (stocker) un message
  - Body JSON : `{ "recipientIds": [1,2], "message": "Bonjour" }`
  - Réponse : `{ "results": [ ...records... ] }`

Remarques
- Ce backend stocke simplement les messages dans un fichier JSON; pas d'envoi réel de SMS (mock).
- Si tu déploies sur un serveur partagé, vérifie les permissions d'écriture sur `data/messages.json`.
- Si tu veux ajouter : pagination, recherche, authentification ou stockage en base (SQLite/MySQL), je peux adapter.