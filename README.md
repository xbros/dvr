dvr
==========
Dynamic VPN routes

Install
========
1. Placer les fichiers dans le sous-répertoire `nic` du DocumentRoot.
    ex: `~/public_html/nic`
2. Editer le fichier de config `DVR/config.php`
3. Tester l'url `https://server.domain.fr/nic/update`
4. Si nécessaire, ajouter des utilisateurs dans le fichier passwords

Update API
==============
Protocole [dyndns2](https://help.dyn.com/remote-access-api/perform-update/)

```
https://username:password@server.domain.fr/nic/update?hostname=devicename&myip=1.2.3.4
```
- `username:password` = nom utilisateur et mot de passe sur serveur
- `hostname` = nom du device. minimum 3 caractères alphanumeriques ou [_-.] commencant par uen lettre ex: samsung-galaxy
- `myip` = ip publique (optionnel). si omise ou invalide, l'ip est déterminée par le serveur.
- `offline` = `YES` ou `NOCHG` (optionnel). supprime le device de la table

[Return code](https://help.dyn.com/remote-access-api/return-codes/):
- affiché dans body

Devices API
==============
Affiche les devices et ip de l'utlisateur
```
https://username:password@server.domain.fr/nic/devices
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
user device ip
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

### script de config des routes [papa]
- fichier bash `dvr.sh`
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
