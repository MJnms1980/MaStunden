# MaStunden 1.0.8 – Prüfbericht

Stand: 23. November 2026.

## Durchgeführte Prüfungen

- PHP-Syntaxprüfung und vollständige Integrationssuite unter PHP 8.3 und PHP 8.4: bestanden.
- Isolierte Neuinstallation, Mitarbeiterrechte, CSRF, Monatsabschlüsse, Dokumentzugriff, PDF/CSV, verschlüsselte Sicherung und Wiederherstellung: bestanden.
- Prozessabbruch vor und nach einem Datenbank-Commit: neue Dateien bzw. bestätigte Löschungen werden entsprechend dem dauerhaften Dateijournal bereinigt.
- Ungültige Upload-Metadaten hinterlassen keine Dateien; Speicherlimit wird durchgesetzt.
- Dokumentrückmeldungen bleiben im Änderungsprotokoll mit Vorher-/Nachherwerten erhalten.
- Mitarbeiterlöschung bewahrt nicht betroffene Audit-Ereignisse und Sicherungsarchive; gelöschte Akteure werden anonymisiert.
- Ausdrücklich bestätigte Archiventfernung funktioniert.
- Parallele Lesezugriffe und blockierende Downloadausgabe halten andere Anfragen nicht mehr mit einer globalen exklusiven Sperre auf.
- Die ausdrücklich konfigurierbare Ausnahme für private SQL-Netze ist für öffentliche IP-Adressen und fremde Hostnamen gesperrt.
- Neuer reproduzierbarer Runner aus einem frischen temporären Verzeichnis mit eigenen Containern und synthetischen Daten unter PHP 8.4 erfolgreich durchgelaufen.

## Grenzen und Betriebsanforderungen

Diese Prüfungen sind keine Sicherheitszertifizierung. Sie ersetzen nicht die Prüfung der Hostingkonfiguration und der tatsächlich installierten PHP-/MySQL-/Systembibliotheksversionen. Eine sichere externe SQL-Verbindung benötigt TLS mit einem passenden Serverzertifikat und CA-Vertrauen. Wenn ein Hoster dies nicht bereitstellt, kann die Anwendung diese Verschlüsselung nicht selbst aktivieren. Die ausdrücklich mögliche Ausnahme für private Hostingnetze bleibt unverschlüsselt und wird unter Backup als Warnung angezeigt.

Die Wirksamkeit von Webserversperren wird für Speicher im Webverzeichnis periodisch über dessen fest konfigurierte HTTPS-Adresse geprüft; eine fehlgeschlagene Prüfung sperrt die Anwendung vorsorglich. Die Adresse muss bei einem Domain-/Pfadwechsel angepasst werden.

Keine umfassenden Lasttests mit großen Unternehmen, keine vollständige Browsermatrix und keine Tests gegen jede PHP-Hauptversion. PHP 8.5/8.6 sind mit dieser Veröffentlichung nicht ausdrücklich freigegeben. Historische Testprotokolle gehören zu den darin genannten Entwicklungsständen.

## Wiederholbare Tests

Siehe entwicklung/TESTS-LESEN.txt und entwicklung/run_tests.py. Die Tests laufen ausschließlich mit synthetischen Daten. Die GitHub-Konfiguration enthält zusätzlich eine Testmatrix für PHP 8.3 und 8.4. Der Erfolg eines entfernten GitHub-Laufs muss anhand seines jeweiligen Status geprüft werden.
