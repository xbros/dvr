dvr
==========
Dynamic VPN routes

Install
========
1. Placer les fichiers dans le sous-répertoire `nic` du DocumentRoot.
    ex: `/var/www/nic`

2. Editer le fichier de config `dvr/config.php`

3. Initialiser
	
	```
	/path/to/dvr/dvr init
	```

4. créer fichier passwd et ajouter utilisateurs

	```
	htpasswd -c /path/to/.htpasswd user1
	htpasswd  /path/to/.htpasswd user2
	```

5. modifier .htaccess avec le chemin absolu vers le fichier passwd
	
	```
	AuthUserFile "/path/to/.htpasswd"

	```

6. mettre en place cron d'actualisation des routes

	```
	* * * * * /path/to/dvr/dvr route
	```

7. mettre en place initialisation automatique (`dvr init`) après démarrage du VPN. Exemple avec crontab :

	```
	@reboot rm dvr.no*.conf; sleep 60; /path/to/dvr/dvr init
	```

Update API
==============
Protocole [dyndns2](https://help.dyn.com/remote-access-api/perform-update/)

```
https://username:password@server.domain.fr/nic/update?hostname=devicename&myip=1.2.3.4
```
- `username:password` = nom utilisateur et mot de passe sur serveur
- `hostname` = nom du device. minimum 3 caractères alphanumeriques ou [_.-] commencant par une lettre ex: samsung-galaxy
- `myip` = ip publique (optionnel). si omise ou invalide, l'ip est déterminée par le serveur.
- `offline` = `YES` ou `NOCHG` (optionnel). supprime le device de la table

[Return code](https://help.dyn.com/remote-access-api/return-codes/):
- affiché dans body

List API
==============
Affiche les devices et ip de l'utlisateur
```
https://username:password@server.domain.fr/nic/list
```

Serveur
=======

### Authentification PHP Basic
- fichier `~/.dvr/dvr.passwd`
```
user passwd
```

### table des ip
- fichier texte `~/.dvr/dvr.conf`
- format csv space delimiter
- une ligne par device

```
ip device user
```

### script php
- fichier php `~/public_html/nic/update.php`
- [Supprimer extension .php de l'url](https://alexcican.com/post/how-to-remove-php-html-htm-extensions-with-htaccess/)
- parse requetes GET et POST
- met à jour la table des ip

### log des requetes
- fichier `~/.dvr/dvr.log`
- [format](https://en.wikipedia.org/wiki/Common_Log_Format)

```
ip user [time] script "message"
```

Client
=======
envoie la requete avec l'ip public ttes les 5min si l'ip change

utiliser des clients pour serveurs DNS:
- linux: ddclient
- android: dynamic dns update
