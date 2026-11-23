# Update auf MaStunden 1.0.8

Stand: 23. November 2026. Das Datenbankschema bleibt auf Version 1. Bestehende Zeiten, Konten und Passwörter bleiben erhalten.

## Vor dem Update

1. Vollständige verschlüsselte Sicherung erstellen, herunterladen und Prüfsummen/Entschlüsselbarkeit prüfen. Das Sicherungspasswort getrennt sicher verwahren.
2. Programmdateien und config.php separat geschützt sichern. Keine Sicherungen im öffentlichen Webverzeichnis ablegen.
3. Wartungsfenster einplanen. Während des Austauschs dürfen keine Anfragen mit gemischten Programmversionen verarbeitet werden. Den Webzugriff vorübergehend über den Hoster sperren oder eine Wartungsseite verwenden; erst nach vollständigem Austausch wieder freigeben.
4. Datenbankverbindung und Speicheradresse wie unten vorbereiten. Bei fehlender TLS-Unterstützung den Hoster kontaktieren.

## Datenbankverbindung

Für localhost, 127.0.0.1 und ::1 wird eine lokale Verbindung verwendet. Externe Verbindungen verwenden standardmäßig geprüftes TLS. Falls PHP den systemweiten CA-Speicher nicht lesen darf, eine geeignete CA-Datei im geschützten Speicher bereitstellen und in config.php innerhalb des zurückgegebenen Arrays ergänzen:

```php
'db_tls' => 'verify',
'db_ssl_ca' => '/absoluter/privater/pfad/mysql-ca.pem',
```

CA-Datei und SQL-Hostname müssen zum Zertifikat des Datenbankservers passen. Zugangsdaten nicht ändern, wenn lediglich TLS eingerichtet wird. Die Anwendung lehnt eine TLS-Verbindung ohne ausgehandelte Verschlüsselung ab.

Für bereits bestehende Installationen in einem vom Hoster isolierten internen Netz gibt es eine ausdrücklich zu setzende Übergangsausnahme `db_tls = private-network`. Sie akzeptiert ausschließlich feste private IPv4-Adressen aus 10.0.0.0/8, 172.16.0.0/12 oder 192.168.0.0/16. Sie verschlüsselt die Verbindung NICHT; unter Backup erscheint eine Warnung. Diese Ausnahme ist kein behobener TLS-Sicherheitsbefund und keine Empfehlung für Neuinstallationen. Sie muss durch eine vom Hoster bereitgestellte geprüfte TLS-Verbindung ersetzt werden. Für öffentliche SQL-Adressen ist die Ausnahme gesperrt.

## Speicher im Webverzeichnis

Nur wenn config.php bereits `storage_guard_sha256` enthält: den festen HTTPS-Pfad des tatsächlichen Speicherordners ergänzen. Beispiel – den zufälligen Ordnernamen und die Domain durch die eigene Installation ersetzen:

```php
'storage_public_url' => 'https://stunden.example.org/mastunden-storage-BEISPIEL/',
```

Keine Adresse aus einer fremden Installation übernehmen. Diese Adresse wird spätestens nach einer Stunde erneut auf wirksame Webserversperren geprüft. Bei fehlendem Schutz oder einer fehlgeschlagenen Prüfung wird der Betrieb vorsorglich gesperrt. Die bestehende Prüfung der .htaccess-Prüfsumme bleibt erhalten. Speicher außerhalb des Webverzeichnisses benötigt diese Angabe nicht.

## Dateien austauschen und prüfen

Den vollständigen Inhalt von web/ übertragen, insbesondere auch die neuen Dateien lib/files.php und lib/database.php. Vorhandene config.php und den privaten Speicher behalten. install.php niemals erneut zur Einrichtung ausführen; bei vorhandener config.php ist der Assistent gesperrt. Danach Anmeldung, Mitarbeiterübersicht, Dokumentzugriff, Berichte und Backup prüfen. Erst dann Wartungssperre aufheben.

## Geändertes Verhalten

- Dateiänderungen werden dauerhaft protokolliert. Bei Prozessabbrüchen werden noch nicht bestätigte neue Dateien entfernt; bestätigte Löschungen werden sicher abgeschlossen. Alte erase-*.tmp aus früheren Versionen werden nicht blind gelöscht, sondern zur manuellen Prüfung behalten.
- Upload-Metadaten werden vor dem Speichern validiert. Standardmäßig gelten 500 MB Speicher pro Mitarbeiter und 30 erfolgreiche Uploads pro Stunde. Das Speicherlimit ist unter Unternehmen einstellbar und ändert keine vorhandenen Dateien.
- Unter Backup werden mehr als 24 Stunden alte, nicht zugeordnete Dokument-/Logodateien zur ausdrücklich bestätigten Bereinigung angeboten. Keine automatische Löschung alter Unterlagen.
- Mitarbeiterlöschungen bewahren fremde Protokolleinträge; Verweise auf gelöschte Akteure werden anonymisiert. Namensteile lösen keine pauschalen Löschungen mehr aus.
- Dokumententscheidungen protokollieren nun die vorherige und neue Rückmeldung. Bereits früher überschriebene Rückmeldungen können nicht nachträglich rekonstruiert werden.
- Mitarbeiterlöschung und normale Seitenaufrufe entfernen keine Sicherungsarchive mehr. Unter Backup können Administratoren ein bestimmtes Archiv ausdrücklich mit Passwort und Begründung löschen. Archive können gelöschte Personen enthalten: Aufbewahrung und Löschung organisatorisch festlegen, auch für externe Kopien.
- Leseanfragen können parallel arbeiten; Downloads geben die globale Sperre vor der Übertragung frei. Wartezeiten für Anwendungssperren sind begrenzt.
- Der bestehende Wiederherstellungsschlüssel heißt nun korrekt installationsweiter Notfallschlüssel. Er bleibt für aktive Administratorkonten gültig und muss entsprechend geschützt verwahrt werden. Es wurde kein neuer Schlüssel erzwungen.
- FTP-Einrichtung verwendet nur noch FTPS mit Zertifikatsprüfung; unverschlüsseltes FTP ist entfernt.

## Rückkehr zur vorherigen Version

Bei einem fehlgeschlagenen Update den Zugriff gesperrt lassen. Zuerst laufende Dateijournale mit Version 1.0.8 prüfen bzw. abschließen; alte Versionen verstehen diese Journale nicht. Danach gesicherte Programmdateien und config.php zurückspielen. Die Datenbank nur dann aus einer Sicherung wiederherstellen, wenn tatsächlich Daten beschädigt wurden; eine Wiederherstellung überschreibt spätere Änderungen. Änderungen ausschließlich an Programmdateien erfordern keine Datenbankwiederherstellung.
