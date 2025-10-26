//Lets get this party started.
var leantime = leantime || {};

var themeColor = jQuery('meta[name=theme-color]').attr("content");
leantime.companyColor = themeColor;

var colorScheme = jQuery('meta[name=color-scheme]').attr("content");
leantime.colorScheme = colorScheme;

var theme = jQuery('meta[name=theme]').attr("content");
leantime.theme = theme;

var appURL = jQuery('meta[name=identifier-URL]').attr("content");
leantime.appUrl = appURL;

var leantimeVersion = jQuery('meta[name=leantime-version]').attr("content");
leantime.version = leantimeVersion;

leantime.replaceSVGColors = function () {

    jQuery(document).ready(function () {

        if (leantime.companyColor != "#1b75bb") {
            jQuery("svg").children().each(function () {
                if (jQuery(this).attr("fill") == "#1b75bb") {
                    jQuery(this).attr("fill", leantime.companyColor);
                }
            });
        }

    });

};

leantime.handleAsyncResponse = function (response) {

    if (response !== undefined) {
        if (response.result !== undefined && response.result.html !== undefined) {
            var content = jQuery(response.result.html);
            jQuery("body").append(content);
        }
    }
};

jQuery.noConflict();

jQuery(document).ready(function () {

    leantime.replaceSVGColors();

    jQuery(".confetti").click(function () {
        confetti.start();
    });

    tippy('[data-tippy-content]');

    if (jQuery('.login-alert .alert').text() !== '') {
        jQuery('.login-alert').fadeIn();
    }

    document.addEventListener('scroll', () => {
        document.documentElement.dataset.scroll = window.scrollY;
    });

});

htmx.onLoad(function(element){
    tippy('[data-tippy-content]');
});

window.addEventListener("HTMX.ShowNotification", function(evt) {
    jQuery.get(leantime.appUrl+"/notifications/getLatestGrowl", function(data){
        let notification = JSON.parse(data);

        if(notification.notification && notification.notification !== "undefined") {
            jQuery.growl({
                message: notification.notification, style: notification.notificationType
            });
        }
    })
});

//function to check if a color is dark or light
function isColorDark(color, element = document.body) {
    let resolvedColor = color;

    // --- 1️⃣ Resolve CSS variable ---
    if (color.startsWith('var(')) {
    const varName = color.slice(4, -1).trim();
    resolvedColor = getComputedStyle(element).getPropertyValue(varName).trim();
}

    // --- 2️⃣ Create a temporary element to resolve any CSS color value ---
    const div = document.createElement('div');
    div.style.color = resolvedColor;
    document.body.appendChild(div);
    const computed = getComputedStyle(div).color;
    document.body.removeChild(div);

    // computed is always like "rgb(r, g, b)"
    const [r, g, b] = computed.match(/\d+/g).map(Number);

    // --- 3️⃣ Compute luminance ---
    const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b;

    return luminance < 128;
}

