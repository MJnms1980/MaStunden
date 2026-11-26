# MaStunden 1.0.9 – SQL-Verbindung und Update-Assistent

Schema 1 bleibt unverändert. Mitarbeiterkonten, Zeiten, Dokumente und Passwörter bleiben erhalten.

## Einmaliger Umstieg von 1.0.8

Version 1.0.8 enthält noch keinen Update-Assistenten. Dieses erste Update deshalb per FTPS installieren:

1. Ein vollständiges verschlüsseltes Backup erstellen und herunterladen. Das Passwort getrennt aufbewahren. Programmdateien und config.php zusätzlich geschützt sichern.
2. Für den Dateiaustausch eine Wartungsseite aktivieren, beispielsweise index.php vorübergehend durch eine PHP-Seite mit HTTP-Status 503 ersetzen. Bestehende Anfragen auslaufen lassen. Keine parallele Bedienung während des Austauschs.
3. Sämtliche Dateien aus web/ des Installationspakets übertragen, **index.php zuletzt**. Neu sind insbesondere app.php, update-bootstrap.php, update-public.key und die beiden Verwaltungsmodule. Keine config.php und keinen privaten Speicher überschreiben oder löschen.
4. Mit der neuen index.php wird die Anwendung wieder freigegeben. install.php nicht erneut ausführen.
5. Unter **Backup & Wiederherstellung** stehen „SQL-Verbindung verwalten“ und „Update-Assistent öffnen“ zur Verfügung. Dort wird auch die installierte Version angezeigt.

Bei einem manuellen Rückweg die zuvor gesicherten Programmdateien einschließlich index.php zurückspielen. Schema 1 benötigt für dieses Update keine Datenrücksetzung. Die aktuelle config.php mit der geprüften SQL-Verbindung behalten.

## SQL-Verbindung verwalten

- „Nur Verbindung prüfen“ ändert keine Konfiguration.
- „Prüfen und speichern“ ersetzt die Konfiguration erst nach erfolgreicher Verbindung und Abgleich der bisherigen Datenbankinstanz sowie der Installation.
- Externe Server verwenden TLS mit Zertifikatsprüfung. Eine IP-Adresse passt häufig nicht zum Zertifikat; den korrekten SQL-Hostnamen des Anbieters verwenden, beispielsweise sql.example.org.
- Das SQL-Passwort bleibt bei leerem Feld unverändert und wird nie angezeigt. Bei Bedarf ein CA-Zertifikat im PEM-Format hochladen. Keine privaten Schlüssel hochladen.
- Ein Fehler bei Verbindung oder Zertifikatsprüfung lässt die bisherige Konfiguration bestehen. Es gibt keinen automatischen Rückfall auf unverschlüsseltes SQL.
- Vor dem Speichern wird die bisherige Konfiguration geschützt als sql-config-previous.php im privaten Speicher gesichert. Bei einem späteren Zugangsproblem kann sie per FTPS als config.php zurückgespielt werden. Diese Datei enthält Zugangsdaten und darf nicht veröffentlicht werden.
- Ein Wechsel auf eine andere Datenbank ist eine Migration mit Backup/Wiederherstellung und wird von dieser Oberfläche absichtlich nicht durchgeführt.

## Künftige Updates im Browser

1. Das separate **signierte Updatepaket** aus den offiziellen GitHub-Veröffentlichungen laden. Das Installationspaket ist kein Updatepaket für diesen Assistenten.
2. Unter Backup den Update-Assistenten öffnen. ZIP auswählen, ein neues Backup-Passwort und das Administratorpasswort eingeben.
3. Der Assistent prüft Signatur, Version, PHP-Anforderung, Dateipfade und Prüfsummen. Anschließend wird automatisch ein verschlüsseltes Sicherheitsbackup in einzelnen Schritten erstellt. Währenddessen bleiben Änderungen gesperrt.
4. Nach Abschluss über den angezeigten Link zum Update-Assistenten zurückkehren. Das Sicherheitsbackup herunterladen und Passwort getrennt aufbewahren.
5. Mit Administratorpasswort und „UPDATE INSTALLIEREN“ den Dateiaustausch starten. Vorher wird der Inhalt des Sicherheitsbackups nochmals geprüft.
6. Nach erfolgreichem Austausch erscheint das Ergebnis unter Backup. Das Sicherheitsbackup bleibt gespeichert. Bei einem unterbrochenen Dateiaustausch stellt der nächste Seitenaufruf zuerst die vorherigen Programmdateien wieder her.

Zum Abbrechen zunächst gegebenenfalls die laufende Sicherung unter Backup abbrechen, danach die Updatevorbereitung im Assistenten beenden. Bei abgelaufener Sitzung Vorbereitung abbrechen und neu starten.

### Grenzen des Assistenten

- PHP benötigt Sodium und ZIP sowie Schreibrechte in den Programmordnern. Fehlen Schreibrechte, ist weiterhin der dokumentierte FTPS-Weg erforderlich; der Assistent erweitert keine Serverrechte und speichert keine FTP-Passwörter.
- Paket höchstens 16 MB, entpackt höchstens 32 MB, einzelne Datei höchstens 8 MB; zusätzlich gelten die PHP-Uploadlimits. Mindestens 100 MB freier Speicher für die Vorbereitung, zuzüglich ausreichendem Platz für das vollständige Datenbackup.
- Unterstützt werden vorwärts gerichtete Updates mit Schema 1 und unverändertem Bootstrap-Protokoll. Datenbankmigrationen, Downgrades und Änderungen des stabilen Update-Starters erfordern eine gesonderte manuelle Anleitung.
- Die automatische Rücknahme schützt vor einem unterbrochenen Dateiaustausch. Sie ersetzt keine Rücksetzung bei später entdeckten fachlichen Fehlern. Dafür bleiben die Programmdateien im privaten update-rollback-Ordner und das verschlüsselte Datenbackup erhalten. Der Rücknahmeordner wird bei der nächsten Updateinstallation ersetzt.
- Der öffentliche Signaturprüfschlüssel gehört zur Distribution. Ein privater Signaturschlüssel wird niemals mitgeliefert. Eigene Weiterentwicklungen können einen eigenen Schlüssel und eigene signierte Pakete verwenden; diese Vertrauensentscheidung erfolgt manuell bei der Einrichtung der eigenen Distribution.

## Technische Prüfungen

Die isolierte Prüfumgebung enthält Berechtigungs- und CSRF-Prüfungen, manipulierte/signierte Pakete, unzulässige Pfade und Konfigurationsdateien, Versions- und Schemaabweichungen, Sicherheitsbackup, Datenerhalt sowie einen erzwungenen Prozessabbruch mitten im Dateiaustausch. Sie verwendet ausschließlich erfundene Testdaten.
