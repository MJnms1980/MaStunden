# MaStunden 1.0.7 – Installation und Betrieb

**Für Ihr Unternehmen konfigurierbar · Entwickelt von IT-Janz**  
Stand: 8. Oktober 2026. Unternehmensangaben und Firmenlogo sind pro Installation anpassbar; das Entwicklerlogo ist fest eingebunden.

## 1. Was Sie erhalten

MaStunden ist eine PHP-/MySQL-Anwendung mit Mitarbeiter- und Administratorkonten, Arbeitszeiten und mehreren Pausen, Stempeluhr, Arbeitszeitmodellen, Stundenkonto, Abwesenheiten, Urlaubsfreigaben, geschützten Dokumenten, Monatsfreigaben, PDF-/CSV-Berichten, Änderungsprotokoll sowie verschlüsselten Backups und Wiederherstellung.

Die Installation erfolgt ausschließlich über FTP/FTPS und den Browser. Auf dem Webserver werden weder SSH, Composer, Node.js noch Docker benötigt. Es gibt keine Cloud-, CDN-, Analyse- oder externen Schriftarten-Abhängigkeiten und keine Lohnabrechnung.

Das Paket enthält:

- `web/`: fertige Programmdateien, die auf den Webspace gehören.
- `dokumentation/`: Anleitung, Funktionsübersicht und Prüfbericht.
- `entwicklung/`: Datenbankschema und automatisierte Tests für Weiterentwicklung. **Nicht in das öffentlich erreichbare Webverzeichnis hochladen.**

**Keine Testkonten und keine Datenbankzugangsdaten sind im ausgelieferten Programm vorkonfiguriert.** Das erste Administratorkonto legen Sie selbst an.

## Domains und getrennte Kundeninstallationen

MaStunden ist nicht an eine bestimmte Domain oder ein bestimmtes Unternehmen gebunden. Die Anwendung nutzt relative Links und ermittelt den Installationspfad aus der jeweiligen Serverumgebung. Im Programm muss keine Kunden-Domain eingetragen werden.

Beispiele (durch Ihre eigenen Angaben ersetzen):

- Eigene Domain: `https://example.org/`
- Subdomain: `https://stunden.example.org/`
- Unterverzeichnis: `https://example.org/zeiterfassung/`
- Installationsaufruf im Unterverzeichnis: `https://example.org/zeiterfassung/install.php`
- Unternehmen: „Beispiel GmbH“; Administrator: „Alex Beispiel“; E-Mail: `admin@example.org`.
- FTP-Zielordner je nach Anbieter beispielsweise `/httpdocs/` oder `/httpdocs/zeiterfassung/`.

Die Domains `example.org` und die genannten Personen- und Unternehmensnamen sind ausschließlich Beispiele. Firma, Administratorname und E-Mail werden bei der Installation selbst eingegeben und sind nicht vorausgefüllt.

Für jedes Kundenunternehmen richten Sie eine separate Installation mit eigenen Programmdateien, eigener Konfiguration, eigenem geschütztem Speicher und eigener Datenbank ein. Alternativ können getrennte Tabellenpräfixe verwendet werden, sofern die Hostingrechte dies zulassen. Ein gemeinsamer Kundenwechsel innerhalb einer Installation ist nicht vorgesehen. Jede genutzte Domain benötigt ein gültiges HTTPS-Zertifikat. Der FTP-Servername und der Datenbankserver können von der Website-Domain abweichen; maßgeblich sind die Angaben Ihres Hosters.

## 2. Voraussetzungen beim Hoster

Erforderlich:

- PHP **8.3 oder neuer**, eine aktuell gepflegte PHP-Version verwenden. Geprüft mit PHP 8.3.
- MySQL **8.0 oder neuer** mit InnoDB und utf8mb4. Geprüft mit MySQL 8.4. MariaDB wurde nicht getestet.
- PHP-Erweiterungen: PDO MySQL, ZIP mit AES-256-Unterstützung, Fileinfo, Mbstring, GD, OpenSSL und die üblichen Standardfunktionen einschließlich iconv.
- HTTPS mit gültigem Zertifikat. PHP muss die Verbindung als HTTPS erkennen.
- Mindestens **128 MB PHP memory_limit**, für größere Backups und große Logos **256 MB empfohlen**.
- Ein bereits vorhandener Datenbankzugang mit SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP und INDEX. Für die Wiederherstellung muss RENAME TABLE innerhalb dieser Datenbank erlaubt sein.
- Ein für PHP beschreibbarer Speicher: bevorzugt außerhalb des öffentlichen Webverzeichnisses. Falls das Hosting dies verhindert, kann MaStunden einen durch den Webserver gesperrten Ordner im Programmverzeichnis verwenden. Dafür sind cURL, HTTPS und wirksame Apache-kompatible `.htaccess`-Zugriffssperren erforderlich. Der Speicher darf auch über keine andere Domain oder Alias öffentlich erreichbar sein.
- Genügend Speicher für Dokumente, das laufende Backup, ein Sicherheitsbackup und die Wiederherstellung. Bei einer Wiederherstellung zusätzlich Platz für einen zweiten Datenbankbestand einplanen.

Die Anwendung benötigt keine Rewrite-Regeln, keine zusätzlichen Hintergrunddienste und keinen Cronjob. JavaScript erleichtert Bedienung und schrittweise Sicherung; der nächste Sicherungsschritt ist auch manuell anklickbar.

**Wichtig zum Webhosting:** MaStunden prüft die tatsächlich verfügbaren Möglichkeiten. Bei eingeschränktem Hosting testet der Installer den Zugriffsschutz über HTTPS, bevor er vertrauliche Daten speichert. Wirkt die Sperre nicht, bricht die Installation ab. Ein reiner HTML-Webspace reicht nicht aus.

## 3. Installation Schritt für Schritt

### Schritt 1 – Webspace vorbereiten

Richten Sie Ihre gewünschte Domain oder Subdomain und HTTPS beim Hoster ein, beispielsweise `stunden.example.org`. Notieren Sie Datenbankserver, Datenbankname, Datenbankbenutzer und Datenbankpasswort. Diese Angaben erhalten Sie aus dem Kundenbereich des Hostinganbieters.

Laden Sie das ZIP-Paket herunter und entpacken Sie es auf Ihrem Computer. Lassen Sie die Ordnerstruktur innerhalb von `web/` unverändert.

### Schritt 2 – Dateien hochladen

Laden Sie **nur den Inhalt des Ordners `web/`** in das Zielverzeichnis Ihrer Domain. Verwenden Sie nach Möglichkeit FTPS. ZIP-Dateien nicht einfach auf dem Webspace ablegen; die Programmdateien müssen entpackt übertragen werden.

Für eine Unterverzeichnis-Installation, beispielsweise `/zeit/`, laden Sie den Inhalt von `web/` dorthin. Die Anwendung verwendet relative Verweise und benötigt keine fest eingetragene Domain.

Ein Installationsschlüssel ist nicht erforderlich. MaStunden legt den geschützten Speicher während der Einrichtung automatisch an.

### Schritt 3 – Installationsassistent öffnen

Öffnen Sie:

```text
https://stunden.example.org/install.php
```

Bei einer Unterverzeichnis-Installation ergänzen Sie den Unterordner. Der Assistent prüft die wichtigsten PHP-Voraussetzungen und HTTPS.

Tragen Sie ein:

1. Die Datenbankzugangsdaten Ihres Hosters. Servername häufig `localhost`, verbindlich ist die Angabe des Anbieters. Standardport ist 3306.
2. Ein Tabellenpräfix, beispielsweise `ma_`. Bei weiteren Installationen ein anderes Präfix wählen; eine eigene Datenbank pro Unternehmen ist vorzuziehen.
3. Ihren Unternehmensnamen, Ihren Namen, eine E-Mail-Adresse als Anmeldenamen und ein eigenes Passwort mit 12 bis 72 Zeichen.
4. Das Passwort zur Bestätigung erneut eingeben. In der breiten Ansicht stehen beide Passwortfelder nebeneinander, auf dem Smartphone untereinander.

Klicken Sie auf **„MaStunden installieren“**. Die Datenbank muss bereits existieren; MaStunden erstellt nur die Tabellen. Tabellen eines bereits verwendeten Präfixes werden nicht überschrieben.

### Optional: Einrichtung über FTP / FTPS

Unter „Dateien mit FTP / FTPS einrichten“ aktivieren Sie „FTP-Einrichtung verwenden“. Tragen Sie Server, Port (üblicherweise 21), FTP-Benutzer, FTP-Passwort und den FTP-Ordner ein, in dem `install.php` liegt, beispielsweise `/public_html/mastunden`. Unternehmen und Administratorname sind bewusst leer und müssen selbst eingetragen werden.

FTPS ist vorausgewählt und verschlüsselt Steuerverbindung und Dateiübertragung. Das Serverzertifikat wird geprüft. Einfaches FTP ist optional, überträgt aber Zugangsdaten und Dateien unverschlüsselt. SFTP wird nicht unterstützt. Für diese Option benötigt PHP zusätzlich die Erweiterung **cURL mit FTP- und TLS-Unterstützung**.

MaStunden prüft die Zuordnung des FTP-Ordners, erstellt den privaten Speicher und schreibt die Konfiguration über FTP. Die FTP-Zugangsdaten werden ausschließlich während dieses Installationsaufrufs verwendet und weder in der Konfiguration noch in der Sitzung gespeichert. Nach einem Fehler müssen Sie das FTP-Passwort erneut eingeben.

Der FTP-Zugang muss den übergeordneten Ordner des öffentlichen Webverzeichnisses erreichen. PHP und FTP benötigen passende gemeinsame Rechte: Der private Ordner erhält 0770, die Konfiguration 0640; der Server muss diese Rechte setzen können. Der Assistent prüft Lesen und Schreiben durch PHP. FTP kann eine Hosting-Sperre nicht umgehen. Nach der Einrichtung arbeitet MaStunden direkt mit dem privaten Speicher; ein dauerhafter FTP-Zugang ist nicht erforderlich.

### Schritt 4 – Wiederherstellungsschlüssel aufbewahren

Nach erfolgreicher Einrichtung zeigt MaStunden einmalig einen **Administrator-Wiederherstellungsschlüssel** an. Speichern Sie diesen in Ihrem Passwortmanager. Dieser Schlüssel dient ausschließlich dazu, einen verlorenen Administratorzugang später wiederherzustellen; für die Installation müssen Sie keinen Schlüssel eingeben.

Die Installation erstellt `config.php` mit den Zugangsdaten und dem Speicherpfad. **Diese Datei bei Updates niemals überschreiben.** Der Installationsassistent ist nach erfolgreicher Einrichtung automatisch gesperrt. Entfernen Sie zusätzlich `install.php` per FTP, wenn Sie möchten; für den normalen Betrieb wird diese Datei nicht benötigt.

### Automatisch angelegter Speicher

MaStunden versucht zuerst, neben dem öffentlichen Webverzeichnis einen eigenen privaten Ordner anzulegen. Verhindern Hostingrechte oder `open_basedir` dies, legt es automatisch einen Ordner `mastunden-storage-<zufällige Kennung>` im Programmverzeichnis an und sperrt diesen mit `.htaccess` für direkte Webzugriffe.

Vor Verwendung prüft der Installer eine harmlose Zufallsdatei über HTTPS zunächst auf Erreichbarkeit. Anschließend aktiviert er die Sperre und verlangt für TXT, PDF, JPG, PNG, ZIP, JSON, Protokoll-, Lock- und PHP-Testdateien jeweils HTTP 403. Ohne erfolgreiche Prüfung wird abgebrochen und der Testordner entfernt. Danach erhält der Speicher zusätzlich die Dateirechte 0700. Bei jedem Anwendungsaufruf wird geprüft, ob die Sperrdatei unverändert vorhanden ist.

**Den Speicherordner einschließlich `.htaccess` bei Updates immer erhalten.** Keine zusätzliche Domain oder Alias auf diesen Ordner richten. Bei einem Hostingwechsel oder Änderungen der Webserverkonfiguration neu installieren und den Speicherschutz erneut prüfen; eine lokale Prüfung der Sperrdatei kann geänderte Serverregeln nicht erkennen. Die Konfiguration enthält den automatisch ermittelten Speicherpfad. Änderungen am Server sind auf kompatiblem Shared Hosting nicht erforderlich.

## 4. Erste Einrichtung

### Unternehmen und Logos

Öffnen Sie **Unternehmen**. Hinterlegen Sie Name, Adresse, Ansprechpartner, E-Mail, Telefonnummer, Website, Standort, Bundesland und Zeitzone sowie Links zu Impressum und Datenschutzerklärung.

- **Firmenlogo:** Logo des nutzenden Unternehmens. Es erscheint bei Anmeldung, Navigation und Berichten.
- **Feste Entwicklerkennzeichnung:** „Entwickelt von“ mit dem mitgelieferten IT-Janz-Logo erscheint ausschließlich bei Installation, Anmeldung und im Menü. Diese Kennzeichnung ist nicht über die Anwendung änderbar. Unter Unternehmen lässt sich ausschließlich das eigene Firmenlogo des Kunden hochladen.
- Unterstützt werden PNG und JPG bis 3000 × 3000 Pixel, innerhalb des Uploadlimits. Für eine gute Darstellung sind kleinere, sauber zugeschnittene Logos ausreichend.
- Akzentfarbe ist frei wählbar. Achten Sie auf einen ausreichenden Kontrast zu weißer Schrift.

Das Original-Logo von IT-Janz ist im Paket enthalten. Die Entwicklerkennzeichnung besteht aus „Entwickelt von“ und dem Logo.

### Mitarbeiter anlegen

Unter **Mitarbeiter → Mitarbeiter anlegen** tragen Sie Name, E-Mail, Personalnummer, Rolle, Beschäftigungsbeginn, optionales Beschäftigungsende und jährlichen Urlaubsanspruch ein. Legen Sie ein vorläufiges Passwort fest und übermitteln Sie es persönlich über einen geeigneten sicheren Kanal. Beim ersten Login muss es geändert werden.

MaStunden versendet keine Einladungs-E-Mails. Eine öffentliche Registrierung ist nicht vorhanden.

Alle neuen Konten einschließlich des ersten Administrators starten ohne Arbeitszeitmodell und ohne vorgegebenen Urlaubsanspruch. Ein leeres Urlaubsfeld bedeutet 0 Tage. Sollstunden entstehen erst, wenn Sie unter Mitarbeiter bearbeiten ausdrücklich ein Arbeitszeitmodell hinterlegen. Firma, Administratorname und E-Mail sind im Installer leer.

### Arbeitszeitmodelle und Startsalden

Arbeitszeitmodelle gelten ab einem Datum und bleiben historisch erhalten. Für Teilzeit lassen sich die Stunden je Wochentag einzeln einstellen. Ist der Beschäftigungsbeginn früher als der Start der Nutzung, tragen Sie Arbeitszeiten nach oder buchen Sie einen nachvollziehbaren Startsaldo. Sonst werden nicht erfasste frühere Solltage als Minusstunden berücksichtigt.

Die Korrekturbuchung verwendet **Dezimalstunden**: `1,5` bzw. je nach Browser `1.5` entspricht 1 Stunde 30 Minuten. Berichte zeigen dagegen **Stunden:Minuten**, also `1:30`. Negative Werte ziehen Stunden ab. Fehlerhafte Korrekturen werden durch eine Gegenbuchung berichtigt.

Der Grund-Urlaubsanspruch wird bei der Anlage festgelegt. Abweichungen für einzelne Jahre, anteiliger Urlaub und Übertrag werden als begründete Urlaubskorrektur im betreffenden Jahr gebucht. Ein automatischer Verfall oder eine automatische anteilige Berechnung findet nicht statt.

### Feiertage und Hinweise

Feiertage werden unter **Unternehmen** für den jeweiligen Standort eingetragen. Das Bundesland allein erzeugt noch keinen Feiertagskalender. Prüfen und pflegen Sie die konkreten Feiertage jährlich. Änderungen an Feiertagen eines gesperrten Monats erfordern zuerst dessen Wiederöffnung.

Pausen- und Ruhezeithinweise sind konfigurierbare Prüfhilfen. Sie ziehen keine Pausen automatisch ab. Gesetzliche und betriebliche Regeln müssen zum Unternehmen passen. Die Software ist nicht als rechtlich zertifiziertes Zeiterfassungssystem ausgewiesen.

„Sonstige Abwesenheit“ kann vor ihrer ersten Verwendung umbenannt und mit oder ohne Zeitgutschrift konfiguriert werden. Eine bereits verwendete Regel wird nicht rückwirkend umdefiniert.

## 5. Kurzanleitung für Mitarbeiter

- **Übersicht:** Arbeit starten, Pause beginnen, Pause beenden und Arbeit beenden. Start und Ende werden minutengenau gespeichert. Ein offener Timer bleibt auch nach Abmeldung oder Schließen des Browsers bestehen.
- **Arbeitszeiten:** Fehlende Zeiten manuell nachtragen. Pro Tag sind mehrere Blöcke und pro Block mehrere Pausen möglich. Pausen müssen vollständig, zeitlich sortiert und innerhalb des Arbeitsblocks liegen.
- **Nachtschichten:** Beginn und Ende jeweils mit dem tatsächlichen Datum eingeben. Die Zeit wird auf die betroffenen Kalendertage und Monate verteilt.
- **Zeitumstellung:** Eine nicht existierende Frühlings-Uhrzeit wird abgewiesen. Bei der doppelten Stunde im Herbst unter „Zeitumstellung“ den richtigen UTC-Abstand auswählen: in Deutschland gewöhnlich +02:00 oder +01:00. Die Berechnung verwendet die tatsächlich vergangene Zeit.
- **Abwesenheiten:** Urlaub, Freizeitausgleich und sonstige Abwesenheiten beantragen. Krankheit wird als Meldung unmittelbar erfasst. Keine Diagnose erforderlich. Ganze, halbe oder stundenweise Tage sind möglich; stundenweise Angaben gelten für jeden ausgewählten Tag.
- **Dokumente:** PDF, JPG oder PNG mit Titel, Kategorie und optionalem Abwesenheitsbezug hochladen. Rückmeldungen erscheinen dort und in den Mitteilungen. Downloads erfordern eine Anmeldung.
- **Monatsberichte:** Einen abgeschlossenen Monat prüfen, als PDF/CSV exportieren und zur Prüfung einreichen. Offene Timer und noch nicht entschiedene Abwesenheitsanträge müssen zuvor geklärt werden.
- **Mein Konto:** Passwort ändern. Nach einer Stunde ohne Aktivität ist eine erneute Anmeldung notwendig.

Mitarbeiter sehen nur ihre eigenen Daten. Ein Mitarbeiter kann offene Abwesenheitsanträge zurückziehen. Freigegebene Abwesenheiten werden durch Administratoren storniert.

## 6. Kurzanleitung für Administratoren

Unter **Freigaben** entscheiden Sie über Abwesenheitsanträge und öffnen eingereichte Monatsberichte. Krankheit erscheint als bereits erfasste Meldung im Abwesenheitsbereich.

Unter **Arbeitszeiten**, **Abwesenheiten**, **Dokumente** und **Monatsberichte** wählen Sie den betreffenden Mitarbeiter. Im Dokumentenbereich gibt es zusätzlich eine Ansicht für alle Mitarbeiter.

Ein Monatsabschluss folgt dem Ablauf **offen → eingereicht → freigegeben**. Eingereichte und freigegebene Monate sind für Änderungen gesperrt, auch für Administratoren. Administratoren geben sie mit Begründung zurück oder öffnen sie erneut. Die Freigabe speichert einen festen Berichtsstand mit den Arbeitsblöcken und Pausen.

Korrekturen an fremden Arbeitszeiten benötigen eine Begründung. Änderungen werden mit Person und Zeitpunkt sowie vorherigen und neuen Werten protokolliert. Das Protokoll ist in der Anwendung nicht editierbar. Es ersetzt keine extern manipulationssichere Archivlösung.

PDF-Exporte enthalten Monatszettel und bei vorhandenen Arbeitszeiten einen Einzelnachweis. Der Teamexport erstellt mehrere Monatszettel in einer PDF; der Gesamtbericht zeigt die Monatssummen nebeneinander. CSV verwendet Semikolon, UTF-8 und Minutenwerte für die Weiterverarbeitung. Maximal 250 Mitarbeiter pro Export; bei Hosting-Laufzeitgrenzen kleinere Gruppen auswählen.

Ganztägige Abwesenheiten und Arbeitszeiten dürfen sich nicht überschneiden. Halbtägige und stundenweise Gutschriften können mit geleisteter Arbeit kombiniert werden; prüfen Sie dabei eine korrekte betriebliche Anrechnung. Freizeitausgleich erzeugt keine zusätzliche Zeitgutschrift und vermindert damit das Stundenkonto um die ausfallende Sollzeit.

## 7. Backup erstellen

1. Öffnen Sie **Backup & Wiederherstellung**.
2. Wählen Sie ein starkes Backup-Passwort mit mindestens 12 Zeichen.
3. Starten Sie das vollständige Backup und lassen Sie die Seite geöffnet.
4. MaStunden verarbeitet Tabellen in Abschnitten und Dateien einzeln. Währenddessen sind normale Zugriffe gesperrt, damit keine Mitarbeiterdaten zwischen den Schritten geändert werden.
5. Laden Sie die fertige ZIP-Datei herunter. Bewahren Sie Archiv und Passwort getrennt und außerhalb des Webservers auf.

Das Archiv ist AES-256-verschlüsselt und enthält Datenbanktabellen, hochgeladene Dokumente, Logos, Einstellungen und Prüfsummen. Es enthält auch Passwort-Hashes und personenbezogene Daten; behandeln Sie es vertraulich. Eine passwortgestützte Integritätsprüfung wird bei der Wiederherstellung durchgeführt.

Datenbankzugangsdaten und der absolute Speicherpfad bleiben in `config.php` und sind nicht Bestandteil des Anwendungsbackups. Sichern Sie diese Datei für einen vollständigen manuellen Umzug separat und geschützt. Das Programm selbst bewahren Sie als Installationspaket auf.

Es gibt keine automatischen Backups ohne zuverlässigen externen Zeitgeber. Richten Sie einen regelmäßigen manuellen Ablauf ein. Fertige temporäre Backup-Dateien auf dem Webserver werden nach 24 Stunden beim nächsten Administrator-Seitenaufruf entfernt; die Bereinigung ist ohne Cronjob nicht minutengenau garantiert.

## 8. Wiederherstellung und Serverumzug

### Wiederherstellung im Browser

1. Verwenden Sie ausschließlich eigene, vertrauenswürdige MaStunden-Backups aus **MaStunden 1.0.0, 1.0.1, 1.0.2, 1.0.3 oder 1.0.5**. Diese Versionen verwenden das kompatible Datenbankschema 1.
2. Wählen Sie das ZIP-Archiv und dessen Passwort aus.
3. Legen Sie zusätzlich ein Passwort für das automatisch erstellte Sicherheitsbackup des aktuellen Zustands fest.
4. Geben Sie `WIEDERHERSTELLEN` ein und starten Sie den Ablauf.
5. MaStunden sichert zunächst den aktuellen Zustand, prüft das Archiv und importiert es in vorläufige Tabellen. Die bestehenden Tabellen bleiben bis zur abschließenden Bestätigung erhalten.
6. Laden Sie das angebotene Sicherheitsbackup herunter.
7. Bestätigen Sie zuletzt mit `DATEN ERSETZEN`. Die Tabellen werden zusammen ausgetauscht. Alle bisherigen Sitzungen werden ungültig.
8. Melden Sie sich mit einem Konto **aus dem eingespielten Backup** an. Auch der Administrator-Wiederherstellungsschlüssel stammt anschließend aus diesem Backup.

Nach einem normalen Abbruch vor dem Tabellentausch bleibt die vorhandene Datenbank erhalten. Bei einer unterbrochenen Abschlussphase bietet die Anwendung eine Bereinigung an. Den privaten Status `maintenance.json` nicht manuell löschen, solange der Zustand unklar ist.

### Archive über dem Hosting-Uploadlimit

Laden Sie das verschlüsselte Archiv per FTP unter dem Namen `incoming-backup.zip` in den **privaten** Speicherordner hoch. Aktivieren Sie im Wiederherstellungsformular „Statt Upload … verwenden“. Die Datei darf ausschließlich im geschützten Speicher liegen, niemals in einem öffentlich abrufbaren Ordner. Entfernen Sie die eingehende Datei nach erfolgreicher Wiederherstellung per FTP.

Die Browser-Auswahl ist auf 512 MB begrenzt, zusätzlich gelten die oft deutlich kleineren PHP-Uploadlimits. Per FTP bereitgestellte Archive dürfen maximal 2 GB groß sein; der entpackte Inhalt ebenfalls maximal 2 GB. Ein einzelner Sicherungsbestandteil darf bei der Wiederherstellung höchstens 64 MB groß sein. Größere Installationen benötigen einen vom Hoster durchgeführten Datenbank-/Dateiumzug. Die Sicherung ist für kleine und mittlere Teams gedacht; prüfen Sie größere Bestände mit den konkreten Hostinglimits.

### Umzug auf einen neuen Webserver

Installieren Sie MaStunden 1.0.7 auf dem neuen Server frisch mit eigener Datenbank und eigenem Präfix. Der private Ordner wird automatisch erstellt. Spielen Sie anschließend das Backup ein. Die neuen Datenbankzugangsdaten und der neue Speicherpfad bleiben bestehen, während Unternehmensdaten, Benutzer und Dokumente aus dem Backup übernommen werden.

Öffnen Sie danach Stichproben von Monatsberichten und Dokumenten, prüfen Sie HTTPS und Unternehmensangaben. Erst nach erfolgreicher Kontrolle sollte die alte Installation außer Betrieb gehen.

## 9. Updates

Vor jedem Update:

1. Verschlüsseltes Anwendungsbackup erstellen, herunterladen und Passwort sichern.
2. Vorhandene Programmdateien per FTP herunterladen; `config.php` separat geschützt sichern.
3. Laufende Erfassungen und geplante Arbeiten berücksichtigen und ein Wartungsfenster vereinbaren.

Zum Schutz vor gemischten Programmversionen kann vor dem Upload einer künftigen Version ein normales Backup gestartet und dessen Seite vor Abschluss geschlossen werden. Dadurch bleibt MaStunden im Wartungsmodus. Aktualisieren Sie dann nur die Programmdateien. Anschließend unter Backup & Wiederherstellung den angefangenen Sicherungsvorgang abbrechen. Das vorher vollständig heruntergeladene Backup bleibt Ihre Rückfallsicherung.

**Nie überschreiben:** `config.php` und der private Speicherordner. `install.php` wird für Updates nicht erneut ausgeführt.

Version 1.0.7 verwendet unverändert Schema-Version 1. Beim Update von 1.0.0, 1.0.1 oder 1.0.2 werden nur Programmdateien ersetzt; `config.php` und der vorhandene private Ordner bleiben erhalten. Es ist keine Neuinstallation und keine Datenbankmigration notwendig. Das initiale Schema wird im Installer angelegt; eine bereits installierte Version wird nicht stillschweigend verändert. Zukünftige Updates mit Schemaänderung müssen eigene versionierte Migrationen und passende Updatehinweise mitbringen. Eine universelle Migration unbekannter zukünftiger Versionen ist nicht enthalten.

Bei einem fehlgeschlagenen Update die gesicherten Programmdateien zurückspielen. Wurde auch das Schema geändert, verwenden Sie zusätzlich das passende Vorab-Backup nach den Updatehinweisen. Ein Datenbankbackup darf nur mit einer dazu kompatiblen Programmversion wiederhergestellt werden.

## 10. Datenschutz, Aufbewahrung und Betrieb

Administratoren können Konten deaktivieren. Historische Arbeitszeiten bleiben erhalten. Es gibt bewusst keinen unbestätigten Sammellöschknopf für Mitarbeiter mit allen Arbeitszeit- und Prüfungsdaten.

Für Dokumente kann unter **Unternehmen** eine Aufbewahrungsfrist in Tagen festgelegt werden. `0` bedeutet: keine automatische Löschfreigabe. Die Bereinigung wird manuell mit Begründung ausgelöst und betrifft nur erledigte oder zurückgewiesene Dokumente. Prüfen Sie vorher bestehende Aufbewahrungspflichten. Arbeitszeiten, Monatsabschlüsse und Prüfprotokolle haben keine pauschale automatische Löschfrist.

Die Anwendung enthält keinen Virenscanner. Dateiformate werden geprüft, Dokumente werden nur als authentifizierter Download ausgeliefert und im geprüften geschützten Speicher abgelegt. Eine zusätzliche Malwareprüfung durch den Hoster kann sinnvoll sein. Hochgeladene Unterlagen nicht unbesehen öffnen.

Bei Uploads und Sicherungen gelten auch Speicherplatz- und Laufzeitgrenzen des Hosters. Prüfen Sie regelmäßig Backups durch eine Wiederherstellung in einer separaten Testinstallation. Ein heruntergeladenes Archiv allein ist noch kein nachgewiesener Wiederherstellungserfolg.

## 11. Hilfe bei Problemen

**„Privaten Speicher nicht automatisch anlegen“:** Auch der automatisch geprüfte Hosting-Speicher konnte nicht eingerichtet werden. PHP-Schreibrechte, cURL und die Wirksamkeit der `.htaccess`-Sperre beim Hoster prüfen lassen. Sie müssen keinen Speicherpfad in MaStunden eingeben.

**„Datenbankverbindung … fehlgeschlagen“:** Servername, Port, Datenbankname, Benutzer und Passwort prüfen. Der Benutzer muss die benötigten Tabellenrechte besitzen. Bei einer erneuten Einrichtung ein noch unbenutztes Präfix verwenden.

**Weiße Seite / Fehler 500:** PHP-Version und Erweiterungen im Hostingpanel prüfen. Manche Hoster haben unterschiedliche PHP-Versionen je Subdomain. PHP-Fehlerprotokoll des Hosters ansehen. Interne Fehlercodes stehen zusätzlich im privaten `errors.log`; dort werden keine Dokumentinhalte oder Zugangspasswörter protokolliert.

**HTTPS wird nicht erkannt:** Mit `https://` aufrufen. Bei Reverse-Proxy-Hosting muss der Hoster die HTTPS-Information korrekt an PHP weiterreichen. Die Produktivinstallation nicht mit dem Entwicklungs-Testschalter betreiben.

**Upload schlägt fehl:** Anwendungsgrenze, `upload_max_filesize`, `post_max_size` und freien Speicher vergleichen. Die tatsächlich nutzbare Grenze ist die kleinste davon. Große Backups alternativ per FTP privat bereitstellen.

**Monat lässt sich nicht bearbeiten:** Unter Monatsberichte zuerst mit Begründung zurückgeben oder wieder öffnen. Auch rückwirkende Arbeitszeitmodelle und Feiertage beachten diese Sperre.

**Backup bleibt stehen:** Seite geöffnet lassen und gegebenenfalls „Nächsten Schritt“ anklicken. Nach Ablauf der Sitzung erneut als Administrator anmelden und den Vorgang kontrolliert abbrechen und neu starten. Nicht einzelne Wartungsdateien löschen.

**Mitarbeiter hat Passwort vergessen:** Administrator setzt unter Mitarbeiter bearbeiten ein neues vorläufiges Passwort. Der Mitarbeiter muss es beim nächsten Login ändern.

**Administrator hat Passwort vergessen:** Auf der Login-Seite „Passwort vergessen?“ wählen und E-Mail, Administrator-Wiederherstellungsschlüssel und neues Passwort eingeben. Danach wird ein neuer einmalig sichtbarer Wiederherstellungsschlüssel ausgegeben. Ohne diesen Schlüssel ist die Wiederherstellung über die Oberfläche nicht möglich; ein weiterer Administrator kann das Konto zurücksetzen.

## 12. Vor dem ersten produktiven Einsatz

Die dokumentierten Prüfungen verwenden synthetische Testdaten. Prüfen Sie vor der Nutzung die Voraussetzungen Ihres konkreten Hostingtarifs und führen Sie eine Testanmeldung sowie eine Sicherung durch.

Prüfen Sie nach Installation mit zwei eigenen Testmitarbeitern: getrennte Ansichten, Zeiterfassung und Pausen, Upload/Download, Monatsfreigabe, PDF-/CSV-Ausgabe, Smartphone-Bedienung und Backup/Wiederherstellung. Testen Sie dabei die tatsächlich verwendeten Browser Chrome, Firefox, Safari oder Edge auf Ihren Geräten. Der lokale visuelle Test erfolgte im Codex-In-App-Browser bei Desktop- und Smartphone-Größen; separate vollständige Tests aller Browser wurden nicht durchgeführt.

SMTP-Versand, automatische Backup-Zeitpläne, Projekt-/Kundenabrechnung, GPS-Erfassung, gemeinsame SaaS-Mandantenverwaltung und Lohnabrechnung sind nicht Bestandteil dieser Version. Die im Auftrag optional genannten E-Mails und automatischen Backups werden nicht vorausgesetzt. Für weitere Unternehmen verwenden Sie getrennte Installationen mit eigenen privaten Ordnern und vorzugsweise eigenen Datenbanken.

## Änderungen in Version 1.0.5

Keine voreingestellten Arbeitszeitmodelle oder Urlaubstage. Festes IT-Janz-Logo an den drei vorgesehenen Stellen. Abwesenheitsanträge können ohne Begründung freigegeben oder abgelehnt werden. Andere begründungspflichtige Korrekturen bleiben unverändert. Vereinheitlichte Dropdowns und Textfelder; überarbeitete mobile Formulare, Kennzahlen und Navigation. Bestehende Daten aus alten Backups behalten ihre bisherigen Arbeitszeitmodelle und Urlaubsansprüche.

## Darstellung in Version 1.0.5

Die Versionsnummer wird ausschließlich unter Backup & Wiederherstellung angezeigt. Das Menü trägt den Produktnamen MaStunden ohne zusätzlichen Unternehmensnamen. Der wiederholte Seitenfuß entfällt. Firmenlogos werden proportional in begrenzten Flächen dargestellt; das feste Entwicklerlogo steht unter „Entwickelt von“. Für das Update nur index.php, lib/ und assets/ übertragen; config.php und den geschützten Speicher erhalten.

## Änderungen in Version 1.0.6: Rücknahme und Löschung

Im Änderungsprotokoll stehen Begründungen direkt sichtbar oberhalb der aufklappbaren Details. Gelöschte Zeiteinträge und Dokumente sowie stornierte Abwesenheiten sind über Zeitpunkt, Aktion und Datensatznummer nachvollziehbar. Ältere Einträge ohne gespeicherte Begründung werden entsprechend gekennzeichnet.

Mitarbeiter können eigene offene oder abgelehnte Abwesenheitsanträge mit Begründung zurückziehen. Der Status lautet danach „Zurückgezogen / storniert“. Ein bereits zurückgezogener Antrag kann nicht erneut entschieden werden. Die frühere Ablehnung bleibt im Protokoll nachvollziehbar, solange das Konto nicht endgültig gelöscht wird.

Unter **Mitarbeiter → Bearbeiten → Mitarbeiter dauerhaft löschen** kann ein Administrator ein Konto einschließlich zugehöriger Zeiten, Abwesenheiten, Dokumente, Modelle, Monatsabschlüsse, Korrekturen, Mitteilungen und personenbezogener Protokolle löschen. Dafür sind Administratorpasswort, Begründung ohne personenbezogene Angaben, die Bestätigung **MITARBEITER LÖSCHEN** und die Bestätigung zur Prüfung der Aufbewahrungspflichten erforderlich. Das angemeldete eigene Konto und der letzte aktive Administrator sind geschützt. Eine neutral gehaltene Löschbegründung mit Mengenangaben bleibt protokolliert; die gelöschte Person wird dabei nicht benannt.

Alle vorhandenen Sicherungsarchive dieser Installation auf dem Server werden ebenfalls gelöscht, da sie die Person enthalten können. Bereits heruntergeladene Backups, Exporte und Sicherungen des Hostinganbieters werden nicht automatisch entfernt. Eine Wiederherstellung alter Sicherungen kann gelöschte Daten zurückbringen. Diese Kopien und Freitextangaben in anderen Datenbeständen müssen organisatorisch separat geprüft werden. Bei Dateisystemfehlern wird eine unvollständige Dateilöschung gemeldet und beim nächsten Aufruf erneut versucht.

Die Löschfunktion allein ist keine Garantie für die Erfüllung sämtlicher Datenschutzpflichten. Gesetzliche Aufbewahrungspflichten können einer sofortigen Löschung entgegenstehen; in diesem Fall zunächst Konto deaktivieren und den notwendigen Datenbestand klären. Quelle: BfDI, Kurzpapier Nr. 11, Recht auf Löschung: https://www.bfdi.bund.de/SharedDocs/Downloads/DE/DSK/Kurzpapiere/20170829_Kurzpapier_11_RechtaufVergessenwerden.html

Für dieses Update ist keine Datenbankmigration erforderlich. Bestehende Daten werden beim Hochladen der Programmdateien nicht gelöscht; Löschungen erfolgen ausschließlich durch die neue bestätigte Aktion. Frühere als „Abgelehnt“ gespeicherte Stornierungen werden nicht automatisch umgedeutet.

## Version 1.0.7: Mitteilungen und Entwicklerlogo

Die Übersicht zeigt standardmäßig nur ungelesene Mitteilungen. Mit „Gelesene anzeigen“ erscheinen auch bereits gelesene Einträge; „Gelesene ausblenden“ kehrt zur Standardansicht zurück. Die Einträge werden dabei nicht gelöscht. Ältere Mitteilungen sind über die Blätterfunktion erreichbar. Nach „Als gelesen markieren“ kehrt die Anzeige zur Standardansicht zurück. „Entwickelt von“ steht mittig über dem IT-Janz-Logo. Keine Datenbankmigration nötig.
