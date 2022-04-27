# Unveröffentlicht
* Ergänzung einer Paginierung für die Registrierung von Webhooks

# 4.0.0
* Überweisungsinformationen werden nun in den Zusatzfeldern anstelle einer eigenen Tabelle gespeichert
    * **Bitte beachten,** dass bestehende Daten beim Plugin-Update migriert werden. Wir empfehlen daher, dass Plugin-Update über die Konsole auszuführen.

# 3.2.0
* Korrektur der Validierung der AGB-Checkbox im Checkout mit Unzer Zahlungsarten
* Korrektur der Fehler-Logik innerhalb der Zahlungsarten, um das Shopware-Standard-Handling aufzugreifen 
* Kompatibilität zu Shopware 6.4.10.0 hergestellt

# 3.1.0
* Kompatibilität zu EasyCoupon Plugin hergestellt
* Korrektur der Möglichkeit um Kunden zu löschen, die eine gespeicherte Zahlungsart haben
* Korrektur des Zahlungsstatus bei der Weiterleitung zur externen Zahlung
* Verwaltung der Webhooks ergänzt
  * **Bitte beachten,** dass Webhooks neu registriert werden sollten

# 3.0.1
* Korrektur der Weiterleitung beim Löschen der gespeicherten Zahlungsmittel im Fall eines Fehlers
* Korrektur der Darstellung der gespeicherten Zahlungsmittel für SEPA gesichert
* Aktualisierung des Unzer PHP SDK zu Version 1.1.4.0
* Kompatibilität zu PHP 8 hergestellt
* Ergänzung des Recurrence Types für Zahlungen mit Kreditkarte

# 3.0.0
* Hinzufügen der Administrations UI zum Angeben von Rückgabe Gründen
* Erweitern der Routen um Rückgabe Gründe zu übergeben
* Anpassungen am CancelOrderInterface zum übergeben von Rückgabe Gründen
* Bancontact als weitere Zahlungsart hinzugefügt
* Korrektur der Bezahlung mit Installment und Gutscheinen
* Korrektur eines Fehlers der beim Wechseln der Lieferadresse auftritt
* Der Kunde wird nun auch im Unzer Insights Board aktualisiert
* Korrektur der Abwärtskompatibilität zu Shopware 6.3 und tiefer für SEPA Zahlungsarten
* Korrektur der Längenbegrenzung für das Geburtsjahr
+ Korrektur der Überschreibung der Bestellübersicht, damit andere Plugins diese auch modifizieren können
* Herstellung der Kompatibilität zu Shopware 6.4.3.0
* Korrektur der Webhook-Registrierung für mehrere Saleschannels mit unterschiedlichen Zugangsdaten
* Korrektur eines Fehlers bei der Registierung eines PayPal Accounts falls keine E-Mail Adresse übergeben wurde

# 2.0.1
* Kompatibilität mit shopware 6.4.0.0 hergestellt
* Korrektur des SEPA-Mandat Textes im Checkout

# 2.0.0
* Validierung des Geburtsdatums für Unzer Ratenzahlung im Checkout hinzugefügt
* Wechsel auf das neue unzer SDK (https://packagist.org/packages/unzerdev/php-sdk)
* Fehler bei der Darstellung der vererbten Plugineinstellungen behoben

# 1.0.4
* Fehler in Invoice (guaranteed/factoring) für B2B-Kunden korrigiert
* Fehler in der Nachkommastellen-Anzeige in der Administration behoben
* Korrektur der fehlenden Anzeige der Gesamtsumme im Checkout
* Korrektur eines Fehlers in der Administration bei der Bearbeitung einer Bestellung
* Kompatibilität mit shopware 6.3.5.1 hergestellt

# 1.0.3
* Anpassungen des Codestyles und erhöhen der Codequalität
* Korrektur der fehlenden Nachkommastellen Anzeige im Admin für Erstattung und Einzug
* Korrektur von fehlenden Labels in den Plugineinstellungen
* Korrektur der Webhook-Registrierung
* Zahlungsmethoden für Warenkörbe mit einem Wert von Null werden nun deaktiviert

# 1.0.2
* Korrektur der Gutscheinbehandlung

# 1.0.1
* Korrektur der Zahlungsstatus Änderungen

# 1.0.0
* Veröffentlichung
