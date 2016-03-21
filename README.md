dvr
==========
Dynamic VPN routes

Requete
=======
- protocole [dyndns2](https://help.dyn.com/remote-access-api/perform-update/)

```
https://username:password@server.domain.fr/nic/update?hostname=devicename&myip=1.2.3.4
```
- `username:password` = nom utilisateur et mot de passe sur scw1
- `hostname` = nom du device. ex: samsung-galaxy
- `myip` = ip publique

Serveur
=======

### Authentification HTTP basic [papa]
- [Apache basic auth](https://httpd.apache.org/docs/2.2/fr/mod/mod_auth_basic.html)

### base de données
- fichier texte `/etc/dvr.conf` ou `~/.dvr/dvr.conf`
- format csv space delimiter
- une ligne par device

```
user device ip
```

### script php [simon]
- fichier php `public_html/nic/update.php`
- [Supprimer extension .php de l'url](https://alexcican.com/post/how-to-remove-php-html-htm-extensions-with-htaccess/)
- parse requetes url reçues: [`$_GET`](https://secure.php.net/manual/fr/reserved.variables.get.php)
- obtenir username: [`$_SERVER`](https://secure.php.net/manual/fr/reserved.variables.server.php)
- met à jour la base de données
- [return code](https://help.dyn.com/remote-access-api/return-codes/)


### log des requetes
- fichier `/var/log/dvr.log`
- [format](https://en.wikipedia.org/wiki/Common_Log_Format)

```
ip user time 
```

### interface pour gerer les device par user [optionnel]
- fichier php `public_html/nic/devices.php`
- table avec une ligne par device
- colonnes: device, ip
- actions: ajouter, supprimer, éditer une ligne

### script de config des routes [papa]
- fichier bash `/usr/local/dvr.sh`
- droits root
- cron ttes les 1 min
- lit la base de données et met en place les routes vpn
- gere les conflits. ex: 2 devices sur meme ip

Client
=======
envoie la requete avec l'ip public ttes les 5min si l'ip change

utiliser des clients pour serveurs DNS:
- linux: ddclient
- android: dynamic dns update

