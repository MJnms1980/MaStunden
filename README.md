# MaStunden

Mitarbeiter-Zeiterfassung für den eigenen Webspace. Entwickelt von **IT-Janz**.

MaStunden 1.0.7 läuft mit PHP und MySQL und lässt sich per FTP/FTPS und Browser installieren. Unternehmensangaben und Firmenlogo sind frei konfigurierbar. Hauptdomain, Subdomain und Unterverzeichnis werden unterstützt; für jedes Unternehmen wird eine separate Installation eingerichtet.

## Funktionen

- Mitarbeiter- und Administratorkonten mit getrennten Zugriffsrechten
- Arbeitszeiten, mehrere Pausen, Stempeluhr und Stundenkonto
- Individuell einrichtbare Sollzeiten und Urlaubsansprüche – ohne automatische Vorgaben
- Urlaub, Krankheit, Abwesenheitsanträge und Freigaben
- Monatsberichte als PDF und CSV
- Geschützte Dokumentuploads, Änderungsprotokoll und Mitteilungen
- Verschlüsselte Backups und Wiederherstellung
- Responsive Oberfläche für Desktop, Tablet und Smartphone

Eine Lohnabrechnung ist nicht enthalten.

## Installation

Benötigt werden PHP 8.3 oder neuer, MySQL 8.0 oder neuer, HTTPS und die in der Anleitung genannten PHP-Erweiterungen. SSH, Composer und Node.js sind für den Betrieb nicht erforderlich.

1. Dieses Repository herunterladen und entpacken.
2. **Nur den Inhalt von `web/`** per FTP/FTPS in das gewünschte Webverzeichnis hochladen.
3. `https://stunden.example.org/install.php` aufrufen (Beispieladresse ersetzen).
4. Datenbank, Unternehmen und erstes Administratorkonto einrichten. Der geschützte Speicher wird vom Installationsprogramm angelegt.

Die vollständige [Installations- und Updateanleitung](dokumentation/INSTALLATION.md) beschreibt Voraussetzungen, Einrichtung, Backups und Updates bestehender Installationen. Vor einem Update eine Sicherung anlegen und vorhandene Konfiguration sowie privaten Speicher behalten.

## Dokumentation und Entwicklung

- [Installation und Betrieb](dokumentation/INSTALLATION.md)
- [Architektur](dokumentation/ARCHITEKTUR.md)
- [Prüfbericht und bekannte Prüfgrenzen](dokumentation/PRUEFBERICHT.md)
- [Hinweise zu den Entwicklungstests](entwicklung/TESTS-LESEN.txt)

Die historischen Testskripte dokumentieren den lokalen Entwicklungsaufbau und erfordern angepasste Pfade und isolierte Testdienste. Sie sind keine fertig konfigurierte Testumgebung. Enthaltene Testpasswörter sind ausschließlich synthetische Beispieldaten. Niemals gegen eine produktive Installation testen.

## Lizenz

MaStunden wird unter der [MIT-Lizenz](LICENSE) bereitgestellt. Nutzung, Änderungen und Weitergabe sind auch kommerziell erlaubt; der Lizenz- und Urheberrechtshinweis muss erhalten bleiben. Die Anwendung enthält die Entwicklerkennzeichnung von IT-Janz. Die MIT-Lizenz schreibt keine unveränderbare Anzeige des Logos in abgeleiteten Versionen vor.
