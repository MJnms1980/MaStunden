# MaStunden 1.0.7 – Prüfbericht

Stand: 8. Oktober 2026. Dieser Bericht beschreibt Funktionsprüfungen mit synthetischen Daten und enthält keine kundenspezifischen Installationsangaben. Testergebnisse sind keine Garantie für jeden Hostingtarif.

## Geprüfte Funktionen

- Lokale Neuinstallation mit PHP und MySQL; Administrator und weitere Konten ohne automatisch angelegtes Arbeitszeitmodell und ohne vorgegebenen Urlaubsanspruch.
- Serverseitige Rollen- und Eigentümerprüfung, CSRF-Schutz, Sitzungsentwertung nach Kontolöschung bzw. Wiederherstellung.
- Arbeitszeiten, Pausen, Überschneidungsprüfung, explizite Sollzeitmodelle, Abwesenheiten, Monatsfreigaben, PDF- und CSV-Ausgabe.
- Dokumentupload, berechtigter Download und Ablehnung unberechtigter Zugriffe.
- Verschlüsseltes Backup und Wiederherstellung in isolierten Testinstallationen. Kompatibilitätsprüfungen mit älteren Sicherungsformaten sind in den mitgelieferten Testnachweisen gesondert dokumentiert.
- FTP-/FTPS-Installation in einer isolierten Umgebung mit eingeschränkten PHP-Rechten; abgewiesene falsche Zugangsdaten und abweichender Zertifikatsname.
- HTTPS-Schutzprüfung für Speicher im Webverzeichnis: wirksame Sperren akzeptiert, unwirksame Regeln abgewiesen; Testdateien bereinigt.
- Rücknahme abgelehnter Anträge, Begründungen im Änderungsprotokoll, bestätigte Mitarbeiterlöschung und Bereinigung zugehöriger Testdaten.
- Gelesene Mitteilungen standardmäßig verborgen, auf Wunsch einblendbar und wieder ausblendbar.
- Responsive Ansichten bei 320 und 390 Pixel Breite, einheitliche Feldmaße, festes Entwicklerlogo und zentrierte Beschriftung.

## Domainunabhängigkeit

Im ausgelieferten Programm sind keine kundenspezifischen Domains oder Serverzugänge fest hinterlegt. Links sind relativ; Sitzungspfad und Installationspfad werden aus der jeweiligen Umgebung abgeleitet. Installationen im Hauptverzeichnis und Unterverzeichnis sind vorgesehen. Jede Kundeninstallation erhält ihre eigene Konfiguration und ihren eigenen Speicher.

## Grenzen der Prüfung

PHP-Erweiterungen, Datenbankrechte, Zertifikate, FTP-Pfade, Dateirechte und Webserversperren müssen beim jeweiligen Hoster verfügbar sein. Keine Lastprüfung mit hunderten gleichzeitigen Benutzern oder mehrjährigen Großbeständen. Unterschiedliche Mobilbrowser können weitere Darstellungstests erfordern. Datenschutzpflichten, externe Sicherungen und Aufbewahrungsfristen müssen organisatorisch geprüft werden.

Die mitgelieferten Testprotokolle stammen aus unterschiedlichen Entwicklungsständen. Nur die jeweils ausdrücklich genannten Funktionen wurden in diesen Läufen erneut geprüft.
