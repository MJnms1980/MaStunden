# MaStunden – technische Übersicht

PHP 8.3+, serverseitig gerenderte HTML-Oberfläche, CSS und kleines JavaScript ohne Build-Schritt; MySQL mit PDO und vorbereiteten Abfragen. Kein Framework und keine mitgelieferten Drittanbieter-Laufzeitbibliotheken.

## Dateien

- index.php: HTTPS-/Session-Schutz, Authentifizierung, Berechtigungsprüfung, Routing, Downloads und Exporte.
- install.php: Installation einer leeren Instanz; privater Speicher wird automatisch angelegt; config.php wird erst auf dem Server erzeugt.
- lib/core.php: Datenzugriff, CSRF, Rollen, Validierung, Einstellungen und Änderungsprotokoll.
- lib/install_storage.php: automatische Speicheranlage außerhalb des Webverzeichnisses oder HTTPS-verifizierter, webservergesperrter Hosting-Speicher.
- lib/schema.php: Schema-Version 1 und Anwendungsversion.
- lib/time.php: UTC-Zeitberechnung, lokale Tagesgrenzen, Sollmodelle, Zeit-/Urlaubskonten und Monatsstände.
- lib/actions.php / admin.php: Änderungsaktionen mit Transaktionen.
- lib/layout.php / admin_views.php: Ansichten und Formulare.
- lib/reports.php: CSV, Druckansicht, selbstständiger PDF-Writer für deutsche/lateinische Texte und Firmenlogo.
- lib/backup.php: schrittweise AES-ZIP-Sicherung, Integritätsprüfung, Wiederherstellung und Tabellentausch.
- assets/app.css / app.js: responsive Oberfläche und kleine Interaktionen.

## Datenmodell

users → models, entries, absences, closures, documents, adjustments und notifications (jeweils user_id). Dokumente haben optional absence_id. audit protokolliert Aktionen mit actor_id. settings speichert Instanzkonfiguration, holidays den betrieblichen Feiertagskalender und attempts gehashte Anmeldeversuchsgruppen.

Referenzprüfungen erfolgen in den serverseitigen Aktionen. Die Tabellen verwenden keine DB-Fremdschlüssel, damit der gemeinsame atomare Austausch von Tabellen bei der Wiederherstellung unabhängig von einem ursprünglichen Präfix möglich bleibt. Backups sind deshalb ausschließlich als vollständige, vertrauenswürdige Anwendungsarchive einzuspielen; kein manuelles Zusammenmischen einzelner Tabellen.

Benutzer-IDs aus Requests allein verleihen niemals Rechte. Alle personenbezogenen Lese-/Schreibzugriffe werden serverseitig gegen die angemeldete Rolle geprüft. Passwort-Hashes werden bei jedem Zugriff mit der Sitzungssignatur abgeglichen; Deaktivierungen, Passwortänderungen und Wiederherstellungen entwerten bestehende Sitzungen entsprechend.

Alle Anwendungszugriffe verwenden einen Dateilock im instanzspezifischen privaten Ordner. Das verhindert parallel widersprüchliche Schreibvorgänge und schützt den Backup-Ablauf. Diese bewusst einfache Architektur ist für gewöhnliches Shared Hosting und kleine bis mittlere Teams vorgesehen, nicht für einen verteilten Hochlast-Cluster.

Zeitpunkte werden in UTC gespeichert; Sollmodelle und Abwesenheiten beziehen sich auf lokale Kalendertage. Eine Zeitzonenänderung ist nach dem ersten Zeiteintrag über die Oberfläche gesperrt. Monatsfreigaben speichern vollständige Berichtssnapshots einschließlich der betroffenen Zeiteinträge. Die Darstellung des Firmennamens/Logos verwendet bei Exporten die aktuellen Unternehmensangaben.

Das Stundenkonto beginnt am Beschäftigungsbeginn und berücksichtigt Solltage bis einschließlich heute. Ein passender Startsaldo ist bei nachträglicher Einführung zu buchen. Vollständige Monatsberichte enthalten auch die Sollzeiten noch nicht gearbeiteter Tage des ausgewählten Monats.

## Sicherung und Betriebsgrenzen

Archive verschlüsseln jeden ZIP-Eintrag mit AES-256. Ein HMAC über das Manifest und SHA-256-Prüfsummen der Inhalte werden bei Restore geprüft. Die Reihenfolge ist Sicherheitsbackup → Integritätsprüfung → Aufbau vorläufiger Tabellen → Dateiwiederherstellung → zweite Bestätigung → atomarer RENAME TABLE → alte Tabellen entfernen. Eine Statusdatei im privaten Speicher hält den Fortschritt fest.

Die Abschnitte begrenzen Laufzeit und Hauptspeicher, aber große Einzeldateien, umfangreiche Datenbankzeilen oder langsames Hosting können weiterhin Serverlimits erreichen. Im Produkt sind keine automatischen Cron-/SMTP-Jobs und kein Virenscanner enthalten. Für mehrsprachige PDF-Ausgabe außerhalb lateinischer Zeichen wäre ein Unicode-Font-/PDF-Modul zu ergänzen.

## Weitergabe und weitere Unternehmen

Jedes Unternehmen erhält eigene Dateien, config.php, privaten Ordner und Datenbank beziehungsweise eigenes Präfix. Kein gemeinsamer SaaS-Tenant-Dispatcher. Niemals produktive config.php oder private Dateien in ein Verteilungspaket aufnehmen.

Es sind keine externen PHP-/JavaScript-Bibliotheken oder Schriftdateien im Paket enthalten. System-PHP, dessen Erweiterungen und MySQL werden vom Hoster bereitgestellt. Die Produktlizenz und geschäftlichen Bedingungen für eine Vermarktung legt IT-Janz selbst fest; dieses Paket enthält keine vorformulierten Kundenverträge oder erfundenen Logo-Lizenzen.

## Änderung 1.0.1 · 7. Oktober 2026

Installationsschlüssel und manuelle Speicherpfadeingabe entfallen. Das Produktzeichen heißt MaSt. Die beiden Passwortfelder stehen in breiten Ansichten nebeneinander, einschließlich der Passwortänderung im Konto. Schema 1 und bestehende Speicherpfade bleiben unverändert; 1.0.0-Backups können in 1.0.1 wiederhergestellt werden.

## Änderung 1.0.2 · 7. Oktober 2026

Installer ohne vorbelegten Unternehmens- oder Administratornamen. Optionaler temporärer FTP-/FTPS-Zugang mit cURL, verifiziertem TLS ohne Rückfall auf Klartext, Zuordnungsprobe, automatischer privater Ordneranlage und Konfigurationsübertragung. FTP-Daten werden nicht persistiert. Der laufende Betrieb nutzt weiterhin lokale PHP-Dateizugriffe; der Installer prüft deren Nutzbarkeit. Installationslock im temporären PHP-Verzeichnis erlaubt nicht beschreibbare Programmverzeichnisse. Backups der Versionen 1.0.0 bis 1.0.2 sind bei Schema 1 kompatibel.

## Änderung 1.0.3 · 7. Oktober 2026

Fallback für eingeschränktes open_basedir: HTTPS-Zuordnungsprobe vor Aktivierung von Require all denied, danach 403-Prüfung aller verwendeten Dateitypen und Rechte 0700. Kein Einsatz bei fehlgeschlagener Prüfung. Sperrdatei-Prüfsumme wird vor jedem Anwendungsaufruf geprüft. Änderungen an Webserverregeln oder zusätzliche Domain-Aliasse erfordern eine erneute Schutzprüfung. Anwendungsversionskonstante auf 1.0.3 korrigiert; Schema bleibt 1.

## Version 1.0.6

Neuer Abwesenheitsstatus cancelled innerhalb des bisherigen VARCHAR-Felds, daher Schema 1 unverändert. lib/user_delete.php koordiniert gezielte Kontolöschung innerhalb der bestehenden Anwendungstransaktion und Dateisperre. Dokumente und Serverarchive werden zuerst umbenannt, bei Transaktionsabbruch zurückbenannt und nach Commit entfernt; verbliebene erase-Dateien werden beim nächsten Aufruf erneut entfernt. Audit-Verknüpfungen, Akteursbezug, historische user_id-Snapshots und explizite Namens-/E-Mail-Nennungen werden bereinigt. Externe Sicherungen und unstrukturierte personenbezogene Freitexte benötigen eine organisatorische Prüfung.
