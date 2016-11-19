<?php
/**
 * Override message controller just for the javascript functionality, the rest is left to the original controller
 * You can configure which extensions to intercept (just 'js' by default) and what the translator function is ('yii.t').
 * The extract function will match calls that contain 2 strings as argument or just one. In case of 1 string,
 * the application name is extracted from the file path and used as category
 */

namespace console\controllers;

use yii\helpers\Json;

/**
 * Extracts messages to be translated from source files.
 *
 * Class MessageController
 * @package console\controllers
 */
class MessageController extends \yii\console\controllers\MessageController
{
    /*** @var string            Base path for the applications, to auto determine the category if none is given */
    public $applicationPath     = '@frontend/applications';
    /** @var string             Global fallback category in case the filename is not part of an application */
    public $fallbackCategory    = 'common';
    /** @var string[]           Functions to consider as a translation call */
    public $translators         = ['yii.t'];
    /** @var string[]           Data attribute to consider as translation */
    public $attributes          = ['data-i18n'];

    /**
     * Extracts messages to be translated from source code.
     *
     * @param string $fileName
     * @param string|array $translator
     * @return array
     */
    protected function extractMessages($fileName, $translator, $ignoreCategories = [])
    {
        $messages = parent::extractMessages($fileName, $translator);

        // Do javascript stuff
        $defaultCategory = $this->getCategory($fileName);

        // Check for the javascript translation functions
        $content = file_get_contents($fileName);
        foreach ($this->translators as $translator)  {
            $translator = str_replace('.', '\\.', $translator);

            // Capture all translation function instances with simply a string as argument
            $offsets  = [];
            $template = '/\b' . $translator . '\s*\(\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*[,\)]/s';
            $matchCount = preg_match_all($template, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
            for ($n = 0; $n < $matchCount; $n ++) {
                $message = eval("return {$matches[$n][1][0]};");
                // Keep track of the offset so we can ignore it later
                $offsets[] = $matches[$n][0][1];
                // Make sure its not function call that has a category ("message" is a category and can be verified against set keys)
                if (!isset($messages[$message]))
                    $messages[$defaultCategory][] = $message;
            }

            // Function regex. Look for the translation function call with a category and a string, ignore the optional arguments behind that
            $template = '/\b' . $translator . '\s*\(\s*(\'.*?\'|\".*?\")\s*,\s*(\'.*?(?<!\\\\)\'|".*?(?<!\\\\)")\s*[,\)]/s';
            $matchCount = preg_match_all($template, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
            for ($n = 0; $n < $matchCount; $n ++)
                // The reg-ex is far from perfect. it will try to match a function without arguments over multiple lines as well, so ignore the previous matches
                if (!in_array($matches[$n][0][1], $offsets)) {
                    $category = eval("return {$matches[$n][1][0]};"); // Eliminate quotes and escaping etc
                    if (!isset($messages[$category])) $messages[$category] = [];
                    $messages[$category][] = eval("return {$matches[$n][2]};");
                }

        }

        // Do html tag translation extract as well (data-i18n attribute by default)
        foreach ($this->attributes as $attribute) {
            $template = '/\<[^>]*?' . $attribute . '=(\".*?\"|\'.*?\')/s';
            $matchCount = preg_match_all($template, $content, $matches, PREG_SET_ORDER );
            // The category for this is based on the filename, at this point we don't have functionality for this
            if (!isset($messages[$defaultCategory])) $messages[$defaultCategory] = [];
            for ($n = 0; $n < $matchCount; $n ++) {
                $result = eval("return {$matches[$n][1]};");
                try {
                    $object = Json::decode($result);
                    if (isset($object['text']))
                        $result = $object['text'];
                    else
                        continue;
                }
                catch (\Exception $e) { }
                $messages[$defaultCategory][] = $result;
            }
        }
        return $messages;
    }

    /**
     * For attribute translation we currently do not have a category, so use the file path to extract the application
     * name, that will be used as category
     *
     * @param $fileName
     * @return string
     */
    protected function getCategory($fileName)
    {
        $applications = rtrim(\Yii::getAlias($this->applicationPath), '/') . '/';
        if (substr($fileName, 0, strlen($applications)) != $applications)
            return $this->fallbackCategory;

        $parts = explode('/', substr($fileName, strlen($applications)));
        return reset($parts);
    }
}