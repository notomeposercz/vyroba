Projekt, který na základě dat z CSV souboru reflektuje vytvořené objednávky, jejich stav a vytížení realizace. Vizuálně zobrazujeme v kalendáři.

Soubor CSV (Objednavka prijata CSV - IMAGE CZECH.csv):
Kód objednávky = sloupec "Číslo dokladu"
Typy technologií = sloupec “Katalog”
Počet kusů = sloupec “Množ.hlavní”
Vytvoření objednávky = sloupec "Datum vystavení"
Zboží objednáno = sloupec "Datum vystavení OV"
Zboží naskladněno = sloupec "Datum vytvoření DLP"

Stavy zboží:
- není skladem
- objednáno
- skladem

Aplikace zobrazuje vytvořené objednávky jako čekající do doby, než se neschválí náhled.
Možnost ručně změnit stav náhledu, při změně stavu se zaznamená aktuální datum změny stavu.

Stavy náhledu:
- není vytvořen
- odesláno na klienta
- schváleno

Po změně stavu náhledu na "schváleno" se tato objednávka přesune vizuálně do kalendáře. (stav zboží není rozhodující pro zobrazení objednávky v kalendáři)
- začátek vizuálního zobrazení v kalendáři je den schválení náhledu
- doba trvání je standardně +14dní
- datum požadované expedice = konec vizuálního zobrazení objednávky v kalendáři
- datum požadovaní expedice se může ručně upravovat

Katalogová čísla udávají jakou technologií bude zboží realizováno.

Možnost ručně označit objednávku jako "hotovo" = přesun do hotových zakázek, informace e-mailem na obchodníka uvedeného u objednávky.
