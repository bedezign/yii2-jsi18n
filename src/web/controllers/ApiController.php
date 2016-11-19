<?php


class ApiController extends Controller
{
    public function actionTranslations($language, $category)
    {
        // If you want to use translation files
        $path = \Yii::getAlias('@messages/' . $language . '/' . $category . '.php');
        if (file_exists($path))
            return ['messages' => include($path)];
        else {
            // Perhaps you need database messages, select those here alternatively
            // ...
        }
        return [];
    }
}