# MaStunden

Mitarbeiter-Zeiterfassung für den eigenen Webspace. Entwickelt von **[IT-Janz](https://www.it-janz.de)**.

**Kostenlos und frei nutzbar:** Jede Person darf MaStunden privat oder kommerziell verwenden, anpassen und weitergeben. Es gelten die Bedingungen der [MIT-Lizenz](LICENSE).

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

1. Das [Installationspaket der aktuellen Veröffentlichung](https://github.com/MJnms1980/MaStunden/releases/latest) herunterladen und entpacken. Für Mitarbeit am Quellcode kann alternativ das gesamte Repository über **Code → Download ZIP** heruntergeladen oder mit Git geklont werden.
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

## Mitarbeit und Rückmeldungen

Fehlerberichte, Vorschläge und Pull Requests sind willkommen. [CONTRIBUTING.md](CONTRIBUTING.md) beschreibt den Ablauf und die Voraussetzungen für Entwicklungstests. Für normale Fehler und Funktionswünsche bitte die [GitHub Issues](https://github.com/MJnms1980/MaStunden/issues) verwenden.

## Sicherheitsmeldungen und Betriebsdaten

Potenzielle Sicherheitslücken bitte vertraulich melden, wie in [SECURITY.md](SECURITY.md) beschrieben. Keine Zugangsdaten, Mitarbeiterdaten, Dokumente oder produktiven Backups in Issues, Pull Requests oder Testdateien hochladen. Für Beispiele ausschließlich erfundene Daten verwenden.

Die vorhandene `.gitignore` schließt unter anderem Konfigurationen, Uploads und Backups aus. Sie entfernt keine bereits versionierten Daten aus der Git-Historie.

## Lizenz

MaStunden wird unter der [MIT-Lizenz](LICENSE) bereitgestellt. Nutzung, Änderungen und Weitergabe sind auch kommerziell erlaubt; der Lizenz- und Urheberrechtshinweis muss erhalten bleiben. Die Anwendung enthält die Entwicklerkennzeichnung von IT-Janz. Die MIT-Lizenz schreibt keine unveränderbare Anzeige des Logos in abgeleiteten Versionen vor.

Die Software wird ohne Gewährleistung bereitgestellt; maßgeblich ist der Lizenztext. Eigene Änderungen müssen nicht veröffentlicht werden. Kostenlose Nutzung beinhaltet keinen zugesicherten Support.
