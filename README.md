# Simple Shibboleth Client for Etherpad Lite

Simple Shibboleth client is a wrapper for Etherpad Lite using Etherpad Lite API.

It allows restricting etherpad to shibboleth authenticated users. The exported URL forces shibboleth authentication.
It also gives a few simple functionalities : 
* list pads you created/contributed
* actions on pad you created: delete it, make it public/private

# Configuration

## config.inc.php

    <?php
    
    $APIKEY = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    $APIENDPOINT = 'https://pad.univ-xxxxxx.fr/api';

## Apache configuration

At Université de Paris1, we used the following servers:
- a reverse proxy (apache worker) : "pad"
- a server with PHP and mod-shib2 : "etherpad-wrapper"
- a server for etherpad-lite running node.js : "etherpad-lite"

### Configuration of reverse proxy

    SSLProxyEngine on
    ProxyPreserveHost on
    ProxyPassMatch ^(/|/ip/.*|/Shibboleth.sso/.*)$ https://etherpad-wrapper.univ-paris1.fr$1   
    ProxyPass / http://etherpad-lite.univ-paris1.fr:8080/

### Configuration of PHP + mod-shib2 server

    DocumentRoot /webhome/etherpdw/html
    
    RewriteEngine On
    RewriteRule ^/ip/(.*) /index.php?name=$1 [L]
    
    <Location />
         AuthType shibboleth
         ShibRequireSession On
         require valid-user
         ShibUseHeaders On
    </Location>

# Examples

See it live here: https://pad.univ-paris1.fr/

# Etherpad Lite adaptations

## disable ability for the user to modify its username for group pads
    
    diff --git a/src/static/js/pad_userlist.js b/src/static/js/pad_userlist.js
    index d051182..83e813a 100644
    --- a/src/static/js/pad_userlist.js
    +++ b/src/static/js/pad_userlist.js
    @@ -473,7 +473,7 @@ var paduserlist = (function()
     
           $("#otheruserstable tr").remove();
     
    -      if (pad.getUserIsGuest())
    +      if (pad.getUserIsGuest() && !document.location.href.match(/\$/))
           {
             $("#myusernameedit").addClass('myusernameedithoverable');
             setUpEditable($("#myusernameedit"), function()


## for private group pags, export url is /ip/xxx instead of /p/xxx

    --- a/src/node/handler/PadMessageHandler.js
    +++ b/src/node/handler/PadMessageHandler.js
    @@ -1050,6 +1050,7 @@ function handleClientReady(client, message)
               "readonly": padIds.readonly,
               "serverTimestamp": new Date().getTime(),
               "globalPadId": message.padId,
    +         "publicStatus": pad.getPublicStatus(),
               "userId": author,
               "cookiePrefsToSet": {
                   "fullWidth": false,
    diff --git a/src/static/js/pad_editbar.js b/src/static/js/pad_editbar.js
    index 91a07bf..169f0e0 100644
    --- a/src/static/js/pad_editbar.js
    +++ b/src/static/js/pad_editbar.js
    @@ -248,13 +248,14 @@ var padeditbar = (function()
           if ($('#readonlyinput').is(':checked'))
           {
             var basePath = document.location.href.substring(0, document.location.href.indexOf("/p/"));
    -        var readonlyLink = basePath + "/p/" + clientVars.readOnlyId;
    +        var readonlyLink = basePath + (clientVars.publicStatus || !basePath.match(/\$/) ? "/p/" : "/ip/") + cli
entVars.readOnlyId;
             $('#embedinput').val("<iframe name='embed_readonly' src='" + readonlyLink + "?showControls=true&showCha
             $('#linkinput').val(readonlyLink);
           }
           else
           {
             var padurl = window.location.href.split("?")[0];
    +       if (!clientVars.publicStatus && padurl.match(/\$/)) padurl = padurl.replace('/p/', '/ip/');
             $('#embedinput').val("<iframe name='embed_readwrite' src='" + padurl + "?showControls=true&showChat=tru
             $('#linkinput').val(padurl);
           }


# License

Apache License
