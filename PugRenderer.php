<?php
/**
 * @link https://github.com/borodulin/yii2-pug
 * @copyright Copyright (c) 2016 Andrey Borodulin
 * @license https://github.com/borodulin/yii2-pug/blob/master/LICENSE
 */

namespace conquer\pug;

use Pug\Pug;
use Yii;
use yii\base\ViewRenderer;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

/**
 * @link https://github.com/pug-php/pug
 *
 * Class PugRenderer
 * @package conquer\pug
 */
class PugRenderer extends ViewRenderer
{
    public $cachePath = '@runtime/Pug/cache';
    public $cacheDuration = 0;

    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @var array Pug options.
     */
    public $options;

    /**
     * @var array
     */
    public $filters;

    /**
     * @var array
     */
    public $keyWords;

    /**
     * {@inheritDoc}
     */
    public function init()
    {
        $this->cachePath = Yii::getAlias(rtrim($this->cachePath, '\\/'));
        FileHelper::createDirectory($this->cachePath);
        $this->options = ArrayHelper::merge([
            'extension' => '.pug',
            'expressionLanguage' => 'php',
            'prettyprint' => $this->debug,
        ], (array)$this->options);
    }

    /**
     * @param \yii\base\View $view
     * @param string $file
     * @param array $params
     * @return string
     */
    public function render($view, $file, $params)
    {
        $filename = $this->cachePath . DIRECTORY_SEPARATOR . md5($file) . '.php';
        if ($this->debug || !file_exists($filename) || (time() - filemtime($filename) >= $this->cacheDuration)) {
            $options = ArrayHelper::merge([
                'basedir' => dirname($file),
            ], $this->options);
            $pug = new Pug($options);
            if (is_array($this->filters)) {
                foreach ($this->filters as $name => $callback) {
                    $pug->filter($name, $callback);
                }
            }
            if (is_array($this->keyWords)) {
                foreach ($this->keyWords as $name => $callback) {
                    $pug->addKeyword($name, $callback);
                }
            }
            $data = $pug->compile($file);
            file_put_contents($filename, $data);
        }
        return $view->renderPhpFile($filename, $params);
    }
}