# **SEO Monitor \- Keresőoptimalizálás Monitoring Platform**

## **Projekt Leírás**

Laravel alapú SEO monitoring platform Filament v4 admin panellel, amely lehetővé teszi a keresőoptimalizálás eredményeinek követését, automatizált adatgyűjtést és teljesítmény elemzését.

## **Backend (Adatgyűjtés és feldolgozás)**

### **Egyszerű architektúra**

* Laravel framework alapú backend  
* Filament v4 admin panel UI-hoz és adatvizualizációhoz  
* Külső API-k integrációja (kimenő hívások)  
* Nincs saját API végpont (nincs külső hozzáférés szükségessége)

### **Adatbázis (SQLite)**

* **SQLite**: Egyszerű fájl alapú adatbázis  
* Relációs adatok tárolása (users, projects, keywords, positions, reports)  
* JSON mezők komplexebb adatstruktúrákhoz  
* Automatikus backup SQLite fájlokról

### **Automatizált adatgyűjtési folyamatok**

* Laravel Scheduler cron job-okhoz  
* Queue system (database driver) aszinkron feladatokhoz  
* Background worker processes  
* Külső API hívások automatizálása  
* Retry mechanizmus hibás API kérések esetén

### **Értesítési rendszer**

* Laravel Notifications email értesítésekhez  
* Filament admin panelen belüli notifications  
* Email templates egyszerű HTML formátumban

## **Kulcs adatforrások**

### **1\. Keresőmotor pozíciók**

* **Google Search Console API**: Organikus kattintások, impressziók, pozíció adatok  
* **SerpApi vagy hasonló szolgáltatás**: Rank tracking külső API-n keresztül  
* **Kulcsszavak teljesítményének követése**: Pozíció változások, trendek

### **2\. Weboldal teljesítmény**

* **Google Analytics 4 API**: Látogatottság, felhasználói viselkedés adatok  
* **Google PageSpeed Insights API**: Oldalbetöltési sebesség mérések  
* **Core Web Vitals API**: LCP, FID, CLS metrikák gyűjtése  
* **Google Search Console API**: Technical SEO adatok

### **3\. Technikai SEO**

* **Guzzle HTTP client**: Egyszerű weboldal ellenőrzések  
* **XML sitemap parser**: Sitemap validálás és elemzés  
* **Robots.txt analyzer**: Basic robots direktívák vizsgálata  
* **Meta tag extractor**: Title, description, schema markup kinyerése  
* **Google Mobile-Friendly Test API**: Mobilbarát teszt eredmények

## **Főbb funkciók**

### **Automatizált jelentések**

* **Heti/havi összefoglalók**: PDF jelentések Laravel DomPDF-fel  
* **Teljesítmény trendek**: Filament chart widget-ekkel  
* **Kulcsszó pozíció változások**: Táblázatos és grafikus megjelenítés  
* **Filament dashboard**: Testreszabható KPI widget-ek

### **Riasztási rendszer**

* **Email értesítések**: Pozíció változások esetén  
* **Filament notifications**: Admin panelen belüli riasztások

### **ROI mérés**

* **Forgalom kalkuláció**: Becsült organikus forgalom érték számítás  
* **Egyszerű ROI számítás**: Alapvető költség-haszon elemzés  
* **Filament táblázatok**: Teljesítmény metrikák megjelenítése

## **Rendszer tulajdonságok**

### **Egyszerű multi-user rendszer**

* **Felhasználó kezelés**: Laravel auth + Filament user management  
* **Szerepkör-alapú hozzáférés**: Filament roles and permissions  
* **Projekt alapú adatelkülönítés**: User-project kapcsolatok  
* **Filament tenant isolation**: Policy-alapú hozzáférés-szabályozás

### **Monitoring**

* **Scheduled adatfrissítés**: Laravel Scheduler alapú frissítések  
* **Email értesítések**: Laravel Notifications  
* **Filament dashboard**: Real-time widget frissítések  
* **Background job processing**: Database queue driver

### **Alapvető elemzés**

* **Trend számítás**: Egyszerű matematikai összehasonlítások  
* **Pozíció változás tracking**: Historikus adatok elemzése  
* **Filament chart widget-ek**: ApexCharts integráció adatvizualizációhoz

### **Vizualizáció és riportok**

* **Filament dashboard**: Widget alapú áttekintő felület  
* **Filament táblák**: Szűrhető, rendezhető adattáblák  
* **ApexCharts integráció**: Line, bar, pie chart-ok Filament widget-ekben  
* **PDF jelentés generálás**: DomPDF-fel automatizált riportok  
* **Export funkciók**: Excel/CSV export Filament táblákból

## **Kulcsszó management rendszer**

### **Kulcsszó hozzáadás és kategorizálás**

* **Bulk kulcsszó import**: CSV/Excel fájlból Laravel Excel-lel  
* **Filament form-ok**: Kulcsszó hozzáadás és szerkesztés  
* **Kategóriák**: Egyszerű grouposítás (brand, termék, szolgáltatás)  
* **Prioritás beállítás**: Magas/közepes/alacsony szintek  
* **Geo-targeting**: Országos/helyi keresések

### **Kulcsszó import és adatszerzés**

* **Google Search Console API**: Automatikus kulcsszó import  
* **Manuális hozzáadás**: Filament admin felületen keresztül  
* **CSV import**: Tömeges kulcsszó feltöltés  
* **Külső API integráció**: SerpApi vagy hasonló szolgáltatás

### **Pozíció követés**

#### **Historikus adatok**

* **Pozíciótörténet tárolása**: SQLite adatbázisban  
* **Trend számítások**: Egyszerű matematikai összehasonlítások  
* **Filament chart widget-ek**: Pozíció változások megjelenítése  
* **Időszakos összehasonlítások**: Heti/havi riportok

### **Alapvető metrikák**

#### **Forgalom becslés**

* **Google Search Console adatok**: Kattintások és impressziók  
* **Pozíció alapú CTR becslés**: Egyszerű pozíció-CTR táblázat  
* **Organikus forgalom kalkuláció**: Alapvető számítások  
* **Filament táblák**: Metrikák megjelenítése

#### **Egyszerű elemzés**

* **Pozíció változás tracking**: Fel/le mozgás követés  
* **Top 10/Top 3 tracking**: Fontos pozíciók figyelése  
* **Filament filter-ek**: Kulcsszavak szűrése teljesítmény szerint

### **SERP funkciók**

#### **Alapvető SERP elemek**

* **Featured snippets**: Egyszerű yes/no tracking  
* **Top 10 eredmények**: Klasszikus organikus pozíciók  
* **Pozíció típus jelölés**: Organikus vs. featured snippet  
* **Filament badge-ek**: SERP funkciók vizuális jelzése

#### **Kulcsszó kategorizálás**

* **Manuális kategorizálás**: Filament select field-ek  
* **Intent típusok**: Informational, Navigational, Transactional  
* **Egyszerű tagging rendszer**: Laravel polymorphic relations  
* **Filament filter-ek**: Kategória szerinti szűrés

### **Riportolás**

#### **Filament dashboard widget-ek**

* **Top movers widget**: Legnagyobb pozíció változások  
* **Ranking distribution chart**: Pozíció eloszlás pie chart  
* **Stats overview**: Alapvető számok (összes kulcsszó, átlag pozíció)  
* **Recent changes tábla**: Friss pozíció változások listája

#### **Értesítési rendszer**

* **Email értesítések**: Laravel Notifications  
* **Top 3-ba kerülés**: Achievement email-ek  
* **Első oldalról kiesés**: Warning email-ek  
* **Heti összefoglaló**: Scheduled job PDF jelentéssel  
* **Filament notifications**: Admin panel értesítések

### **Egyszerű optimalizálás**

#### **Alapvető javaslatok**

* **Pozíció javítási lehetőségek**: 11-20 pozícióban lévő kulcsszavak  
* **Top 10-be kerülési esélyek**: 4-10 pozíció közötti kulcsszavak  
* **Filament action button-ök**: Gyors műveletek (prioritás váltás, jegyzet)  
* **Export funkciók**: Optimalizálandó kulcsszavak CSV-ben

