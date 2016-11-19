/**
 * Translation functionality for javascript.
 * This module can translate 2 ways:
 * - If you are going to assign the result yourself, just use the yii.t function
 * - Html tags need to be translated, use $(selector).translate();
 *
 * Tag translation is done by specifying the data-i18n attribute.
 * The default behavior is to translate the content of that attribute and assign it as content of that node via .html()
 * You can influence this by specifying a target. The target can either be "html" (default), "text" (use the .text()-function instead)
 * or anything else (use the .prop() function).
 *
 * Specifying a target can be done by the data-i18n-target attribute or by making data-i18n a JSON object:
 *
 * <a data-i18n="Translated text"></a>
 *      - Content of a tag will be the translated text
 * <a data-i18n="Translated text"><i class="fa fa-trash"></i></a>
 *      - Content of a tag will be the translated text, icon will be removed
 * <a data-i18n="Translated text" data-i18n-target="title"><i class="fa fa-trash"></i></a>
 *      - The icon will be left alone, translation will be stored in the <a> title attribute.
 * <a data-i18n="{'text': 'Translated text', 'target': 'title'}"><i class="fa fa-trash"></i></a>
 *      - Same thing
 *
 * This component expects a window.i18nconfig object set with an url and appLanguage.
 * Feel free to change this behavior as it wasn't like this originally
 */

/**
 * Takes an object and an array of strings and returns the sub-property. false will be returned if not found.
 * Similar to isDefined but for own functions
 */
$.getSubObject = function(object, path)
{
    if (typeof path == 'string')
        path = path.split('.');

    while (path.length) {
        var property = path.shift();
        if (typeof object[property] === 'undefined')
            return false;

        object = object[property];
    }
    return object;
};

yii.i18n = (function($) {
    var pub = {
        isActive: true,
        init: function () {

            // Link the functionality in the yii namespace (backwards compatibility)
            yii.loadTranslations = pub.loadTranslations;
            yii.t = pub.t;
            yii.nodeTranslate = pub.nodeTranslate;

           /**
            * Helper function, allows you to call this directly on any jquery element
            * @param category
            * @returns {*|jQuery|HTMLElement}
            */
           $.fn.translate = function(category)
           {
               var node = $(this);
               pub.nodeTranslate(category, node);
               return node;
           };

           /**
            * Helper translation function, but on a regular text.
            * Note: If you added data to some of the nodes in here using $.fn.data(), that will be lost.
            * @returns {string}
            */
           $.translate = function(string) {
               var object = $($.parseHTML(string));
               object.translate();
               return object.outerHTML();
           };
        },

        loadTranslations: function(category) {
            var config = window.i18nconfig, url = config.url, lng = config.appLanguage;

            if ($.getSubObject(_translations, lng + "." + category))
               return _translations[lng][category];

            if (!_translations[lng])
                _translations[lng] = {};

            return $.get(url, {language: lng,  category: category})
                .then(function(messages) { _translations[lng][category] = messages; });
        },

        t: function(category, text, variables)
        {
            if (typeof text == 'undefined' || typeof text == 'object') {
                // Called without category, move over everything
                variables = text;
                text = category;
                category = undefined;
            }

            // Load message category
            var messages = pub.loadTranslations(category);
            if (typeof messages.then === 'function')
                // Deferred, you should've loaded the translation on initialisation... Hope you are prepared for a Promise object
                return messages.done(function() {
                    pub.t(category, text, variables)
                });

            // Only replace the text if we have a set and not empty result (empty just means "use the original text")
            if ($.isset(messages[text]) && messages[text].length)
                text = messages[text];

            // If we have variables to replace...
            variables = $.extend({}, variables);
            if (!$.isEmptyObject(variables)) {
                for (var index in variables) {
                    var search = index, replace = variables[index];
                    if (search.substr(0, 1) != '{') search = '{' + search + '}';
                    text = text.split(search).join(replace);
                }
            }

            return text;
        },

        /**
         * Locates every node with a "data-i18n"-attribute under the given root-node and replaces the html with the translation
         * @param category      string, translation category
         * @param node          jQuery node, root item for the replacement search
         */
        nodeTranslate: function(category, node)
        {
            $('[data-i18n]', node).each(function(){
                var node = $(this),
                    target = node.data('i18n-target'),
                    data = node.data('i18n');

                if (typeof data == 'object') {
                    if (data.target) target = data.target;
                    if (data.text) data = data.text;
                }

                var translation = pub.t(category, data);

                if (target) {
                    target = target.split(',');
                    for (var index in target) {
                        var type = target[index];
                        if ($.inArray(type,  ['html', 'text']) != -1)
                            // content, call the function
                            node[type](translation);
                        else
                            // attribute
                            node.prop(type, translation);
                    }
                }
                else
                    node.html(translation);
            });
        }
    };

    var _translations = {};

    return pub;
})(jQuery);
