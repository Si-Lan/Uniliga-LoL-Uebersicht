# Uniliga-LoL-Übersicht
## Webseite
Meine Webseite, auf der dieses Projekt läuft findet ihr hier:  
https://silence.lol/uniliga/

## Unterstützung
Wenn ihr mich unterstützen wollt könnt ihr das gerne hier tun  
[![PayPal](https://img.shields.io/badge/Donate-PayPal-blue?style=flat)](https://paypal.me/SimonlLang)

## Benötigt zum eigenen Aufsetzen:

### Festlegen von Zugangsdaten:
* Zugangsdaten zur Datenbank in
  * **DB-info.php** (*.template* entfernen)
  * ```
    <?php
    $dbservername = "Server-Name";
    $dbdatabase = "Datenbank-Name";
    $dbusername = "Datenbank-Benutzername";
    $dbpassword = "Datenbank-Passwort";
    $dbport = Datenbank-Port (NULL wenn nicht vorhanden);
    ```
* Admin-Passwort in
  * **admin/admin-pass.php** (*.template* entfernen)
  * ```
    <?php
    $RGAPI_Key = "Riot-API-Key"
    ```
* Riot-API-Key in
  * **admin/riot-api-access/RGAPI-info.php** (*.template* entfernen)
  * ```
    <?php
    function get_admin_pass(): string {
        return "Admin-Passwort";
    }

    ```

### Datenbank:
*MariaDB*-Datenbank:  
[SQL-File mit Datenbank-Struktur](https://silence.lol/storage/dbs9010181.sql.zip)


## Wartungsaufwand:

### Toornament-Updates:
1. Möglichkeit: Manuell
   * Buttons im BE (/admin)
2. Möglichkeit: Automatisch
   * Cron-Jobs einrichten
      * *Dokumentation dazu folgt*

### bei neuen LoL-Patches:
* Riots DataDragon Dateien updaten:
  * *Muss bisher noch manuell erledigt werden, Automation ist geplant*
> * https://ddragon.leagueoflegends.com/cdn/(patch)/data/en_US/summoner.json  
> * https://ddragon.leagueoflegends.com/cdn/(patch)/data/en_US/runesReforged.json  
> * https://ddragon.leagueoflegends.com/cdn/(patch)/data/en_US/champion.json  
> * https://ddragon.leagueoflegends.com/cdn/dragontail-(patch).tgz  
>   * Aus der .tgz werden gebraucht:
>     * (patch)/img/champion  
>     * (patch)/img/item  
>     * (patch)/img/spell  
>     * img/perk-images
>   * (Patch 13.8.1 ist als Beispiel schon in /ddragon)
>
> * Und hier können die Patches nachgeschaut werden:  
>   * https://ddragon.leagueoflegends.com/api/versions.json
