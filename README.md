# JS internationalisation for Yii2

This repository contains some of the code I ripped out of a much larger application where I implemented
i18n for javascript/HTML.

It provides:
- a `yii.t()`-function to translate things in javascript
- a jQuery plugin that supports translating the content of html tags automatically with `data-i18` attributes, see the js for examples
- Parsing of messages in javascript/html files
 
I will try to answer questions about it but I wrote this a couple years ago and things are a bit fuzzy.
Yes, I do now my javascript skills are kinda crap, deal with it :)
 
If you decide this code, I'd like a shout-out. 
If you decide to try to get this thing grown up into a full plugin, I will accept pull requests on it and am prepared invest a little time in it (like get it in packagist and so on).

 
**THIS CODE PROBABLY DOES NOT WORK AS IS AND WAS NOT TESTED**

I just published this to help people out there get started with it.

Included files serve the following purposes:

## commands/MessageController` ## 
A derived message controller that also supports "parsing" javascript to extract the messages.
It will look for both `yii.t(` instances and `data-i18n` attributes on HTML tags.

## yii ##
Shows you how to override the system MessageController. If you have different environments, you'll obviously do the modifications in the `yii`-file there.

## web/assets/config.js ##
A dummy config object (the original code didn't work like that)

## web/assets/yii.i18n.js ##
Originally the file was part of a different namespace, but I put it back in the yii namespace.

This is the most important file. When using the provided `yii.t` function you can chose to either use categories
or just use text, it should detect it fine.
It has limited support for "{token}"-replacement in the translations, so it accepts an object/array with keys/values for that purpose.
