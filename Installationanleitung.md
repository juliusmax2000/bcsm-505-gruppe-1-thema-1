# Anleitungen (Meilenstein 3)

## Installationsanleitung

In diesem Abschnitt wird die Installation der Datenhalde für die Stellenbörse der Hochschule Niederrhein Schritt für Schritt erläutert. Voraussetzung ist ein Linux Betriebssystem.

### Voraussetzungen:
- Frisch installierte (und aktuelle) Ubuntu-Installation (Empfohlen: Ubuntu 24.04)
- Eine Domain (hier: "108.web.ide3.de"), welche zur IP des Servers auflöst
- Anlegen des Benutzers "user", welcher zur Gruppe "sudo" hinzugefügt wird

### Installieren von Docker
Als Erstes wird der GPG-Schlüssel der offiziellen Docker-Repository zu der Schlüsselverwaltung des Paketmanagers "APT" (Advanced Packaging Tool), welcher uns ermöglicht Software-Pakete zu installieren, hinzugefügt. Danach wird die offizielle Docker-Repository der Liste mit den Paketquellen hinzugefügt. Dies wird mit folgenden Kommandos erreicht:
```console
# Hinzufügen von Docker's offiziellen GPG-Schlüssel:
user@hk01:~$ sudo apt-get update
user@hk01:~$ sudo apt-get install ca-certificates curl
user@hk01:~$ sudo install -m 0755 -d /etc/apt/keyrings
user@hk01:~$ sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
user@hk01:~$ sudo chmod a+r /etc/apt/keyrings/docker.asc

# Hinzufügen der Repository zu den APT-Quellen:
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```
Anschließend wird mit folgenden Kommandos Docker installiert:
```console
# Paketliste aktualisieren
user@hk01:~$ sudo apt-get update
...
Get:6 https://download.docker.com/linux/ubuntu noble/stable amd64 Packages [15.5 kB]
...
# Docker installieren
user@hk01:~$ sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```
Mit folgendem Kommando kann man nun prüfen, ob Docker erfolgreich installiert wurde:
```console
user@hk01:~$ sudo docker run hello-world
Unable to find image 'hello-world:latest' locally
latest: Pulling from library/hello-world
478afc919002: Pull complete 
Digest: sha256:305243c734571da2d100c8c8b3c3167a098cab6049c9a5b066b6021a60fcb966
Status: Downloaded newer image for hello-world:latest

Hello from Docker!
This message shows that your installation appears to be working correctly.

To generate this message, Docker took the following steps:
 1. The Docker client contacted the Docker daemon.
 2. The Docker daemon pulled the "hello-world" image from the Docker Hub.
    (amd64v8)
 3. The Docker daemon created a new container from that image which runs the
    executable that produces the output you are currently reading.
 4. The Docker daemon streamed that output to the Docker client, which sent it
    to your terminal.

To try something more ambitious, you can run an Ubuntu container with:
 $ docker run -it ubuntu bash

Share images, automate workflows, and more with a free Docker ID:
 https://hub.docker.com/

For more examples and ideas, visit:
 https://docs.docker.com/get-started/
```
Falls die Ausgabe des Kommandos mit der obigen Ausgabe übereinstimmt oder ihr ähnelt, wurde Docker erfolgreich installiert.
<br>
Damit man als Benutzer "user" den "docker"-Befehl nutzen kann, ohne Sudo-Rechte zu benötigen, wird der Benutzer "user" noch zur Gruppe "docker" hinzugefügt:
```console
user@hk01:~$ sudo usermod -aG docker $USER
```
_$USER_ ist eine Variable, welche den Namen des aktuell eingeloggten Benutzers zurückgibt.

Nun muss man sich nur noch neu einloggen, um die Rechtetabelle neu zu laden. Um zu Überprüfen, ob der Benutzer ohne Sudo-Rechte auf den "docker"-Befehl zugreifen kann, ist möglich mittels:
```console
user@hk01:~$ docker --version
Docker version 27.4.0, build bde2b89
```
Ohne diesen zusätzlichen Schritt würde man beim Aufrufen des Befehls ohne Sudo-Rechte folgende Ausgabe erhalten:
```console
user@hk01:~$ docker container ls
permission denied while trying to connect to the Docker daemon socket at unix:///var/run/docker.sock: Get "http://%2Fvar%2Frun%2Fdocker.sock/v1.47/containers/json": dial unix /var/run/docker.sock: connect: permission denied
```

### Herunterladen der benötigten Dateien aus dem Repository
Die benötigten Dateien für das Aufsetzen des Webservers müssen aus dem Git-Repository heruntergeladen werden. Hierbei werden folgenden Befehle benötigt:
```console
# Um das git-Package zu installieren:
user@hk01:~$ sudo apt install git
# Um das Repository zu "klonen":
user@hk01:~$ git clone https://github.com/juliusmax2000/bcsm-505-gruppe-1-thema-1.git
# Ins Verzeichnis des Repositories wechseln:
user@hk01:~$ cd bcsm-505-gruppe-1-thema-1/
# Setzen der Berechtigungen, damit der Docker-Container Schreibrechte auf die Dateien besitzt:
user@hk01:~/bcsm-505-gruppe-1-thema-1$ sudo chown -R 82:user *
```

### Aufsetzen des Webservers
Um den Inhalt unserer Webseite abrufbar zu machen, benötigen wir einen Webserver. In diesem Falle werden mittels Docker zwei Container erstellt: Ein Container mit Caddy (dem Webserver) und einen mit PHP.
Da Caddy alleine keine Funktionalitäten besitzt, um PHP-Dateien zu interpretieren, wird ein zweiter Container mit PHP benötigt. Dieser stellt eine Schnittstelle für Caddy bereit, damit Caddy die PHP-Dateien bereitstellen kann.
Für eine vereinfachte Erstellung der beiden Container wird hierbei ein Docker-Compose-Script angelegt. In diesem kann man bereits im Voraus Parameter definieren, mit welchen die Container erstellt werden. Das genutzte Script und die verwendeten Parameter werden im Folgenden näher erläutert:
```yaml=yaml
services:
  caddy:
    image: caddy:2-alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile:ro
      - ./public:/var/www/html
      - caddy_data:/data
      - caddy_config:/config
    depends_on:
      - php
    networks:
      - caddy_php

  php:
    image: php:8.2-fpm-alpine
    volumes:
      - ./public:/var/www/html
    networks:
      - caddy_php

volumes:
  caddy_data:
  caddy_config:

networks:
  caddy_php:
    driver: bridge
```
- ```services:``` Abschnitt definiert die Docker-Container, die durch das docker-compose-Skript gestartet werden
- ```caddy: | php:``` der Name des Services
- ```image: caddy:2-alpine``` Gibt das Docker-Image an, das verwendet werden soll (in diesem Falle das "caddy-alpine"-Image und das "php:8.2-fpm-alpine"-Image)
- ```ports:``` Binden der Ports an den Docker-Container
    - ```80:80``` Bindet Port 80 auf dem Host an Port 80 im Container (HTTP-Verkehr)
    - ```443:443``` Bindet Port 443 auf dem Host an Port 443 im Container (HTTPS-Verkehr)
- ```volumes:``` Einbinden von lokalen Verzeichnissen oder/und virtuellen Festplatten
    - ```./Caddyfile:/etc/caddy/Caddyfile:ro``` Einbinden der Caddy-Konfigurationsdatei in das entsprechende Verzeichnis im Docker-Container (":ro" steht hierbei für Read-Only)
    - ```./public:/var/www/html``` Bindet das lokale Verzeichnis ```./public``` in den Container unter ```/var/www/html```, wo Caddy die Webseite hostet
    - ```caddy_data:/data``` Bindet das Volume ```caddy_data``` in den Container unter ```/data``` ein
    - ```caddy_config:/config``` Bindet das Volume ```caddy_config``` in den Container unter ```/config``` ein, das für die Speicherung von Konfigurationsdateien von Caddy genutzt wird
- ```depends_on:``` Definieren von Abhängigkeiten von anderen Docker-Containern
    - ```php``` Container hängt vom PHP-Container ab, sodass der PHP-Container vor dem Caddy-Container gestartet werden muss
- ```networks:``` Definieren von virtuellen Netzwerken, in die sich der Docker-Container befindet
    - ```caddy_php``` Docker-Container wird Teil des Netzwerks ```caddy_php```, über welches die beiden Container isoliert miteinander kommunizieren können
- ```volumes:``` Abschnitt definiert benannte Volumes, die persistent sind und außerhalb der Lebensdauer der Container bestehen bleiben
    - ```caddy_data:``` Volume, das von Caddy verwendet wird, um Daten wie z.B. Zertifikate, Logs zu speichern
    - ```caddy_config:``` Volume, das von Caddy verwendet wird, um Konfigurationsdaten zu speichern
- ```networks:``` Definieren von Netzwerken, welche von den Docker-Containern verwendet werden
    - ```caddy_php:``` Name des Netzwerks
        - ```driver: bridge``` ist der Standardtreiber für Docker-Netzwerke. Er sorgt dafür, dass die Container in einem virtuellen Netzwerk kommunizieren können, während sie die Netzwerkschnittstellen des Hosts verwenden

Anschließend werden mittels des folgenden Befehls beide Docker-Container auf Basis der definierten Parameter in der Docker Compose-Datei erstellt:
```console
user@hk01:~/bcsm-505-gruppe-1-thema-1$ docker compose up -d
```

#### Zusammenfassung:
Insgesamt werden mittels der Docker Compose-Datei zwei Services (Caddy und PHP) definiert, die miteinander über ein gemeinsames Netzwerk (```caddy_php```) kommunizieren. Caddy wird hierbei als Webserver verwendet, um HTTP/HTTPS-Verkehr zu bedienen, und PHP verarbeitet die PHP-Skripte.

### Turnstile CAPTCHA einbinden

Um den CAPTCHA-Dienst für Ihr System nutzen zu können, müssen Sie sich zunächst ein Konto auf der Webseite von Cloudflare (https://dash.cloudflare.com/sign-up) erstellen. 

Erstellen Sie nun ein Widget, welches Sie für Ihre Implementierung benötigen werden. Klicken Sie dafür auf den Knopf "Add widget".

![](https://codi.ide3.de/uploads/upload_d4386a2ce46a9e46b7b2b985e26a4cdf.png)

Geben Sie dem Widget einen Namen, unter dem Feld "Widget name", sodass Sie es identifizieren können.
Außerdem fügen Sie in dem darunterliegenden Feld den Hostnamen Ihrer Webseite an, auf der das Widget laufen wird.

![](https://codi.ide3.de/uploads/upload_8744aa52ea29b6c30aa2c5a6422ed52e.png)

Als nächstes wählen Sie den "Widget Mode", wie das Widget für den Nutzer zu sehen ist und wie es mit den Nutzern interagiert. Wählen Sie die Option "Managed" aus, sofern Sie keine anderen Anforderungen oder Wünsche an das Widget haben. Außerdem wird "opt for pre-clearance" auf "no" gesetzt.

![](https://codi.ide3.de/uploads/upload_b093c4051bffd5dff67483226ab3014f.png)

Drücken Sie anschließend auf "Create", falls Ihr Widget wie gewünscht konfiguriert wurde.


Im folgenden sehen Sie nun den *site-key* und *secret-key*, diese beiden Schlüssel müssen Sie sicher abspeichern, um diese in den Konfig-Dateien einzufügen.


**Änderungen in den Config-Dateien:**

In der Datei index.php muss der *data-sitekey* auf den eigenen erstellten Key geändert werden. 

```PHP
<div class="form-group">
  <div id="cfCaptcha" class="cf-turnstile" data-sitekey="[Cloudflare Site-Key]"></div>
</div>
```

In der Datei functions.php muss in der Funktion `"checkCaptcha()"` bei der `"$data"-Variable` der eigene *secret-key* eingefügt werden.

```php
$data = [
        'secret' => '[Cloudflare Secret-Key]',
        'response' => $captchaToken,
        'remoteip' => $ip
    ];
```

### Umsetzen der automatisierten Löschung alter PDF-Dateien und Metadaten

In der Datenhalde abgelegte PDF-Dateien und META Daten, welche älter als 30 Tage sind, sollen automatisiert gelöscht und der Speicher somit bereinigt werden. Hierzu ist die regelmäßige Prüfung der Timestamps, der beim Upload einer Datei erstellten Ordner, nötig. Dies lässt sich durch das Erstellen eines Skripts in Kombination mit dem Cron-Daemon bewerkstelligen. Cron ist ein Dienst welcher automatisiert wiederkehrende Aufgaben erledigen kann und ist in Ubuntu in der Regel bereits vorinstalliert (```cron``` und ```anacron```) [[10]](https://wiki.ubuntuusers.de/Cron/).

Zum Überprüfen, ob die Dienste Cron und Anacron auf dem System installiert sind, werden die folgenden Befehle nacheinander ausgeführt: 
```systemctl status cron``` 
```systemctl status anacron```

Beim Erhalt der Fehlernachricht *Unit cron.service could not be found* müssen die Dienste zuerst installiert werden. Dies wird durch folgende Befehle ausgeführt:
```sudo apt install cron```
```sudo apt install anacron```

Danach werden die Dienste manuell gestartet:
```sudo systemctl start cron```
```sudo systemctl start anacron```


Nach erneutem Prüfen gibt es eine positive Rückmeldung vom System, dass die Dienste wie gewünscht ausgeführt werden. 

Zum Anlegen des Cronjobs, welcher die Prüfung/Löschung durchführen soll, muss folgendes ausführbares Skript in dem dafür entsprechendem Verzeichnis (```/etc/cron.daily/```) gespeichert werden: 

**Skript "*datenbereinigung*"**
```bash
#!/bin/bash
Speicher="/home/user/caddy_test/public/uploads"
find "$Speicher" -type d -mtime +30 -exec rm -rf
```

Das Skript wird durch Speichern im entsprechenden Verzeichnis vom Cron-Dienst ausgeführt und erledigt somit nun automatisiert seine Aufgabe. Um von Cron ausgeführt zu werden, muss dem Skript zuvor noch die benötigte Berechtigung mit ```sudo chmod +x datenbereinigung``` zugeteilt werden.

Standardmäßig werden die Cronjobs zur Prüfung/Löschung immer morgens um 6:25 Systemzeit ausgeführt. Um das Skript und die Automatisierung auf Funktion zu prüfen, können eine oder mehrere Testdateien über die Webanwendung hochgeladen werden. Diese werden daraufhin an folgendem Ort gespeichert: ```/home/user/caddy_test/public/uploads```
Im Skript ist ```-mtime +30``` durch ```-mmin +1``` zu ersetzen. Diese Änderung löscht Ordner im Speicherverzeichnis, welche älter als eine Minute sind. Anschließend werden die Daily Cronjobs mit dem Befehl ```run-parts /etc/cron.daily``` manuell ausgeführt.

Unter /home/user/caddy_test/public/uploads lässt sich nach der manuellen Ausführung überprüfen, ob die Ordner erfolgreich gelöscht wurden. Nach dem Test muss das Skript wieder auf ```-mtime +30``` geändert werden.


## Betreiberdokumentation

- Überprüfung, ob die eingesetzten Tools weiterhin kostenlos verfügbar sind.
    - Alternativen:
        - reCAPTCHA (aktuell komplett kostenlos)
        - hCAPTCHA (kostenloser Plan bis 1.000.000 Anfragen im Monat)
- Datenbank überprüfen, ob der festgelegte Speicher ausreichend ist.
- (Optional) Jährliche Überprüfung auf neue Software zum Scannen von schädlichen PDF-Dateien.

### Updaten der Docker-Images
Falls eine neue Version von einer der Docker-Images für Caddy oder PHP verfügbar wird, müssen diese geupdated werden, um die aktuellen (Sicherheits-)Patches installiert zu haben:
```console
# Befehle müssen im selben Pfad wie "docker-compose.yml" ausgeführt werden
# Löschen der Docker-Container
docker compose down
# Neue Version des "caddy:2-alpine"-Images herunterladen
docker pull caddy:2-alpine
# Neue Version des "php:8.2-fpm-alpine"-Images herunterladen
docker pull php:8.2-fpm-alpine
# Docker-Container mit neuen Images erstellen
docker compose up -d
```

Bei der halbjährlichen Wartung sollte der Testplan einmal durchlaufen werden. Die zu erwarteten Ergebnisse stehen in der Tabelle.
Die Variablen zur Anpassung der auswählbaren Fachbereiche und Stellentypen befinden sich in der ```config.php```.
Um die Fachbereiche anzupassen, müssen die Werte in ```$fachbereiche``` verändert werden.
Um die Stellentypen anzupassen, müssen die Werte in ```$stellentypen``` verändert werden.