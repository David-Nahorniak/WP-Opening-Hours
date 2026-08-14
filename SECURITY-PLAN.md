# Bezpečnostní analýza a plán oprav — WP Opening Hours 2.3.0

Datum: 2026-08-14
Rozsah: `classes/`, `views/`, `assets/scripts/`, `wp-opening-hours.php`, `functions.php`, `run.php`
Pozn.: submoduly `includes/admin-notice-helper` a `includes/jquery-ui-timepicker` nejsou inicializovány — nebylo možné je auditovat.

---

## 1. Nalezené zranitelnosti

### 1.1 Vysoká závažnost

#### V1 — Stored XSS na frontendu přes názvy svátků / nepravidelných otevíracích dob
- **Root cause (chybějící sanitizace vstupu):**
  - `classes/OpeningHours/Module/CustomPostType/MetaBox/Holidays.php:101` — `name` z `$_POST` uložen bez sanitizace
  - `classes/OpeningHours/Module/CustomPostType/MetaBox/IrregularOpenings.php:82` — totéž
- **Chybějící escaping výstupu:**
  - `views/shortcode/holidays.php`, `views/shortcode/holidays-list.php` — `echo $holiday->getName();`
  - `views/shortcode/irregular-openings.php`, `views/shortcode/irregular-openings-list.php` — `echo $io->getName();`
  - `classes/OpeningHours/Module/Shortcode/Overview.php` — `renderHoliday()` (~r. 246) a `renderIrregularOpening()` (~r. 219) vkládají `$holiday->getName()` / `$io->getName()` přímo do HTML
- **Dopad:** editor (kdokoli s právem editovat `op-set`) může vložit JS, který se vykoná v prohlížeči návštěvníků i administrátorů webu.

#### V2 — XSS přes atributy shortcodů (zranitelnost z role contributor)
- `views/shortcode/is-open.php` — neescapované: `$text`, `$next_string`, `$today_string`, `$title`, `$classes`, `$next_period_classes`, `$before_widget`, `$after_widget`, `$before_title`, `$after_title`
- `views/shortcode/overview.php`, `overview-list.php` — `$title`, `$description` (meta pole setu), `caption_closed` v `periodsMarkup`, `before/after_widget`, `before/after_title`
- `views/shortcode/holidays*.php`, `irregular-openings*.php` — `$title`, `before/after_*`
- Všechny tyto hodnoty lze ovlivnit atributy shortcode (výchozí hodnoty jsou v `defaultAttributes`, tedy přepisovatelné), např. `[op-is-open open_text="<script>…"]`.
- **Dopad:** uživatel s rolí contributor (bez `unfiltered_html`) vloží shortcode do draftu → stored XSS vůči adminovi (preview) i návštěvníkům po publikaci.

#### V3 — XSS v JSON-LD výstupu (`</script>` breakout)
- `views/shortcode/schema.php` — `json_encode($schema, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES)` bez `JSON_HEX_TAG`.
- Hodnoty `schema_attr_name`, `schema_attr_description`, `schema_attr_type` (atributy shortcode) i názvy svátků/IO v `$schema` mohou obsahovat `</script><script>…</script>`.
- **Dopad:** stejné jako V2, navíc přes uložené názvy (stored).

### 1.2 Střední závažnost

#### V4 — Stored XSS v administraci
- `views/ajax/op-set-holiday.php` — `value="<?php echo $this->data['name']; ?>"` (neescapovaný HTML atribut)
- `views/ajax/op-set-irregular-opening.php` — `value="<?php echo $name; ?>"`, `$date`, `$timeStart`, `$timeEnd`
- `classes/OpeningHours/Module/CustomPostType/MetaBox/SetDetails.php:154-158` — `saveData()` ukládá `description`, `alias`, `dateStart`, `dateEnd`, `weekScheme` z `$_POST` bez sanitizace
- `classes/OpeningHours/Fields/FieldRenderer.php` — `renderField()` vypisuje `$value`, `$caption`, `$options`, `$field['description']` a `generateAttributesString()` (klíče i hodnoty atributů) bez escapingu → stored XSS v editaci setu i ve formulářích widgetů
- `SetDetails.php:133` — `$builderUrl` v `href` bez `esc_url` (base64 — nízké riziko, přesto escapovat)
- **Dopad:** eskalace oprávnění — editor může spustit JS v session administrátora.

#### V5 — Chybějící autorizace a nonce u AJAX endpointů
- `classes/OpeningHours/Module/Ajax.php:39-43` — `wp_ajax_op_render_single_period`, `wp_ajax_op_render_single_dummy_holiday`, `wp_ajax_op_render_single_dummy_irregular_opening`:
  - žádné `current_user_can()` — volat může i subscriber
  - žádná nonce kontrola — CSRF (dopad omezen na render HTML fragmentů, přesto opravit)

#### V6 — Chybějící capability kontrola při ukládání meta boxů
- `classes/OpeningHours/Module/CustomPostType/MetaBox/AbstractMetaBox.php:77-97`:
  - chybí `current_user_can('edit_post', $post_id)`
  - chybí kontrola autosave/revize (`DOING_AUTOSAVE`, `wp_is_post_autosave`, `wp_is_post_revision`)
  - nonce action (`$id . '_edit'`) neobsahuje post ID → nonce lze přehrát na jiný set

### 1.3 Nízká závažnost / hardening

- **V7** — `AbstractShortcode::filterAttributes()` (AbstractShortcode.php:170) se **nikdy nevolá** → `validAttributeValues` nejsou vynuceny; `$attributes['template']` apod. nejsou validovány (aktuálně brání LFI až map lookup, ale jde o mrtvý kód a chybějící vrstvu validace).
- **V8** — Žádný PHP soubor (classes, views) nemá `defined('ABSPATH') || exit;` → přímý přístup způsobí fatal error → full path disclosure.
- **V9** — CPT registrace (`CustomPostType/Set.php:89-103`): `public => false`, ale `publicly_queryable => true` a `has_archive => true` → set posty (titulky) potenciálně dostupné na frontendu.
- **V10** — `AbstractWidget` neimplementuje `update()` → výchozí `WP_Widget::update()` ukládá hodnoty formuláře bez sanitizace (spojeno s V1/V2/V4).
- **V11** — `sprintf()` s uživatelským format stringem (`today_format`, `next_format`, `closed_holiday_text` v `Shortcode/IsOpen.php`) → při nadbytku `%s` warning/`ValueError` (PHP 8) → DoS stránky; escapování výstupu řeší V2.
- **V12** — `MetaBox/OpeningHours.php:47` — `$_POST['opening-hours']` bez `array_key_exists` (PHP notice; robustnost).
- **V13** — `extract($this->data['attributes'])` ve všech shortcode views — riziko přepsání proměnných templatu; nahradit explicitními proměnnými nebo `EXTR_SKIP`.
- **V14** — Zastaralý stack: vyžadováno PHP >= 5.3 (EOL), staré jQuery UI timepicker submodule — zvýšit min. verze a aktualizovat závislosti; submoduly inicializovat a auditovat.
- **V15** — `OpeningHours::maybeUpdate()`/`Importer::import()` běží na `wp_loaded` i na frontendu a zapisuje do DB — omezit na admin kontext (robustnost, race condition při souběžných requestech).
- **V16** — Celý plugin: **0 výskytů** `esc_*()` / `sanitize_*()` — systematický problém, řešit globálně, ne bodově.

---

## 2. Plán oprav (prioritizovaný)

### Fáze 1 — Escaping frontendového výstupu (V1, V2, V3)
1. `views/shortcode/holidays.php`, `holidays-list.php`, `irregular-openings.php`, `irregular-openings-list.php`:
   - `echo esc_html($holiday->getName())`, `echo esc_html($io->getName())`
   - classy do `esc_attr()`, titulek `esc_html()`, `before/after_widget|title` přes `wp_kses_post()`
2. `views/shortcode/is-open.php`:
   - `$text`, `$next_string`, `$today_string`, `$title` → `esc_html()`
   - `$classes`, `$next_period_classes` → `esc_attr()`
   - `before/after_*` → `wp_kses_post()`
3. `views/shortcode/overview.php`, `overview-list.php`:
   - `$description` → `wp_kses_post()` (může obsahovat legit HTML) nebo `esc_html()`
   - `dayCaption` → `esc_html()`; `periodsMarkup` escapovat již při skládání v `Shortcode/Overview.php`:
     - `renderHoliday()` / `renderIrregularOpening()` → `esc_html()` na jména, `esc_attr()` na classy
     - `caption_closed` → `esc_html()`
4. `views/shortcode/schema.php`:
   - `json_encode($schema, JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE)` (odstranit `JSON_UNESCAPED_SLASHES`)

### Fáze 2 — Sanitizace vstupů (V1, V4, V7, V10, V11, V12)
1. `MetaBox/Holidays.php::getHolidaysFromPostData()` — `sanitize_text_field()` na `name`; `dateStart`/`dateEnd` validovat regexem `Y-m-d` před `new DateTime`.
2. `MetaBox/IrregularOpenings.php::getIrregularOpeningsFromPostData()` — `sanitize_text_field()` na `name`; validovat `date` (`Y-m-d`) a časy; `array_key_exists` guardy pro pole.
3. `MetaBox/SetDetails.php::saveData()` — sanitizace dle typu pole: `description` → `sanitize_textarea_field()`, text/date → `sanitize_text_field()`, `alias` → `sanitize_title()`, `weekScheme` → whitelist `array('all','even','odd')`; `array_key_exists($this->id, $_POST) && is_array(...)`.
4. `AbstractWidget` — přidat `update($new_instance, $old_instance)` se sanitizací per-field (text → `sanitize_text_field()`, checkbox/select → whitelist), čímž se ošetří i cesta widget→shortcode.
5. `AbstractShortcode::renderShortcode()` — zavolat `$attributes = $this->filterAttributes($attributes);` po `shortcode_atts()` (aktivuje `validAttributeValues` whitelisty vč. `template`).
6. `Shortcode/IsOpen.php` — u `sprintf` format stringů z atributů povolené placeholdery omezit (např. `preg_match('/%[0-9]\$s/', …)` povolit jen `%1$s`–`%4$s`), výstup escapovat (Fáze 1).
7. `MetaBox/OpeningHours.php::saveData()` — `array_key_exists('opening-hours', $_POST) && is_array(...)` guard; `$weekday` → `absint()` + whitelist 0–6.

### Fáze 3 — Autorizace a CSRF (V5, V6)
1. `Module/Ajax.php`:
   - na začátek každého handleru: `check_ajax_referer('op_ajax_nonce')` + `current_user_can('edit_posts')` jinak `wp_die(-1, 403)`
   - nonce vystavit přes `wp_localize_script` vedle `ajax_url` a doplnit `nonce` parametr do `assets/scripts/Periods.js`, `Holidays.js`, `IrregularOpenings.js`
2. `AbstractMetaBox::saveDataCallback()`:
   - `current_user_can('edit_post', $post_id)` jinak return
   - přeskočit při `defined('DOING_AUTOSAVE') && DOING_AUTOSAVE`, `wp_is_post_autosave($post_id)`, `wp_is_post_revision($post_id)`
   - nonce action rozšířit o post ID: `$this->id . '_edit_' . $post_id` (pozor: `nonceField()` nemá post — předávat `$post->ID` z `renderMetaBox()`)

### Fáze 4 — Escaping administrace (V4)
1. `views/ajax/op-set-holiday.php`, `op-set-irregular-opening.php`, `op-set-period.php` — všechny hodnoty v `value="…"` → `esc_attr()`.
2. `classes/OpeningHours/Fields/FieldRenderer.php`:
   - input `value`, `id`, `name`, atributy z `generateAttributesString()` → `esc_attr()`
   - `$caption`, option captions, datalist → `esc_html()`; textarea hodnota → `esc_textarea()`
   - `$field['description']` → `wp_kses()` s povolenými `<code><br><a>`
3. `SetDetails.php::renderMetaBox()` — `esc_url($builderUrl)`, `absint($post->ID)`.
4. `views/meta-box/*.php` — doplnit escaping tam, kde chybí (set ID, weekday názvy apod.).

### Fáze 5 — Hardening (V8, V9, V13, V14, V15)
1. `defined('ABSPATH') || exit;` na začátek všech PHP souborů (classes + views).
2. CPT argumenty: odstranit `publicly_queryable => true` a `has_archive => true` (nebo nastavit `false`), ověřit, že nepřeruší nic ve frontendu.
3. Views: nahradit `extract()` explicitním přiřazením proměnných (případně `EXTR_SKIP` jako mezikrok).
4. `composer.json`/`package.json`: min. PHP 7.4+, aktualizovat/inicializovat submoduly a projít je auditem (`yarn audit`, PHPCS security sniffs).
5. `maybeUpdate()` — spouštět jen v `is_admin()` kontextu.

### Fáze 6 — Verifikace
1. `composer require --dev squizlabs/php_codesniffer wp-coding-standards/wpcs pheromone/phpcs-security-audit` → spustit PHPCS se sniffs `WordPress.Security.EscapeOutput`, `WordPress.Security.ValidatedSanitizedInput`, `WordPress.Security.NonceVerification` — dojit k nule findingů v plugin kódu.
2. PHPUnit: existující testy v `tests/` rozšířit o:
   - sanitizaci `getHolidaysFromPostData` / `getIrregularOpeningsFromPostData` (injektovaný payload `<script>` nesmí projít do entity)
   - `filterAttributes` whitelist
3. Manuální exploit scénáře k ověření (před/po):
   - `[op-is-open open_text="</span><script>alert(1)</script>"]` jako contributor
   - svátek s názvem `"><script>alert(1)</script>` → frontend holidays shortcode, overview, admin meta box
   - `[op-schema schema_attr_name="</script><script>alert(1)</script>"]`
   - AJAX volání bez nonce / jako subscriber → očekáváno 403
4. Zvážit responsible disclosure: verzi bumpnout na 2.3.1 / 2.4.0 a v changelogu zmínit bezpečnostní opravy.

---

## 3. Přehled souborů k úpravě (dle fází)

| Soubor | Fáze | Změna |
|---|---|---|
| `views/shortcode/*.php` (8 souborů) | 1 | escaping výstupu |
| `classes/OpeningHours/Module/Shortcode/Overview.php` | 1 | escaping v `renderHoliday`/`renderIrregularOpening` |
| `classes/OpeningHours/Module/Shortcode/IsOpen.php` | 1, 2 | escaping, format-string validace |
| `classes/OpeningHours/Module/Shortcode/AbstractShortcode.php` | 2 | volání `filterAttributes` |
| `classes/OpeningHours/Module/CustomPostType/MetaBox/Holidays.php` | 2 | sanitizace POST |
| `classes/OpeningHours/Module/CustomPostType/MetaBox/IrregularOpenings.php` | 2 | sanitizace POST |
| `classes/OpeningHours/Module/CustomPostType/MetaBox/SetDetails.php` | 2, 4 | sanitizace POST, `esc_url` |
| `classes/OpeningHours/Module/CustomPostType/MetaBox/OpeningHours.php` | 2 | guardy, whitelist weekday |
| `classes/OpeningHours/Module/Widget/AbstractWidget.php` | 2 | `update()` sanitizace |
| `classes/OpeningHours/Module/Ajax.php` | 3 | nonce + capability |
| `assets/scripts/{Periods,Holidays,IrregularOpenings}.js` | 3 | posílat nonce |
| `classes/OpeningHours/Module/CustomPostType/MetaBox/AbstractMetaBox.php` | 3 | capability, autosave, nonce s post ID |
| `views/ajax/*.php` (3 soubory) | 4 | `esc_attr` |
| `classes/OpeningHours/Fields/FieldRenderer.php` | 4 | escaping všech výstupů |
| všechny `.php` v `classes/` a `views/` | 5 | ABSPATH guard |
| `classes/OpeningHours/Module/CustomPostType/Set.php` | 5 | CPT argumenty |
| `classes/OpeningHours/OpeningHours.php` | 5 | `maybeUpdate` jen admin |

## 4. Rizika a poznámky k implementaci

- **Zpětná kompatibilita escapingu:** `before_widget`/`after_widget` u widgetů obsahují HTML z šablon (`wp_kses_post` je bezpečná volba — typické sidebar markupy projdou). U shortcode atributů zvažte přísnější whitelist.
- **Nonce s post ID:** vyžaduje změnu signatury `nonceField()`/`generateNonceValues()` — sjednotit na `nonceField($postId)`.
- **Existující uložená data:** escaping na výstupu (ne při uložení) zajistí ochranu i pro již napadená data v DB; sanitizace při ukládání je druhá vrstva.
- **`filterAttributes` aktivace:** ověřit, že booleanské atributy z widgetů (`'1'` vs `true`) projdou whitelisty — jinak rozšířit povolené hodnoty.
- Po každé fázi spustit PHPUnit (`phpunit.xml` existuje) a PHPCS security sniffs.
