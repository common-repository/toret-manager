<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<div class="wrap">

    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

    <div>
        <a href="https://play.google.com/store/apps/details?id=com.toretwoocommercemanager" target="_blank">
            <img alt="Toret Plugins" src="<?php echo esc_url(WP_PLUGIN_URL .'/toret-manager/admin/img/Toret-WooCommerce-Manager.png')?>" class="toret-plugins-admin-img"
        />
        </a>
    </div>

    <div class="toret-admin-buttons">
        <a class="toret-muj-ucet" href="https://toret.cz/muj-ucet/" target="_blank">ZOBRAZIT MŮJ ÚČET</a>
        <a class="toret-muj-ucet" href="https://toret.cz/dokumentace-k-pluginum/" target="_blank">DOKUMENTACE</a>
        <a class="toret-podpora" href="https://toret.cz/podpora/" target="_blank">KONTAKTOVAT PODPORU</a>
    </div>

    <div class="toret-plugins-admin-table">
        <h2>Vylepšete si e-shop pomocí našich dalších pluginů</h2>
        <p>Vybírat můžete z pluginů pro dopravu, fakturaci, platební brány nebo například srovnávače zboží. Použitím našich pluginů vylepšíte automatizaci e-shopu, kdy nebudete muset ručně přepisovat údaje z webu do služeb třetích stran (například dopravní služby, fakturační systémy) nebo automaticky párovat platby poslané na účet. Nabídněte zákazníkům nové, komfortnější platební možnosti pomocí populárních metod, mezi které patří kromě platby kartou, QR kódy, rychlé bankovní převody nebo internetové peněženky Apple Pay a Google Pay. Prohlédněte si nabídku našich rozšíření pro WooCommerce obchody.</p>
        <h3>Pluginy pro dopravu</h3>
    <table>
    <thead>
    <tr>
        <th>Jméno pluginu</th>
        <th>Popisek</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
        <?php /*    KOPÍROVAT    */ ?>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/balikobot/" target="_blank" class="toret-plugins-admin-link">Balíkobot</a>
            </td>
            <td>
                Oficiální WordPress plugin pro WooCommerce, který e-shop propojuje se službou Balíkobot.cz.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/balikobot/" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woo-balik-na-postu-sk/?utm_source=administrace&utm_medium=ppd&utm_campaign=balik-sk-posta" target="_blank" class="toret-plugins-admin-link">Balík na Slovenskou poštu</a>
            </td>
            <td>
                Přidejte do svého e-shopu možnost doručení na Slovenskou poštu. Plugin načte pobočky pošty a umožní zákazníkovi si z nich vybrat na stránce pokladny ve WooCommerce.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woo-balik-na-postu-sk/?utm_source=administrace&utm_medium=ppd&utm_campaign=balik-sk-posta" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/ceska-posta/?utm_source=administrace&utm_medium=ppd&utm_campaign=cp" target="_blank" class="toret-plugins-admin-link">Česká pošta</a>
            </td>
            <td>
                Plugin pro WooCommerce, který integruje dopravní metody České pošty. S pluginem nastavíte služby Balíkovna, Balík Na poštu a Balík Do ruky, Cenný balík, EMS, Doporučený balíček, Obyčejný balík a další.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/ceska-posta/?utm_source=administrace&utm_medium=ppd&utm_campaign=cp" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/dpd/?utm_source=administrace&utm_medium=ppd&utm_campaign=dpd" target="_blank" class="toret-plugins-admin-link">DPD</a>
            </td>
            <td>
                Plugin pro WooCommerce, který integruje službu DPD. Na jeden klik vytisknete přepravní štítky, odešlete balík do systému DPD, vygenerujete trackovací číslo pro zákazníka a odešle mu je.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/dpd/?utm_source=administrace&utm_medium=ppd&utm_campaign=dpd" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/dropshipping-cz-pro-woocommerce-bez-podpory/?utm_source=administrace&utm_medium=ppd&utm_campaign=dropshipping" target="_blank" class="toret-plugins-admin-link">Dropshipping.cz</a>
            </td>
            <td>
                Plugin pro propojení systému Dropshipping.cz a WooCommerce. Plugin odesílá vytvořené objednávky přímo do systému, kde se postarají o jejich distribuci.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/dropshipping-cz-pro-woocommerce-bez-podpory/?utm_source=administrace&utm_medium=ppd&utm_campaign=dropshipping" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/gls/" target="_blank" class="toret-plugins-admin-link">GLS</a>
            </td>
            <td>
                Plugin pro WooCommerce, který integruje službu GLS. Na jeden klik vytisknete přepravní štítky, “odešlete” balík do systému GLS, vygenerujete trackovací číslo pro zákazníka a odešle mu je.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/gls/" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/ppl/?utm_source=administrace&utm_medium=ppd&utm_campaign=PPL" target="_blank" class="toret-plugins-admin-link">PPL</a>
            </td>
            <td>
                Plugin pro WooCommerce, který integruje službu PPL. Na jeden klik vytisknete přepravní štítky, odešlete balík do systému PPL, vygenerujete trackovací číslo pro zákazníka a odešle mu je.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/ppl/?utm_source=administrace&utm_medium=ppd&utm_campaign=PPL" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woocommerce-sledovani-zasilek-cz/?utm_source=administrace&utm_medium=ppd&utm_campaign=Sledovani-zasilek-cz" target="_blank" class="toret-plugins-admin-link">Sledování Zásilek CZ</a>
            </td>
            <td>
                Pošlete zákazníkovi sledovací číslo balíku. Plugin podporuje dopravce Česká pošta, DPD, PPL, DHL, GLS, Zásilkovna, Uloženka, Geis, Slovenská Pošta a InTime.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woocommerce-sledovani-zasilek-cz/?utm_source=administrace&utm_medium=ppd&utm_campaign=Sledovani-zasilek-cz" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woocommerce-sledovani-zasilek/?utm_source=administrace&utm_medium=ppd&utm_campaign=Sledovani-zasilek-cz" target="_blank" class="toret-plugins-admin-link">Sledování Zásilek SK</a>
            </td>
            <td>
                Pošlete zákazníkovi sledovací číslo balíku. Plugin podporuje dopravce Slovenská Pošta, DPD, Geis, DHL, GLS a UPS.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woocommerce-sledovani-zasilek/?utm_source=administrace&utm_medium=ppd&utm_campaign=Sledovani-zasilek-cz" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/toptrans/?utm_medium=ppd&utm_campaign=TOPTRANS" target="_blank" class="toret-plugins-admin-link">TOPTRANS</a>
            </td>
            <td>
                Plugin pro WooCommerce, který integruje službu TOPTRANS. Na jeden klik vytisknete přepravní štítky, “odešlete” balík do systému TOPTRANS, vygenerujete trackovací číslo pro zákazníka a odešle mu je.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/toptrans/?utm_medium=ppd&utm_campaign=TOPTRANS" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/vlastni-pobocky?utm_medium=ppd&utm_campaign=BRANCHES" target="_blank" class="toret-plugins-admin-link">Vlastní pobočky</a>
            </td>
            <td>
                Plugin pro WooCommerce, který umožňuje vytvoření vlastní výdejní sítě (poboček), ze kterých si můžou zákazníci v pokladně vybrat.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/vlastni-pobocky?utm_medium=ppd&utm_campaign=BRANCHES" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/vyzvedni-to/?utm_source=administrace&utm_medium=ppd&utm_campaign=vyzvednito" target="_blank" class="toret-plugins-admin-link">Vyzvedni.to</a>
            </td>
            <td>
                Plugin pro WooCommerce, který integruje dopravní metody dopravce Vyzvedni.to. Na jeden klik vytisknete přepravní štítky, odešlete balík do systému Vyzvedni.to, vygenerujete trackovací číslo pro zákazníka a odešle mu je.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/vyzvedni-to/?utm_source=administrace&utm_medium=ppd&utm_campaign=vyzvednito" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woocommerce-wedo/?utm_source=administrace&utm_medium=ppd&utm_campaign=WEDO" target="_blank" class="toret-plugins-admin-link">WE|DO</a>
            </td>
            <td>
                Propojení e-shopu a služby WE|DO home, Uloženka a boxy. Přes plugin odešlete balík do systému WE|DO, vytisknete přepravní štítky, plugin vygeneruje trackovací číslo pro zákazníka a odešle mu jej.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woocommerce-wedo/?utm_source=administrace&utm_medium=ppd&utm_campaign=WEDO" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woocommerce-zasilkovna/?utm_source=administrace&utm_medium=ppd&utm_campaign=Zasilkovna" target="_blank" class="toret-plugins-admin-link">Zásilkovna</a>
            </td>
            <td>
                Propojení e-shopu a služby Zásilkovna. Přes plugin “odešlete” balík do systému Zásilkovny, na jeden klik vytisknete přepravní štítky, plugin vygeneruje trackovací číslo pro zákazníka a odešle mu jej.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woocommerce-zasilkovna/?utm_source=administrace&utm_medium=ppd&utm_campaign=Zasilkovna" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <?php /*        KONEC    */ ?>
    </tbody>
</table>

<h3>Pluginy pro platební brány</h3>
<table>
<thead>
    <tr>
        <th>Jméno pluginu</th>
        <th>Popisek</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
        <?php /*    KOPÍROVAT    */ ?>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woo-comgate/?utm_source=administrace&utm_medium=ppd&utm_campaign=Comgate" target="_blank" class="toret-plugins-admin-link">Comgate</a>
            </td>
            <td>
                Integrace platební brány Comgate. Využívejte moderní platební metody, jako je platba kartou nebo zrychleným bankovním převodem.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woo-comgate/?utm_source=administrace&utm_medium=ppd&utm_campaign=Comgate" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/csob/" target="_blank" class="toret-plugins-admin-link">ČSOB</a>
            </td>
            <td>
                Plugin pro WooCommerce, který integruje platební bránu ČSOB.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/csob/" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woo-gp-webpay/?utm_source=administrace&utm_medium=ppd&utm_campaign=GPwebpay" target="_blank" class="toret-plugins-admin-link">GP webpay</a>
            </td>
            <td>
                Integrace platební brány GP webpay. Přidejte do e-shopu možnost platby kartou.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woo-gp-webpay/?utm_source=administrace&utm_medium=ppd&utm_campaign=GPwebpay" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woo-gopay-inline-platebni-brana/?utm_source=administrace&utm_medium=ppd&utm_campaign=GoPayInline" target="_blank" class="toret-plugins-admin-link">GoPay Inline</a>
            </td>
            <td>
                Integrace platební brány GoPay. Využívejte moderní platební metody, jako jsou platební peněženky (GoPay a Apple Pay), platba kartou nebo zrychleným bankovním převodem.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woo-gopay-inline-platebni-brana/?utm_source=administrace&utm_medium=ppd&utm_campaign=GoPayInline" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/thepay-2-0/?utm_source=administrace&utm_medium=ppd&utm_campaign=thepay20" target="_blank" class="toret-plugins-admin-link">ThePay 2.0</a>
            </td>
            <td>
                Plugin pro WooCommerce, který integruje platební bránu ThePay 2.0.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/thepay-2-0/?utm_source=administrace&utm_medium=ppd&utm_campaign=thepay20" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <?php /*        KONEC    */ ?>
    </tbody>
</table>

<h3>Pluginy pro fakturace a účetnictví</h3>
<table>
<thead>
    <tr>
        <th>Jméno pluginu</th>
        <th>Popisek</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
        <?php /*    KOPÍROVAT    */ ?>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woocommerce-fakturoid/?utm_source=administrace&utm_medium=ppd&utm_campaign=Fakturoid" target="_blank" class="toret-plugins-admin-link">Fakturoid</a>
            </td>
            <td>
                Propojení e-shopu a fakturačního systému Fakturoid. Automatizujte tvorbu faktur a posílejte je přímo zákazníkům.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woocommerce-fakturoid/?utm_source=administrace&utm_medium=ppd&utm_campaign=Fakturoid" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/toret-fio/?utm_source=administrace&utm_medium=ppd&utm_campaign=Fio" target="_blank" class="toret-plugins-admin-link">Fio</a>
            </td>
            <td>
                Automatizujte kontrolu zaplacení objednávky na bankovní účet ve FIO Bance.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/toret-fio/?utm_source=administrace&utm_medium=ppd&utm_campaign=Fio" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woo-idoklad/?utm_source=administrace&utm_medium=ppd&utm_campaign=iDoklad" target="_blank" class="toret-plugins-admin-link">iDoklad</a>
            </td>
            <td>
                Propojení e-shopu a fakturačního systému iDoklad. Automatizujte tvorbu faktur a posílejte je přímo zákazníkům.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woo-idoklad/?utm_source=administrace&utm_medium=ppd&utm_campaign=iDoklad" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/toret-vyfakturuj/?utm_source=administrace&utm_medium=ppd&utm_campaign=Vyfakturuj" target="_blank" class="toret-plugins-admin-link">Vyfakturuj</a>
            </td>
            <td>
                Propojení e-shopu a fakturačního systému Vyfakturuj. Automatizujte tvorbu faktur a posílejte je přímo zákazníkům.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/toret-vyfakturuj/?utm_source=administrace&utm_medium=ppd&utm_campaign=Vyfakturuj" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/qr-platby/?utm_source=administrace&utm_medium=ppd&utm_campaign=qrplatby" target="_blank" class="toret-plugins-admin-link">QR platby</a>
            </td>
            <td>
                Plugin pro WooCommerce, který do e-shopu přidává možnost přidání QR kódu na děkovné stránce a ve WooCommerce e-mailu při použití platební metody Bankovním převodem nebo Platba na účet.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/qr-platby/?utm_source=administrace&utm_medium=ppd&utm_campaign=qrplatby" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/zaokrouhleni-objednavky/" target="_blank" class="toret-plugins-admin-link">Zaokrouhlení objednávky</a>
            </td>
            <td>
                WooCommerce plugin pro zaokrouhlení ceny objednávky. 
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/zaokrouhleni-objednavky/" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <?php /*        KONEC    */ ?>
    </tbody>
</table>

<h3>Pluginy pro srovnávače zboží</h3>
<table>
<thead>
    <tr>
        <th>Jméno pluginu</th>
        <th>Popisek</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
        <?php /*    KOPÍROVAT    */ ?>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/glami-pixel-woocommerce/?utm_source=administrace&utm_medium=ppd&utm_campaign=GlamiPixel" target="_blank" class="toret-plugins-admin-link">Glami Pixel</a>
            </td>
            <td>
                Implementace konverzního kódu pro srovnávač zboží Glami. Měření zobrazení stránek, produktů, přidání do košíku a dokončených objednávek.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/glami-pixel-woocommerce/?utm_source=administrace&utm_medium=ppd&utm_campaign=GlamiPixel" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/srovnavace-zbozi/" target="_blank" class="toret-plugins-admin-link">Srovnávače zboží</a>
            </td>
            <td>
                WooCommerce integrační plugin pro propojení srovnávačů zboží (recenze, ověřeno zákazníky, měřící kódy).
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/srovnavace-zbozi/" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/xml-feeds/" target="_blank" class="toret-plugins-admin-link">XML Feeds</a>
            </td>
            <td>
                Vytváření XML feedů pro porovnávače zboží Heuréka CZ, Heuréka SK, Najnakup, Pricemania, Google Nákupy, Glami a další.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/xml-feeds/" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <?php /*        KONEC    */ ?>
    </tbody>
</table>

<h3>Ostatní</h3>
<table>
<thead>
    <tr>
        <th>Jméno pluginu</th>
        <th>Popisek</th>
        <th></th>
    </tr>
    </thead>
    <tbody>
        <?php /*    KOPÍROVAT    */ ?>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/breadcrumbs/?utm_source=administrace&utm_medium=ppd&utm_campaign=breadcrumbs" target="_blank" class="toret-plugins-admin-link">Drobečková navigace</a>
            </td>
            <td>
                Vytvářejte vlastní strukturu drobečkové navigace pomocí WordPress menu. Kombinujte všechny typy obsahu přesně tak, jak potřebujete.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/breadcrumbs/?utm_source=administrace&utm_medium=ppd&utm_campaign=breadcrumbs" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/dotaz-na-produkt/?utm_source=administrace&utm_medium=ppd&utm_campaign=dotaz-na-produkt" target="_blank" class="toret-plugins-admin-link">Dotaz na produkt</a>
            </td>
            <td>
                Přidejte ke svým WooCommerce produktům kontaktní formulář, přes který vám může zákazník napsat.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/dotaz-na-produkt/?utm_source=administrace&utm_medium=ppd&utm_campaign=dotaz-na-produkt" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/order-numbers/?utm_source=administrace&utm_medium=ppd&utm_campaign=ordernumbers" target="_blank" class="toret-plugins-admin-link">Order Numbers</a>
            </td>
            <td>
                Plugin pro WooCommerce, který do e-shopu přidává postupné číslování objednávek a možnost vlastního číslování objednávek.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/order-numbers/?utm_source=administrace&utm_medium=ppd&utm_campaign=ordernumbers" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/sklik-mereni-konverzi-retargeting/?utm_source=administrace&utm_medium=ppd&utm_campaign=Sklik" target="_blank" class="toret-plugins-admin-link">Sklik.cz</a>
            </td>
            <td>
                Implementace konverzního a retargetingového kódu pro systém Sklik.cz.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/sklik-mereni-konverzi-retargeting/?utm_source=administrace&utm_medium=ppd&utm_campaign=Sklik" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/woo-smart-emailing/?utm_source=administrace&utm_medium=ppd&utm_campaign=SmartEmailing" target="_blank" class="toret-plugins-admin-link">SmartEmailing</a>
            </td>
            <td>
                Propojení e-shopu a systému pro rozesílání newsletterů SmartEmailing. Přidejte na stránku pokladny možnost přihlášení k newsletteru.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/woo-smart-emailing/?utm_source=administrace&utm_medium=ppd&utm_campaign=SmartEmailing" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/telefon/" target="_blank" class="toret-plugins-admin-link">Telefon</a>
            </td>
            <td>
                Vylepšuje pole telefon ve WooCommerce pokladně.
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/telefon/" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <tr>
            <td>
                <a href="https://toret.cz/produkt/vokativ/" target="_blank" class="toret-plugins-admin-link">České oslovení</a>
            </td>
            <td>
                WooCommerce plugin, který automaticky upravuje oslovení zákazníka v e-mailu a sekci Můj účet, kde používá 5. pád (vokativ).
            </td>
            <td class="toret-plugins-admin-last">
                <a href="https://toret.cz/produkt/vokativ/" target="_blank" class="toret-plugins-admin-buy">Koupit</a>
            </td>
        </tr>
        <?php /*        KONEC    */ ?>
    </tbody>
</table>


    </div>

    <div>
        <a href="https://toret.cz/#kategorie-hp" target="_blank">
            <img alt="Toret Reviews" src="<?php echo esc_url(WP_PLUGIN_URL .'/toret-manager/admin/img/recenze_banner.png');?>" class="toret-plugins-admin-img"
            />
        </a>
    </div>

    <h2>Diagnostika</h2>
    <p>V případě problému s pluginem si prosím pomocí tlačítka Stáhnout vygenerujte report a pošlete jej na <a href="mailto:podpora@toret.cz">podpora@toret.cz</a>.</p>
    <p>Pro detailnější diagnostiku problému je dobré poslat i chyby z WordPress debug nástroje. Návod jak jej aktivovat najdete v článku <a href="https://www.wplama.cz/jak-u-wordpressu-aktivovat-debug/" target="_blank">Jak u WordPressu aktivovat debug</a>.</p>
    <button id="toret-copy"><span class="dashicons dashicons-download"></span> Stáhnout</button>
    <div id="toret-plugins-admin-diag">
     
        ***************************************<br />
        Toret - Diagnostika<br />
        ***************************************<br /><br />

        WEB: <?php echo esc_url(site_url()); ?><br />
        Plugin DIR: <?php echo esc_url(WP_PLUGIN_URL); ?><br /><br />

        PHP: <?php if( function_exists('phpversion') ){ echo esc_html(phpversion()); } ?><br />
        SOAP: <?php if (extension_loaded('soap')) { echo 'ACTIVE'; }?><br />
        CURL: <?php if (function_exists('curl_version')) { echo 'ACTIVE'; }?><br /><br />

        Woocommerce: <?php         $check = true;

                    if ( function_exists( 'is_multisite' ) && is_multisite() ) {
                        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                        if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                            $check = false;
                        }
                    } else {
                        if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                            $check = false;
                        }
                    }

                    if ( $check === true ) { echo esc_html(WC_VERSION); }?><br />
        Wordpress: <?php echo bloginfo('version');?><br /><br />

        ***************************************<br />
        Plugin list<br />
        ***************************************<br /><br />

        <?php

        $plugins = get_option( 'active_plugins' );
        $html = '';

        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        foreach ( $plugins as $plugin ) {
        $plugin_data = get_plugin_data( ABSPATH . 'wp-content/plugins/' . $plugin );
        ?><?php echo  esc_html($plugin_data['Name']); ?>  - <?php echo esc_html($plugin_data['Version']); ?>  - <?php echo  esc_html($plugin_data['Author']); ?>  - <?php echo  esc_html($plugin_data['TextDomain']); ?> <br />

        <?php
        }

        ?>

        <br /><br />

        ***************************************<br />
        Plugin Detail<br />
        ***************************************<br /><br />

        <?php do_action('toret_plugins_diag'); ?>

    </div>

</div>

